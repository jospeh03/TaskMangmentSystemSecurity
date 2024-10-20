<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GenerateTaskReportJob;
use App\Models\User;

class SendDailyTaskReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:send-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily task reports to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve all users who should receive the daily report, e.g. Admins, Project Managers
        $users = User::role(['admin', 'project_manager'])->get(); // Assumes you're using Spatie Roles

        foreach ($users as $user) {
            // Define the filter for a daily report (tasks updated/completed today)
            $filters = ['daily' => true];

            // Dispatch the report generation job for each user
            GenerateTaskReportJob::dispatch($user, $filters);
        }

        // Inform the console that the command was successful
        $this->info('Daily task reports have been queued for sending.');
    }
}

