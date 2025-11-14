<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_tasks', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->enum('status', ['pending','processing','completed','failed'])->default('pending');
            $table->string('final_video_url', 1024)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_tasks');
    }
};
