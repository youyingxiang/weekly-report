<?php

namespace Yxx\WeeklyReport\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GitHubClient
{
    protected string $token;

    protected string $baseUrl = 'https://api.github.com';

    public function __construct(?string $token = null)
    {
        $this->token = $token
            ?? static::resolveTokenFromGhCli()
            ?? '';
    }

    /**
     * Try to get token from `gh auth token` (GitHub CLI).
     */
    protected static function resolveTokenFromGhCli(): ?string
    {
        $output = [];
        $code = 0;
        @exec('gh auth token 2>/dev/null', $output, $code);

        if ($code === 0 && ! empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Fetch a single issue's details.
     */
    public function getIssue(?string $owner, ?string $repo, int $number): ?array
    {
        if (! $owner || ! $repo) {
            return null;
        }

        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/issues/{$number}");

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'number' => $number,
            'title'  => $data['title'] ?? "Issue #{$number}",
            'state'  => $data['state'] ?? 'unknown',
            'url'    => $data['html_url'] ?? "https://github.com/{$owner}/{$repo}/issues/{$number}",
            'repo'   => "{$owner}/{$repo}",
            'labels' => collect($data['labels'] ?? [])->pluck('name')->toArray(),
        ];
    }

    /**
     * Fetch multiple issues in batch.
     *
     * @param  Collection  $issues  Collection of [{number, owner, repo}]
     */
    public function getIssues(Collection $issues): Collection
    {
        return $issues->map(function ($issue) {
            $result = $this->getIssue($issue['owner'], $issue['repo'], $issue['number']);

            if (! $result) {
                return [
                    'number' => $issue['number'],
                    'title'  => "Issue #{$issue['number']} (unable to fetch)",
                    'state'  => 'unknown',
                    'url'    => $issue['owner'] && $issue['repo']
                        ? "https://github.com/{$issue['owner']}/{$issue['repo']}/issues/{$issue['number']}"
                        : '#',
                    'repo'   => ($issue['owner'] ?? '') . '/' . ($issue['repo'] ?? ''),
                    'labels' => [],
                ];
            }

            return $result;
        });
    }

    protected function headers(): array
    {
        $headers = [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'yxx-weekly-report',
        ];

        if ($this->token) {
            $headers['Authorization'] = "Bearer {$this->token}";
        }

        return $headers;
    }
}
