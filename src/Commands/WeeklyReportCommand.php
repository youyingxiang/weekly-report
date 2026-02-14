<?php

namespace Yxx\WeeklyReport\Commands;

use Yxx\WeeklyReport\Services\ReportGenerator;
use Illuminate\Console\Command;

class WeeklyReportCommand extends Command
{
    protected $signature = 'report:weekly
        {--dry-run : Only show the report data without sending emails}
        {--weeks-ago=0 : Number of weeks back to generate the report for}';

    protected $description = 'Generate a weekly report from git commits and GitHub issues';

    public function handle(ReportGenerator $generator): int
    {
        $weeksAgo = (int) $this->option('weeks-ago');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Generating weekly report" . ($weeksAgo > 0 ? " ({$weeksAgo} week(s) ago)" : ' (current week)') . '...');
        $this->newLine();

        try {
            $data = $generator->generate($weeksAgo);
        } catch (\RuntimeException $e) {
            $this->error("Failed to generate report: {$e->getMessage()}");
            return self::FAILURE;
        }

        $weekStart = $data['week_start']->format('Y-m-d');
        $weekEnd = $data['week_end']->format('Y-m-d');

        $this->info("Period: {$weekStart} ~ {$weekEnd}");
        $this->info('Repositories scanned: ' . implode(', ', $data['repositories']));
        $this->newLine();

        // Display commits
        $this->info("Commits ({$data['commits']->count()}):");
        if ($data['commits']->isEmpty()) {
            $this->warn('  No commits found in this period.');
        } else {
            foreach ($data['commits'] as $commit) {
                $repo = $commit['repo'] ?? '';
                $this->line("  [{$repo}] {$commit['hash']} {$commit['message']}");
            }
        }
        $this->newLine();

        // Display issues
        $this->info("Issues ({$data['issues']->count()}):");
        if ($data['issues']->isEmpty()) {
            $this->warn('  No issues referenced in commits.');
        } else {
            foreach ($data['issues'] as $issue) {
                $this->line("  #{$issue['number']} {$issue['title']} ({$issue['repo']})");
            }
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('Dry run mode - no emails sent.');
            return self::SUCCESS;
        }

        if ($data['issues']->isEmpty() && $data['commits']->isEmpty()) {
            $this->warn('Nothing to report. Skipping email.');
            return self::SUCCESS;
        }

        $previewTo = config('weekly-report.mail.preview_to');
        if (! $previewTo) {
            $this->error('No preview_to email configured. Set WEEKLY_REPORT_PREVIEW_TO in your .env');
            return self::FAILURE;
        }

        $this->info("Sending preview email to {$previewTo}...");
        $generator->sendPreview($data);
        $this->info('Preview sent! Check your email and click Confirm to send the final report.');

        return self::SUCCESS;
    }
}
