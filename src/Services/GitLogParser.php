<?php

namespace Yxx\WeeklyReport\Services;

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

            if (! is_dir($path . '/.git')) {
                continue;
            }

            // Auto-detect owner/repo from git remote if not configured
            $owner = $repoConfig['owner'] ?? null;
            $repo = $repoConfig['repo'] ?? null;

            if (! $owner || ! $repo) {
                [$owner, $repo] = $this->resolveOwnerRepoFromRemote($path, $owner, $repo);
            }

            $repoName = ($owner ?? '') . '/' . ($repo ?? basename($path));

            $result = $this->parse($path, $since, $until, $authorEmail, $branch);

            // Tag commits with repo name
            $taggedCommits = $result['commits']->map(function ($commit) use ($repoName) {
                $commit['repo'] = $repoName;
                return $commit;
            });

            // Tag issues with repo config for GitHub API lookup
            $taggedIssues = $result['issues']->map(function ($number) use ($owner, $repo) {
                return [
                    'number' => $number,
                    'owner'  => $owner,
                    'repo'   => $repo,
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

    /**
     * Parse owner/repo from `git remote get-url origin`.
     *
     * Supports:
     *   git@github.com:owner/repo.git
     *   https://github.com/owner/repo.git
     *
     * @return array{0: ?string, 1: ?string} [owner, repo]
     */
    protected function resolveOwnerRepoFromRemote(string $path, ?string $owner, ?string $repo): array
    {
        $output = [];
        @exec('git -C ' . escapeshellarg($path) . ' remote get-url origin 2>/dev/null', $output);

        $url = trim($output[0] ?? '');
        if (! $url) {
            return [$owner, $repo];
        }

        // git@github.com:owner/repo.git
        if (preg_match('#[:/]([^/]+)/([^/]+?)(?:\.git)?$#', $url, $m)) {
            return [$owner ?? $m[1], $repo ?? $m[2]];
        }

        return [$owner, $repo];
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
