# yxx/weekly-report

[中文文档](README-ZH.md)

A Laravel package that generates weekly reports from git commits and GitHub issues, with an email preview and confirmation workflow.

## Installation

```bash
composer require yxx/weekly-report
```

## Setup

Add to your `.env`:

```env
WEEKLY_REPORT_PREVIEW_TO=you@example.com
WEEKLY_REPORT_RECIPIENTS=boss@example.com,team@example.com
```

That's it. Everything else is auto-detected:

| Item | Auto-detection |
|---|---|
| GitHub token | `gh auth token` (GitHub CLI) |
| Repo owner/name | `git remote get-url origin` |
| Repo path | Defaults to current project |
| Mail from | Laravel's `MAIL_FROM_*` config |

### Multi-repo setup (optional)

Edit `config/weekly-report.php`:

```php
'repositories' => [
    ['path' => '/path/to/repo-a'],
    ['path' => '/path/to/repo-b'],
],
```

## Usage

```bash
# Generate and send preview email
php artisan report:weekly

# Dry run - show data without sending emails
php artisan report:weekly --dry-run

# Generate for a previous week
php artisan report:weekly --weeks-ago=1
```

### How it works

1. Scans `git log` for the current week's commits
2. Extracts `#issue` references from commit messages
3. Fetches issue titles via GitHub API
4. Sends a **preview email** to you (with Confirm / Cancel signed URL buttons)
5. Click **Confirm** → final report sent to all recipients
6. Click **Cancel** → report discarded
7. Links expire after 24 hours

## Customization

```bash
php artisan vendor:publish --tag=weekly-report-views
```

## Architecture

```
src/
├── Commands/WeeklyReportCommand.php    # report:weekly command
├── Http/Controllers/                   # Signed URL confirm/cancel
├── Mail/                               # Preview and final mailables
├── Services/
│   ├── GitLogParser.php                # Git log parsing + issue extraction
│   ├── GitHubClient.php                # GitHub API client (auto token via gh CLI)
│   └── ReportGenerator.php             # Orchestrator
└── WeeklyReportServiceProvider.php     # Auto-registered provider
```

## License

MIT
