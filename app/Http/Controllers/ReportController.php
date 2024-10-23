<?php
namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\GenerateReportJob;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate a report based on provided filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    
    public function generateTaskReport(Request $request)
    {
        $user = auth()->user();  // The authenticated user requesting the report

        // Get filter options from the request
        $filters = $request->all();

        // Dispatch the report generation job
        GenerateTaskReportJob::dispatch($user, $filters);

        return response()->json([
            'status' => 'success',
            'message' => 'The report is being generated and will be sent to your email.'
        ], 200);
    }


    /**
     * Generate and retrieve the daily completed tasks report.
     *
     * @return JsonResponse
     */
    public function dailyCompletedTasksReport(): JsonResponse
    {
        $report = $this->reportService->generateDailyCompletedTasksReport();

        return response()->json(['report' => $report]);
    }
}
