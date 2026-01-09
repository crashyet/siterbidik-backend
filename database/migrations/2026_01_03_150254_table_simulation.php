<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('video');
            $table->string('thumbnail')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('simulation_video_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simulation_video_id')
                ->constrained('simulation_videos')
                ->cascadeOnDelete();

            $table->integer('watched_duration')->default(0);
            $table->decimal('watch_percentage', 5, 2)->default(0);
            $table->boolean('is_counted')->default(false);

            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('simulation_video_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simulation_video_id')
                ->constrained('simulation_videos')
                ->cascadeOnDelete();

            $table->text('comment');
            $table->boolean('is_approved')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_video_comments');
        Schema::dropIfExists('simulation_video_views');
        Schema::dropIfExists('simulation_videos');
    }
};
