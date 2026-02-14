# youyingxiang/weekly-report

[中文文档](README-ZH.md)

A Laravel package that generates weekly reports from git commits and GitHub issues, with an email preview and confirmation workflow.

## Installation

```bash
composer require youyingxiang/weekly-report
```

## Setup

### 1. Publish config

```bash
php artisan vendor:publish --tag=weekly-report-config
```

### 2. Environment variables

Add to your `.env`:

```env
# GitHub token (needs `repo` scope for private repos)
WEEKLY_REPORT_GITHUB_TOKEN=ghp_xxxxxxxxxxxx

# Repository config
WEEKLY_REPORT_REPO_PATH=/path/to/your/repo
WEEKLY_REPORT_REPO_OWNER=your-org
WEEKLY_REPORT_REPO_NAME=your-repo
WEEKLY_REPORT_REPO_BRANCH=main

# Filter commits by author (optional)
WEEKLY_REPORT_GIT_AUTHOR=you@example.com

# Mail config
WEEKLY_REPORT_PREVIEW_TO=you@example.com
WEEKLY_REPORT_RECIPIENTS=boss@example.com,team@example.com
WEEKLY_REPORT_SUBJECT="Weekly Report ({week_start} - {week_end})"
```

### 3. Multi-repo setup

Edit `config/weekly-report.php` to add multiple repositories:

```php
'repositories' => [
    [
        'path'   => '/path/to/repo-a',
        'owner'  => 'your-org',
        'repo'   => 'repo-a',
        'branch' => 'main',
    ],
    [
        'path'   => '/path/to/repo-b',
        'owner'  => 'your-org',
        'repo'   => 'repo-b',
    ],
],
```

## Usage

### Generate and preview

```bash
php artisan report:weekly
```

This will:
1. Scan git log for the current week's commits
2. Extract `#issue` references from commit messages
3. Fetch issue titles from GitHub API
4. Send a **preview email** to yourself (with Confirm / Cancel buttons)
5. Click **Confirm** to send the final report to all recipients

### Options

```bash
# Dry run - show data without sending emails
php artisan report:weekly --dry-run

# Generate for a previous week
php artisan report:weekly --weeks-ago=1
```

### Confirmation flow

- Click **Confirm** in the preview email to send the final report to all recipients
- Click **Cancel** to discard the report
- Links expire after 24 hours (configurable via `WEEKLY_REPORT_URL_EXPIRATION`)

## Customization

Publish and customize the email/page templates:

```bash
php artisan vendor:publish --tag=weekly-report-views
```

Templates will be copied to `resources/views/vendor/weekly-report/`.

## Architecture

```
src/
├── Commands/WeeklyReportCommand.php    # report:weekly command
├── Http/Controllers/                   # Signed URL confirm/cancel
├── Mail/                               # Preview and final mailables
├── Services/
│   ├── GitLogParser.php                # Git log parsing + issue extraction
│   ├── GitHubClient.php                # GitHub API client
│   └── ReportGenerator.php             # Orchestrator
└── WeeklyReportServiceProvider.php     # Auto-registered provider
```

## License

MIT
