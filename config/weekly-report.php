<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GitHub Configuration
    |--------------------------------------------------------------------------
    |
    | Personal access token for the GitHub API. Requires `repo` scope
    | to read private repositories' issues.
    |
    */

    'github' => [
        'token' => env('WEEKLY_REPORT_GITHUB_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    |
    | List of repositories to scan. Each entry should have:
    |   - path:  Absolute local path to the git repository
    |   - owner: GitHub owner (user or org)
    |   - repo:  GitHub repository name
    |   - branch: (optional) Branch to scan, defaults to current branch
    |
    */

    'repositories' => [
        [
            'path'   => env('WEEKLY_REPORT_REPO_PATH', base_path()),
            'owner'  => env('WEEKLY_REPORT_REPO_OWNER'),
            'repo'   => env('WEEKLY_REPORT_REPO_NAME'),
            'branch' => env('WEEKLY_REPORT_REPO_BRANCH'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Git Author Filter
    |--------------------------------------------------------------------------
    |
    | Only include commits from this author email. Leave null to include
    | all authors.
    |
    */

    'git_author_email' => env('WEEKLY_REPORT_GIT_AUTHOR'),

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | preview_to:  Email address to receive the preview (typically yourself)
    | recipients:  Final report recipients (array of email addresses)
    | subject:     Email subject line (supports {week_start} and {week_end} placeholders)
    | from:        Sender address and name
    |
    */

    'mail' => [
        'preview_to' => env('WEEKLY_REPORT_PREVIEW_TO'),

        'recipients' => array_filter(
            explode(',', env('WEEKLY_REPORT_RECIPIENTS', ''))
        ),

        'subject' => env(
            'WEEKLY_REPORT_SUBJECT',
            'Weekly Report ({week_start} - {week_end})'
        ),

        'from' => [
            'address' => env('WEEKLY_REPORT_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
            'name'    => env('WEEKLY_REPORT_FROM_NAME', env('MAIL_FROM_NAME', 'Weekly Report')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URL Expiration
    |--------------------------------------------------------------------------
    |
    | How long the confirm/cancel signed URLs remain valid (in minutes).
    |
    */

    'signed_url_expiration' => env('WEEKLY_REPORT_URL_EXPIRATION', 1440), // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | URL prefix for the confirmation/cancellation routes.
    |
    */

    'route_prefix' => env('WEEKLY_REPORT_ROUTE_PREFIX', 'weekly-report'),
];
