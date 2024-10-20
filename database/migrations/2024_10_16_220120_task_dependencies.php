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
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();  // Primary key for the task dependencies table
            $table->foreignId('task_id')->constrained()->onDelete('cascade');  // The task that has a dependency
            $table->foreignId('dependency_id')->constrained('tasks')->onDelete('cascade');  // The task it depends on
            $table->timestamps();
        });
    
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
