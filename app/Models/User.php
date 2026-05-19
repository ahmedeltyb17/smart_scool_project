<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ParentModel;


class User extends Authenticatable
{
    use HasFactory, Notifiable , HasApiTokens;
    
    protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'phone',
    'student_id',
    'address',
    'is_active',
];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function parent()
    {
        return $this->hasOne(ParentModel::class); // لو الموديل اسمه ParentModel بدل reserved word
    }
    
    public function parentProfile()
    {
        return $this->hasOne(ParentModel::class, 'user_id');
    }

    public function chatbotConversations()
    {
        return $this->hasMany(ChatbotConversation::class);
    }
}



