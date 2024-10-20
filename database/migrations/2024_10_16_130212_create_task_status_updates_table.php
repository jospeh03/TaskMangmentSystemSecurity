<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskStatusUpdatesTable extends Migration
{
    public function up()
    {
        Schema::create('task_status_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->enum('old_status', ['Open', 'In Progress', 'Completed', 'Blocked']);
            $table->enum('new_status', ['Open', 'In Progress', 'Completed', 'Blocked']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_status_updates');
    }
}
