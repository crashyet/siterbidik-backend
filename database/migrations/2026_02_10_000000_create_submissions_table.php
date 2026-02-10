<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'bicara' or 'tugas_akhir'
            $table->string('assignment_id'); // Identifier for the specific assignment
            $table->string('file_path');
            $table->integer('score')->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('Selesai');
            $table->timestamps();
            
            // Allow only one submission per user per assignment
            $table->unique(['user_id', 'type', 'assignment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
