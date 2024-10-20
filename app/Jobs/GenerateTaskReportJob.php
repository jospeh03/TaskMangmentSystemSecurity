<?php
namespace App\Jobs;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskReportMail;

class GenerateTaskReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $filters;
    protected $reportService;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService)
    {
        // Generate the report based on the provided filters (daily or custom filters)
        $report = $reportService->generateTaskReport($this->filters);

        // Send the report via email
        Mail::to($this->user->email)->send(new TaskReportMail($this->user, $report));
    }
}
