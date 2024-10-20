<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        // Example: Only admins or project managers can view all tasks
        return $user->hasRole('admin') || $user->hasRole('project_manager');
    }

    /**
     * Determine whether the user can view a specific task.
     */
    public function view(User $user, Task $task): bool
    {
        // Example: Allow task viewing if user is admin, project manager, or the task assignee
        return $user->hasRole('admin') || $user->hasRole('project_manager') || $user->id === $task->assigned_to;
    }

    /**
     * Determine whether the user can create a task.
     */
    public function create(User $user): bool
    {
        // Example: Only admins or project managers can create tasks
        return $user->hasRole('admin') || $user->hasRole('project_manager');
    }

    /**
     * Determine whether the user can update a task.
     */
    public function update(User $user, Task $task): bool
    {
        // Example: Only allow updating if the user is admin, project manager, or the task assignee
        return $user->hasRole('admin') || $user->hasRole('project_manager') || $user->id === $task->assigned_to;
    }

    /**
     * Determine whether the user can delete a task.
     */
    public function delete(User $user, Task $task): bool
    {
        // Example: Only allow deleting if the user is admin or project manager
        return $user->hasRole('admin') || $user->hasRole('project_manager');
    }

    /**
     * Determine whether the user can restore a soft-deleted task.
     */
    public function restore(User $user, Task $task): bool
    {
        // Example: Only allow restoring if the user is admin
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete a task.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        // Example: Only allow force-deleting if the user is admin
        return $user->hasRole('admin');
    }
}
