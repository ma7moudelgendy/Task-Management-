<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status')->default(TaskStatus::TODO->value);
            $table->string('priority')->default(TaskPriority::MEDIUM->value);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index('due_at');
            $table->index('parent_task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
