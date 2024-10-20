<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 
        'description', 
        'type', 
        'status', 
        'priority', 
        'due_date', 
        'assigned_to'
    ];

    // علاقة belongsTo مع المستخدم
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // علاقة polymorphic للتعليقات
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // علاقة polymorphic للمرفقات
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // علاقة hasMany لتتبع التغييرات في حالة المهام
    public function statusUpdates()
    {
        return $this->hasMany(TaskStatusUpdate::class);
    }

    // علاقة تبعية المهام
    public function dependencies()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'dependency_id');
    }

    // علاقة المهام التابعة لهذه المهمة
    public function dependentOn()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'dependency_id', 'task_id');
    }
}
