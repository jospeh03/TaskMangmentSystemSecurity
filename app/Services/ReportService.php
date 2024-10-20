<?php
namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    /**
     * Generate a daily report for completed tasks.
     *
     * @param array $filters
     * @return array
     */
    public function generateTaskReport(array $filters): array
    {
        $tasks = Task::query();

        if (isset($filters['type'])) {
            $tasks->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $tasks->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $tasks->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['due_date'])) {
            $tasks->whereDate('due_date', $filters['due_date']);
        }

        if (isset($filters['priority'])) {
            $tasks->where('priority', $filters['priority']);
        }

        // You can add more filters as needed

        return $tasks->get()->toArray();
    }

    /**
     * Generate and cache the daily completed task report.
     *
     * @return array
     */
    public function generateDailyCompletedTasksReport(): array
    {
        return Cache::remember('daily_completed_tasks_report', 86400, function () {
            return Task::where('status', 'Completed')
                ->whereDate('updated_at', now()->toDateString())
                ->get()
                ->toArray();
        });
    }
}
