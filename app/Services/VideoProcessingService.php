<?php

namespace App\Services;

use App\Models\VideoTask;
use App\Models\PartialVideo;
use Illuminate\Support\Facades\Storage;

class VideoProcessingService
{
    public function processTask(VideoTask $task): bool
    {

        $dir = storage_path('app/public/videos/');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if ($task->status !== 'processing') {
            $task->update(['status' => 'processing']);
        }

        $payload = is_array($task->payload) ? $task->payload : json_decode($task->payload, true);

        // Create PartialVideos if they do not exist
        if (! PartialVideo::where('task_id', $task->id)->exists()) {
            foreach ($payload['images'] as $img) {
                PartialVideo::create([
                    'task_id' => $task->id,
                    'image_url' => $img['url'],
                    'transition' => $img['transition'],
                    'status' => 'pending',
                ]);
            }
        }

        // Create output folder if it doesn't exist
        $taskDir = storage_path('app/public/videos/' . $task->id);
        if (! is_dir($taskDir)) {
            mkdir($taskDir, 0775, true);
        }

        $partials = PartialVideo::where('task_id', $task->id)->orderBy('id')->get();

        foreach ($partials as $partial) {
            if ($partial->status === 'completed') continue;

            if (! $this->processPartial($partial, $task->id)) {
                $partial->update(['status' => 'failed']);
                $task->update(['status' => 'failed']);
                return false;
            }

            $partialPath = 'videos/' . $task->id . '/partial_' . $partial->id . '.mp4';
            $partial->update([
                'status' => 'completed',
                'video_path' => $partialPath,
            ]);
        }

        $final = $this->concatPartials($task->id);
        if ($final) {
            $task->update([
                'final_video_url' => $final,
                'status' => 'completed',
            ]);
            $partialsToDelete = PartialVideo::where('task_id', $task->id)->pluck('video_path')->toArray();
            Storage::disk('public')->delete($partialsToDelete);

            return true;
        }

        $task->update(['status' => 'failed']);
        return false;
    }

    public function processPartial(PartialVideo $partial, int $taskId): bool
    {
        $tmpImage = storage_path('app/tmp_img_' . $partial->id . '.jpg');

        try {
            $contents = @file_get_contents($partial->image_url);
            if (! $contents) return false;
            file_put_contents($tmpImage, $contents);
        } catch (\Exception $e) {
            return false;
        }

        $outDir = storage_path('app/public/videos/' . $taskId);
        if (! is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        $out = $outDir . '/partial_' . $partial->id . '.mp4';

        $filter = match ($partial->transition) {
            'zoom_in' => "zoompan=z='min(zoom+0.0015,1.5)':d=75",
            'zoom_out' => "zoompan=z='max(zoom-0.0015,1.0)':d=75",
            default => "crop=iw:ih:0:0,scale=1280:720",
        };

        $cmd = "ffmpeg -y -loop 1 -i " . escapeshellarg($tmpImage) .
            " -vf \"scale=1280:720,setsar=1,{$filter}\" -t 3 -r 25 -pix_fmt yuv420p " .
            escapeshellarg($out) . " 2>&1";

        exec($cmd, $output, $rc);
        @unlink($tmpImage);
        return ($rc === 0 && file_exists($out));
    }

    public function concatPartials(int $taskId): ?string
    {
        $partials = PartialVideo::where('task_id', $taskId)->orderBy('id')->get();
        if ($partials->isEmpty()) return null;

        $taskDir = storage_path('app/public/videos/' . $taskId);
        if (! is_dir($taskDir)) {
            mkdir($taskDir, 0775, true);
        }

        $listFile = $taskDir . '/list_' . $taskId . '.txt';
        $fh = fopen($listFile, 'w');
        foreach ($partials as $p) {
            $path = storage_path('app/public/' . $p->video_path);
            fwrite($fh, "file '" . $path . "'\n");
        }
        fclose($fh);

        $final = $taskDir . '/final_' . $taskId . '.mp4';
        $cmd = "ffmpeg -y -f concat -safe 0 -i " . escapeshellarg($listFile) . " -c copy " . escapeshellarg($final) . " 2>&1";
        exec($cmd, $out, $rc);
        @unlink($listFile);

        return ($rc === 0 && file_exists($final))
            ? ('videos/' . $taskId . '/final_' . $taskId . '.mp4')
            : null;
    }
}
