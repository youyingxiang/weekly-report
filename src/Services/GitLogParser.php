<?php

namespace Youyingxiang\WeeklyReport\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class GitLogParser
{
    /**
     * Parse git log for commits within a date range and extract issue numbers.
     *
     * @return array{commits: Collection, issues: Collection}
     */
    public function parse(string $repoPath, Carbon $since, Carbon $until, ?string $authorEmail = null, ?string $branch = null): array
    {
        $command = $this->buildCommand($repoPath, $since, $until, $authorEmail, $branch);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Failed to run git log in {$repoPath}. Return code: {$returnCode}");
        }

        $commits = collect();
        $issues = collect();

        foreach ($output as $line) {
            $parts = explode('|||', $line, 3);
            if (count($parts) < 3) {
                continue;
            }

            [$hash, $date, $message] = $parts;
            $hash = trim($hash);
            $date = trim($date);
            $message = trim($message);

            $commits->push([
                'hash'    => $hash,
                'date'    => $date,
                'message' => $message,
            ]);

            // Extract #issue references from commit message
            if (preg_match_all('/#(\d+)/', $message, $matches)) {
                foreach ($matches[1] as $issueNumber) {
                    $issues->push((int) $issueNumber);
                }
            }
        }

        return [
            'commits' => $commits,
            'issues'  => $issues->unique()->values(),
        ];
    }

    /**
     * Scan multiple repositories and aggregate results.
     *
     * @param  array  $repositories  Array of repo configs [{path, owner, repo, branch}]
     * @return array{commits: Collection, issues: Collection}
     */
    public function scanRepositories(array $repositories, Carbon $since, Carbon $until, ?string $authorEmail = null): array
    {
        $allCommits = collect();
        $allIssues = collect();

        foreach ($repositories as $repoConfig) {
            $path = $repoConfig['path'];
            $branch = $repoConfig['branch'] ?? null;
            $repoName = ($repoConfig['owner'] ?? '') . '/' . ($repoConfig['repo'] ?? basename($path));

            if (! is_dir($path . '/.git')) {
                continue;
            }

            $result = $this->parse($path, $since, $until, $authorEmail, $branch);

            // Tag commits with repo name
            $taggedCommits = $result['commits']->map(function ($commit) use ($repoName) {
                $commit['repo'] = $repoName;
                return $commit;
            });

            // Tag issues with repo config for GitHub API lookup
            $taggedIssues = $result['issues']->map(function ($number) use ($repoConfig) {
                return [
                    'number' => $number,
                    'owner'  => $repoConfig['owner'] ?? null,
                    'repo'   => $repoConfig['repo'] ?? null,
                ];
            });

            $allCommits = $allCommits->merge($taggedCommits);
            $allIssues = $allIssues->merge($taggedIssues);
        }

        // Deduplicate issues by owner/repo/number
        $uniqueIssues = $allIssues->unique(function ($issue) {
            return $issue['owner'] . '/' . $issue['repo'] . '#' . $issue['number'];
        })->values();

        return [
            'commits' => $allCommits,
            'issues'  => $uniqueIssues,
        ];
    }

    protected function buildCommand(string $repoPath, Carbon $since, Carbon $until, ?string $authorEmail, ?string $branch): string
    {
        $sinceStr = $since->format('Y-m-d');
        $untilStr = $until->format('Y-m-d');

        $cmd = sprintf(
            'git -C %s log --since=%s --until=%s --format="%%h|||%%ai|||%%s"',
            escapeshellarg($repoPath),
            escapeshellarg($sinceStr),
            escapeshellarg($untilStr)
        );

        if ($authorEmail) {
            $cmd .= ' --author=' . escapeshellarg($authorEmail);
        }

        if ($branch) {
            $cmd .= ' ' . escapeshellarg($branch);
        }

        return $cmd;
    }
}
