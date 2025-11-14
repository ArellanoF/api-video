<?php

namespace App\Jobs;

use App\Models\VideoTask;
use App\Services\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $taskId;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function handle(VideoProcessingService $service): void
    {
        $task = VideoTask::find($this->taskId);
        if (! $task) {
            Log::warning("Task {$this->taskId} not found.");
            return;
        }

        $result = $service->processTask($task);

        if ($result === false) {
            throw new \RuntimeException("Processing failed for task {$this->taskId}");
        }
    }
}
