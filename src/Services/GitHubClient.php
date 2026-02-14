<?php

namespace Youyingxiang\WeeklyReport\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GitHubClient
{
    protected string $token;

    protected string $baseUrl = 'https://api.github.com';

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? config('weekly-report.github.token', '');
    }

    /**
     * Fetch a single issue's details.
     */
    public function getIssue(string $owner, string $repo, int $number): ?array
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
            'User-Agent' => 'youyingxiang-weekly-report',
        ];

        if ($this->token) {
            $headers['Authorization'] = "Bearer {$this->token}";
        }

        return $headers;
    }
}
