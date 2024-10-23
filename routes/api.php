<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth Routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('jwt.auth');
Route::get('auth/me', [AuthController::class, 'me'])->middleware('jwt.auth');

// Group of routes protected by JWT auth middleware
Route::group(['middleware' => ['jwt.auth']], function () {
        Route::apiResource('tasks', TaskController::class);
        Route::get('tasks/filter', [TaskController::class, 'filter']);
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);
        Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);
        Route::put('tasks/{task}/reassign', [TaskController::class, 'reassign']);
        Route::post('tasks/{task}/restore', [TaskController::class, 'restore']);
        Route::delete('tasks/{task}/force-delete', [TaskController::class, 'forceDelete']);
        Route::post('tasks/{id}/Dependency/add', [TaskController::class, 'addDependency']);
        Route::post('tasks/{id}/Dependency/remove', [TaskController::class, 'removeDependency']);
    
    
  
    
    // Comment Routes
    Route::apiResource('tasks.comments',CommentController::class);
    
    // Attachment Routes
    Route::apiResource('tasks.attachments',AttachmentController::class);
    
    // User Management Routes
    //Route::apiResource('users', UserController::class);
    
    // Report Routes
    Route::get('reports/daily-tasks', [ReportController::class, 'dailyCompletedTasksReport']);
    Route::post('reports/generate', [ReportController::class, 'generateTaskReport']);
});

