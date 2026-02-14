<?php

namespace Yxx\WeeklyReport\Http\Controllers;

use Yxx\WeeklyReport\Services\ReportGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class ReportConfirmationController extends Controller
{
    public function confirm(Request $request, ReportGenerator $generator)
    {
        if (! $request->hasValidSignature()) {
            return response()->view('weekly-report::pages.invalid-link', [], 403);
        }

        $key = $request->query('key');
        $report = Cache::get($key);

        if (! $report || ($report['status'] ?? '') !== 'preview_sent') {
            return response()->view('weekly-report::pages.already-processed', [
                'status' => $report['status'] ?? 'expired',
            ]);
        }

        $report['status'] = 'confirmed';
        Cache::put($key, $report, now()->addMinutes(5));

        $generator->sendFinal($report);

        $report['status'] = 'sent';
        Cache::put($key, $report, now()->addMinutes(60));

        return response()->view('weekly-report::pages.confirmed', [
            'report' => $report,
        ]);
    }

    public function cancel(Request $request)
    {
        if (! $request->hasValidSignature()) {
            return response()->view('weekly-report::pages.invalid-link', [], 403);
        }

        $key = $request->query('key');
        $report = Cache::get($key);

        if (! $report || ($report['status'] ?? '') !== 'preview_sent') {
            return response()->view('weekly-report::pages.already-processed', [
                'status' => $report['status'] ?? 'expired',
            ]);
        }

        $report['status'] = 'cancelled';
        Cache::put($key, $report, now()->addMinutes(60));

        return response()->view('weekly-report::pages.cancelled', [
            'report' => $report,
        ]);
    }
}
