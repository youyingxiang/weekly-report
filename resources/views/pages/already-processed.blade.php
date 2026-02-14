<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Processed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .card { background: #fff; border-radius: 12px; padding: 40px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 450px; }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { margin: 0 0 8px; font-size: 24px; color: #6b7280; }
        p { color: #6b7280; line-height: 1.5; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: 600; font-size: 14px; margin-top: 8px; }
        .status-sent { background: #d1fae5; color: #059669; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .status-confirmed { background: #dbeafe; color: #2563eb; }
        .status-expired { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#8505;</div>
        <h1>Already Processed</h1>
        <p>This report has already been processed.</p>
        <span class="status status-{{ $status }}">
            {{ ucfirst(str_replace('_', ' ', $status)) }}
        </span>
    </div>
</body>
</html>
