<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->unique(['teaching_assignment_id', 'day_of_week', 'starts_at'], 'schedule_slot_unique');
            $table->index(['day_of_week', 'starts_at']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('attachment_disk', 30)->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime', 150)->nullable();
            $table->unsignedInteger('attachment_size')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['teaching_assignment_id', 'status', 'published_at'], 'material_publication_index');
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('description');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->string('attachment_disk', 30)->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime', 150)->nullable();
            $table->unsignedInteger('attachment_size')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['teaching_assignment_id', 'status', 'published_at'], 'assignment_publication_index');
        });

        Schema::create('learning_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['teaching_assignment_id', 'status', 'published_at'], 'information_publication_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_information');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('schedules');
    }
};
