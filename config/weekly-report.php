<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    |
    | List of local git repositories to scan.
    | owner/repo/branch are auto-detected from git remote, only path is needed.
    | Defaults to the current project (base_path()).
    |
    */

    'repositories' => [
        [
            'path' => env('WEEKLY_REPORT_REPO_PATH', base_path()),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | preview_to:  Email address to receive the preview (typically yourself)
    | recipients:  Final report recipients (comma-separated)
    | subject:     Supports {week_start} and {week_end} placeholders
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
    ],
];
