<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Confirmed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .card { background: #fff; border-radius: 12px; padding: 40px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 450px; }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { margin: 0 0 8px; font-size: 24px; color: #059669; }
        p { color: #6b7280; line-height: 1.5; }
        .recipients { margin-top: 12px; font-size: 14px; color: #374151; background: #f9fafb; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#10004;</div>
        <h1>Report Confirmed</h1>
        <p>
            Your weekly report ({{ $report['week_start'] }} &mdash; {{ $report['week_end'] }})
            has been sent to the following recipients:
        </p>
        <div class="recipients">
            {{ implode(', ', $report['recipients'] ?? []) }}
        </div>
    </div>
</body>
</html>
