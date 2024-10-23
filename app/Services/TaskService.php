<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Task;

class TaskService
{
    public function index()
    {
        try {
            return Cache::remember('Tasks', 3600, function () {
                return DB::select('SELECT * FROM tasks');
            });
        } catch (\Exception $e) {
            Log::error('Task retrieval failed: ' . $e->getMessage());
            return null;
        }
    }

    public function store($data)
    {
        try {
            $query = "INSERT INTO tasks (title, description, type, status, priority, due_date, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?)";
            DB::insert($query, [
                $data['title'], 
                $data['description'], 
                $data['type'], 
                $data['status'], 
                $data['priority'], 
                $data['due_date'], 
                $data['assigned_to']
            ]);

            Cache::forget('Tasks');
            return DB::getPdo()->lastInsertId(); // Return the new task ID
        } catch (\Exception $e) {
            Log::error('Task creation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function show($id)
    {
        try {
            return $this->getTaskById($id);
        } catch (\Exception $e) {
            Log::error('Task retrieval failed: ' . $e->getMessage());
            return null;
        }
    }

    public function update(Task $task, array $data): ?Task
    {
        try {
            $query = "UPDATE tasks SET title = ?, description = ?, type = ?, status = ?, priority = ?, due_date = ? WHERE id = ?";
            DB::update($query, [
                $data['title'], 
                $data['description'], 
                $data['type'], 
                $data['status'], 
                $data['priority'], 
                $data['due_date'], 
                $task->id
            ]);

            $this->invalidateCache($task->id);
            return $this->getTaskById($task->id);
        } catch (\Exception $e) {
            Log::error('Task update failed: ' . $e->getMessage());
            return null;
        }
    }
    public function updateStatus(Task $task, array $data): ?Task
    {
        DB::beginTransaction(); // Start a transaction

        try {
            // Check if the task status is being updated to 'Completed'
            if (isset($data['new_status']) && $data['new_status'] === 'Completed') {
                // Automatically unblock tasks that depend on this task
                $this->unblockDependentTasks($task);
            }
            if ($task->status !== $data['new_status']) {
                $this->logStatusChange($task, $data['new_status']);
            }

            
            DB::update("UPDATE tasks SET status = ? WHERE id = ?", [$data['new_status'], $task->id]);
            $this->invalidateCache($task->id);
            $this->cacheTask($task);

            DB::commit(); // Commit the transaction

            return $task;
        } catch (\Exception $e) {
            DB::rollBack(); // Roll back if there's an error
            Log::error($e);
            return null;
        }
    }

    public function filterTasks(array $filters)
    {
        try {
            $query = "SELECT * FROM tasks WHERE 1=1";
            $bindings = [];

            if (!empty($filters['type'])) {
                $query .= " AND type = ?";
                $bindings[] = $filters['type'];
            }

            if (!empty($filters['status'])) {
                $query .= " AND status = ?";
                $bindings[] = $filters['status'];
            }

            if (!empty($filters['assigned_to'])) {
                $query .= " AND assigned_to = ?";
                $bindings[] = $filters['assigned_to'];
            }

            if (!empty($filters['due_date'])) {
                $query .= " AND due_date = ?";
                $bindings[] = $filters['due_date'];
            }

            if (!empty($filters['priority'])) {
                $query .= " AND priority = ?";
                $bindings[] = $filters['priority'];
            }

            return DB::select($query, $bindings);
        } catch (\Exception $e) {
            Log::error('Task filtering failed: ' . $e->getMessage());
            return null;
        }
    }

    public function softDelete(Task $task): bool
    {
        try {
            DB::update("UPDATE tasks SET deleted_at = NOW() WHERE id = ?", [$task->id]);
            Cache::forget('task_' . $task->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Task soft delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function forceDelete(Task $task): bool
    {
        try {
            DB::delete("DELETE FROM tasks WHERE id = ?", [$task->id]);
            Cache::forget('task_' . $task->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Task force delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function restore($id): bool
    {
        try {
            DB::update("UPDATE tasks SET deleted_at = NULL WHERE id = ?", [$id]);
            $this->cacheTask(Task::find($id));
            return true;
        } catch (\Exception $e) {
            Log::error('Task restoration failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function logStatusChange(Task $task, string $newStatus): void
    {
        $query = "INSERT INTO task_status_updates (task_id, old_status, new_status) VALUES (?, ?, ?)";
        DB::insert($query, [$task->id, $task->status, $newStatus]);
    }

    public function cacheTask(Task $task): void
    {
        Cache::put('task_' . $task->id, $task, 3600);  // Cache task for 1 hour
    }

    public function invalidateCache(int $taskId): void
    {
        Cache::forget('task_' . $taskId);
    }

    public function getTaskById(int $taskId): ?Task
    {
        return Cache::remember('task_' . $taskId, 3600, function () use ($taskId) {
            return Task::find($taskId);
        });
    }
    public function assignTask(int $taskId, int $assignedTo): bool
    {
        try {
            // Find the task using raw SQL
            $task = DB::select('SELECT * FROM tasks WHERE id = ?', [$taskId])[0];

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            // Perform the assignment using raw SQL
            DB::update('UPDATE tasks SET assigned_to = ? WHERE id = ?', [$assignedTo, $taskId]);

            // Invalidate the cache for this task
            Cache::forget("task_{$taskId}");

            // Optionally log the assignment action
            Log::info("Task {$taskId} has been assigned to user {$assignedTo}.");

            return true;
        } catch (\Exception $e) {
            Log::error('Error assigning task: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reassign a task to a new user.
     *
     * @param int $taskId
     * @param int $newAssignedTo
     * @return bool
     */
    public function reassignTask(int $taskId, int $newAssignedTo): bool
    {
        try {
            // Find the task using raw SQL
            $task = DB::select('SELECT * FROM tasks WHERE id = ?', [$taskId])[0];

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            // Reassign the task to the new user using raw SQL
            DB::update('UPDATE tasks SET assigned_to = ? WHERE id = ?', [$newAssignedTo, $taskId]);

            // Invalidate the cache for this task
            Cache::forget("task_{$taskId}");

            // Optionally log the reassignment action
            Log::info("Task {$taskId} has been reassigned to user {$newAssignedTo}.");

            return true;
        } catch (\Exception $e) {
            Log::error('Error reassigning task: ' . $e->getMessage());
            return false;
        }
    }
    public function assign(int $taskId, int $assignedTo): bool
    {
        try {
            // Find the task using raw SQL
            $task = DB::select('SELECT * FROM tasks WHERE id = ?', [$taskId])[0];

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            // Perform the assignment using raw SQL
            DB::update('UPDATE tasks SET assigned_to = ? WHERE id = ?', [$assignedTo, $taskId]);

            // Invalidate the cache for this task
            Cache::forget("task_{$taskId}");

            // Optionally log the assignment action
            Log::info("Task {$taskId} has been assigned to user {$assignedTo}.");

            return true;
        } catch (\Exception $e) {
            Log::error('Error assigning task: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reassign a task to a new user.
     *
     * @param int $taskId
     * @param int $newAssignedTo
     * @return bool
     */
    public function reassign(int $taskId, int $newAssignedTo): bool
    {
        try {
            // Find the task using raw SQL
            $task = DB::select('SELECT * FROM tasks WHERE id = ?', [$taskId])[0];

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            // Reassign the task to the new user using raw SQL
            DB::update('UPDATE tasks SET assigned_to = ? WHERE id = ?', [$newAssignedTo, $taskId]);

            // Invalidate the cache for this task
            Cache::forget("task_{$taskId}");

            // Optionally log the reassignment action
            Log::info("Task {$taskId} has been reassigned to user {$newAssignedTo}.");

            return true;
        } catch (\Exception $e) {
            Log::error('Error reassigning task: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Check if a task has incomplete dependencies and block it if necessary.
     */
    protected function blockIfDependenciesIncomplete(Task $task)
    {
        // Get task dependencies
        $incompleteDependencies = $task->dependencies()->where('status', '!=', 'Completed')->exists();

        if ($incompleteDependencies) {
            // If there are incomplete dependencies, block the task
            $task->update(['status' => 'Blocked']);
        }
    }

    /**
     * Automatically unblock dependent tasks when this task is completed.
     */
    protected function unblockDependentTasks(Task $task)
    {
        // Get all tasks that depend on this task
        $dependentTasks = $task->dependentOn;

        foreach ($dependentTasks as $dependentTask) {
            // Check if all dependencies for the dependent task are completed
            $allDependenciesCompleted = $dependentTask->dependencies->every(function ($dependency) {
                return $dependency->status === 'Completed';
            });

            // If all dependencies are completed, unblock the dependent task
            if ($allDependenciesCompleted && $dependentTask->status === 'Blocked') {
                $dependentTask->update(['status' => 'Open']);
                $this->invalidateCache($dependentTask->id); // Invalidate cache for the dependent task
            }
        }
    }

    /**
     * Add a dependency for a task.
     */
    public function addDependency(Task $task, Task $dependency): bool
    {
        try {
            // Add the dependency using raw SQL
            DB::insert('INSERT INTO task_dependencies (task_id, dependency_id) VALUES (?, ?)', [$task->id, $dependency->id]);

            // Check if the task needs to be blocked due to incomplete dependencies
            $this->blockIfDependenciesIncomplete($task);

            return true;
        } catch (\Exception $e) {
            Log::error('Error adding dependency: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a dependency for a task.
     */
    public function removeDependency(Task $task, Task $dependency): bool
    {
        try {
            // Remove the dependency using raw SQL
            DB::delete('DELETE FROM task_dependencies WHERE task_id = ? AND dependency_id = ?', [$task->id, $dependency->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error removing dependency: ' . $e->getMessage());
            return false;
        }
    }

}