<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['file_path', 'user_id'];

    // علاقة polymorphic مع Task
    public function attachable()
    {
        return $this->morphTo();
    }

    // علاقة belongsTo مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
