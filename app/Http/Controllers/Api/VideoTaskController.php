<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VideoTask;
use App\Models\PartialVideo;
use App\Jobs\ProcessVideoTaskJob;
use Illuminate\Validation\ValidationException;

class VideoTaskController extends Controller
{
    public function store(Request $req)
    {

        try {
            $validated = $req->validate([
                'images' => 'required|array|min:1',
                'images.*.url' => 'required|url',
                'images.*.transition' => 'required|in:pan,zoom_in,zoom_out',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Invalid payload format',
                'details' => $e->errors(),
            ], 400);
        }

        $task = VideoTask::create([
            'payload' => $validated,
            'status' => 'pending',
        ]);

        foreach ($validated['images'] as $img) {
            PartialVideo::create([
                'task_id' => $task->id,
                'image_url' => $img['url'],
                'transition' => $img['transition'],
                'status' => 'pending',
            ]);
        }

        ProcessVideoTaskJob::dispatch($task->id);

        return response()->json(['task_id' => $task->id, 'status' => 'pending'], 201);
    }

    public function show($id)
    {
        $task = VideoTask::find($id);
        if (!$task) return response()->json(['error' => 'not found'], 404);

        $partials = PartialVideo::where('task_id', $id)->get();

        return response()->json([
            'task_id' => $task->id,
            'status' => $task->status,
            'partial_videos' => $partials
        ]);
    }

    public function final($id)
    {
        $task = VideoTask::find($id);
        if (! $task) return response()->json(['error' => 'not found'], 404);
        
        return response()->json([
            'task_id' => $task->id,
            'status' => $task->status,
            'final_video_url' => $task->final_video_url
        ]);
    }
}
