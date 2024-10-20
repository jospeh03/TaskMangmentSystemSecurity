<?php
namespace App\Http\Controllers;

use App\Http\Requests\AssignUserRequest;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\ReassignRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    protected $TaskService;

    public function __construct(TaskService $TaskService)
    {
        $this->TaskService = $TaskService;
    }
    
    public function filter(FilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        try {
            $tasks = $this->TaskService->filterTasks($filters);
            return response()->json($tasks);
        } catch (\Exception $e) {
            Log::error('Task filtering failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to filter tasks.'], 500);
        }
    }
    
    public function store(StoreTaskRequest $request)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('project_manager')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validated();

        try {
            $task = $this->TaskService->store($validatedData);
            if (!$task) {
                throw new \Exception('Task creation returned null.');
            }
            return response()->json(['status' => 'success', 'message' => 'The task has been created successfully.', 'task' => $task], 201);
        } catch (\Exception $e) {
            Log::error('Task creation failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'The task could not be created.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function show(Task $task)
    {
        if (!auth()->user()->can('view tasks')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $task = $this->TaskService->show($task->id);
            return response()->json(['status' => 'success', 'message' => 'Task found successfully.', 'task' => $task]);
        } catch (\Exception $e) {
            Log::error('Task retrieval failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Task not found.'], 404);
        }
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        if (!auth()->user()->can('update tasks')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validatedData = $request->validated();
            $updatedTask = $this->TaskService->update($task, $validatedData);

            if (!$updatedTask) {
                return response()->json(['status' => 'error', 'message' => 'Sorry, the task could not be updated.'], 500);
            }

            return response()->json(['status' => 'success', 'message' => 'The task has been updated successfully.', 'task' => $updatedTask]);
        } catch (\Exception $e) {
            Log::error('Task update failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Task update failed.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function destroy(Task $task)
    {
        if (!auth()->user()->can('delete tasks')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $deleted = $this->TaskService->softDelete($task);

            if (!$deleted) {
                return response()->json(['status' => 'error', 'message' => 'Unable to delete the task.'], 500);
            }

            return response()->json(['status' => 'success', 'message' => 'The task has been soft-deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Task deletion failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to delete the task.'], 500);
        }
    }

   /**
     * Assign a task to a user.
     *
     * @param AssignUserRequest $request
     * @return JsonResponse
     */
    public function assign(AssignUserRequest $request): JsonResponse
    {
        // Validate the request
        $validatedData = $request->validated();

        // Call the service to assign the task
        $success = $this->TaskService->assignTask($validatedData['task_id'], $validatedData['assigned_to']);

        if ($success) {
            return response()->json([
                'status' => 'success',
                'message' => 'Task has been assigned successfully.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to assign task.'
        ], 500);
    }

    /**
     * Reassign a task to a new user.
     *
     * @param ReassignRequest $request
     * @return JsonResponse
     */
    public function reassign(ReassignRequest $request): JsonResponse
    {
        // Validate the request
        $validatedData = $request->validated();

        // Call the service to reassign the task
        $success = $this->TaskService->reassignTask($validatedData['task_id'], $validatedData['assigned_to']);

        if ($success) {
            return response()->json([
                'status' => 'success',
                'message' => 'Task has been reassigned successfully.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to reassign task.'
        ], 500);
    }

    public function updateStatus(UpdateStatusRequest $request, Task $task)
    {
        if (!auth()->user()->can('update status')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validatedData = $request->validated();
            $updatedTask = $this->TaskService->updateStatus($task, $validatedData);

            return response()->json(['message' => 'Task status updated successfully', 'task' => $updatedTask]);
        } catch (\Exception $e) {
            Log::error('Task status update failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to update task status.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function restore($id)
    {
        try {
            $restored = $this->TaskService->restore($id);

            if (!$restored) {
                return response()->json(['status' => 'error', 'message' => 'Unable to restore the task.'], 500);
            }

            return response()->json(['status' => 'success', 'message' => 'Task restored successfully.']);
        } catch (\Exception $e) {
            Log::error('Task restoration failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to restore task.', 'errors' => $e->getMessage()], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $forceDeleted = $this->TaskService->forceDelete($id);

            if (!$forceDeleted) {
                return response()->json(['status' => 'error', 'message' => 'Unable to permanently delete the task.'], 500);
            }

            return response()->json(['status' => 'success', 'message' => 'The task has been permanently deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Task force delete failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to permanently delete task.', 'errors' => $e->getMessage()], 500);
        }
    }


    public function addDependency(Task $task, Task $dependency)
    {
        $success = $this->TaskService->addDependency($task, $dependency);
    
        if ($success) {
            return response()->json([
                'status' => 'success',
                'message' => 'The task dependency has been added successfully.'
            ], 200);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'Unable to add dependency.'
        ], 500);
    }
    
    public function removeDependency(Task $task, Task $dependency)
    {
        $success = $this->TaskService->removeDependency($task, $dependency);
    
        if ($success) {
            return response()->json([
                'status' => 'success',
                'message' => 'The task dependency has been removed successfully.'
            ], 200);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'Unable to remove dependency.'
        ], 500);
    }
    




}
