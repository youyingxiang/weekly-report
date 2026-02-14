<?php

namespace Yxx\WeeklyReport\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FinalReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $report,
    ) {}

    public function build(): self
    {
        $weekStart = Carbon::parse($this->report['week_start'])->format('M d');
        $weekEnd = Carbon::parse($this->report['week_end'])->format('M d, Y');

        $subject = str_replace(
            ['{week_start}', '{week_end}'],
            [$weekStart, $weekEnd],
            config('weekly-report.mail.subject', 'Weekly Report')
        );

        return $this
            ->subject($subject)
            ->view('weekly-report::emails.final')
            ->with([
                'report'    => $this->report,
                'weekStart' => $weekStart,
                'weekEnd'   => $weekEnd,
            ]);
    }
}
