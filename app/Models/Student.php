<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClasseModel;
use App\Models\User;
use App\Models\ParentModel;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Attendance;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'user_id',
        'class_id',
        'grade',
        'student_code',
        'enrolled_at'
    ];

    // Auto generate student code
    protected static function booted()
    {
        static::creating(function ($student) {
            $last = self::latest()->first();
            $number = $last ? intval(substr($last->student_id, 4)) + 1 : 1;

            $student->student_id = 'STD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }

    // Student belongs to one class
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Student belongs to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Many parents (correct pivot)
    public function parents()
{
    return $this->belongsToMany(
        ParentModel::class,
        'parent_students',
        'student_id',
        'parent_id'
    );
}

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

  public function grades()
{
    return $this->hasMany(Grade::class, 'student_id');
}

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function results()
{
    return $this->hasMany(QuizResult::class);
}
}