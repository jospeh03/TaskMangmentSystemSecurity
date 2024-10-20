<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskReportMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $report;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $report)
    {
        $this->user = $user;
        $this->report = $report;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Task Report')
                    ->view('emails.taskReport')
                    ->with([
                        'user' => $this->user,
                        'report' => $this->report,
                    ]);
    }
}
