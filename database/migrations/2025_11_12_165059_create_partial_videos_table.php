<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partial_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('video_tasks')->onDelete('cascade');
            $table->text('image_url');
            $table->enum('transition', ['pan','zoom_in','zoom_out']);
            $table->enum('status', ['pending','completed','failed'])->default('pending');
            $table->string('video_path', 1024)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partial_videos');
    }
};
