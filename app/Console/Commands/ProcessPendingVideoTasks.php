<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\VideoTask;
use App\Jobs\ProcessVideoTaskJob;

class ProcessPendingVideoTasks extends Command
{
    protected $signature = 'videos:process-pending {--limit=2}';
    protected $description = 'Search for pending tasks';

    public function handle()
    {
        Log::info('Running videos:process-pending', ['time' => now()]);
        $limit = $this->option('limit');

        $tasks = DB::transaction(function () use ($limit) {
            $tasks = VideoTask::where('status', 'pending')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($tasks->isEmpty()) {
                Log::info('No pending tasks');
                return collect();
            }

            VideoTask::whereIn('id', $tasks->pluck('id'))
                ->update(['status' => 'processing', 'updated_at' => now()]);

            return $tasks;
        });

        foreach ($tasks as $task) {
            ProcessVideoTaskJob::dispatch($task->id);
        }

        return 0;
    }
}
