<?php

namespace App\Models\ParentModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;

class ParentModel extends Model
{
    use HasFactory;
    use Notifiable;
    
    protected $fillable = ['user_id'];
    protected $table = 'parents';


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id');
    }
}

