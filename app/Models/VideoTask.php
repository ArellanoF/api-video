<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoTask extends Model
{
    protected $table = 'video_tasks';
    protected $guarded = [];
    protected $casts = [
        'payload' => 'array',
    ];
}
