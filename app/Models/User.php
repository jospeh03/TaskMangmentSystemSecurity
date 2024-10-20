<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasRoles;
    protected $fillable = ['name', 'email', 'password']; 
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // علاقة hasMany مع التعليقات
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Comment::class);
    }
    
    public function statusUpdate(){
        return $this->hasMany(TaskStatusUpdate::class);
    }
    // JWT methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
