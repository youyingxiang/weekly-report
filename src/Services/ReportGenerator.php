<?php

namespace Yxx\WeeklyReport\Services;

use Carbon\Carbon;
use Yxx\WeeklyReport\Mail\PreviewReportMail;
use Yxx\WeeklyReport\Mail\FinalReportMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ReportGenerator
{
    public function __construct(
        protected GitLogParser $gitLogParser,
        protected GitHubClient $gitHubClient,
    ) {}

    /**
     * Generate report data for a given week.
     */
    public function generate(int $weeksAgo = 0): array
    {
        $weekStart = Carbon::now()->subWeeks($weeksAgo)->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::FRIDAY);
        $untilDate = $weekEnd->copy()->addDay();

        $repositories = config('weekly-report.repositories', []);

        $result = $this->gitLogParser->scanRepositories(
            $repositories,
            $weekStart,
            $untilDate,
        );

        $issues = collect();
        if ($result['issues']->isNotEmpty()) {
            $issues = $this->gitHubClient->getIssues($result['issues']);
        }

        $repoNames = collect($repositories)->map(function ($r) {
            return ($r['owner'] ?? '') . '/' . ($r['repo'] ?? basename($r['path']));
        })->toArray();

        return [
            'week_start'   => $weekStart,
            'week_end'     => $weekEnd,
            'commits'      => $result['commits'],
            'issues'       => $issues,
            'repositories' => $repoNames,
        ];
    }

    /**
     * Store report in cache and send preview email.
     */
    public function sendPreview(array $reportData): string
    {
        $key = 'weekly-report:' . Str::random(32);
        $previewTo = config('weekly-report.mail.preview_to');
        $recipients = config('weekly-report.mail.recipients', []);
        $ttl = 1440;

        $report = [
            'week_start'   => $reportData['week_start']->format('Y-m-d'),
            'week_end'     => $reportData['week_end']->format('Y-m-d'),
            'issues'       => $reportData['issues']->toArray(),
            'commits'      => $reportData['commits']->toArray(),
            'repositories' => $reportData['repositories'],
            'recipients'   => $recipients,
            'status'       => 'preview_sent',
        ];

        Cache::put($key, $report, now()->addMinutes($ttl));

        $expiration = now()->addMinutes($ttl);

        $confirmUrl = URL::temporarySignedRoute(
            'weekly-report.confirm',
            $expiration,
            ['key' => $key]
        );

        $cancelUrl = URL::temporarySignedRoute(
            'weekly-report.cancel',
            $expiration,
            ['key' => $key]
        );

        Mail::to($previewTo)->send(new PreviewReportMail(
            report: $report,
            confirmUrl: $confirmUrl,
            cancelUrl: $cancelUrl,
        ));

        return $key;
    }

    /**
     * Send the final report to all recipients.
     */
    public function sendFinal(array $report): void
    {
        $recipients = $report['recipients'] ?? [];

        if (empty($recipients)) {
            return;
        }

        Mail::to($recipients)->send(new FinalReportMail($report));
    }
}
