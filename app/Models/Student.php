<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
    class Student extends Model
{
    use HasFactory;
    protected $fillable = ['user_id'];
    protected $table = 'students';
    protected static function booted()
{
    static::creating(function ($student) {
        $last = self::latest()->first();
        $number = $last ? intval(substr($last->student_id, 4)) + 1 : 1;

        $student->student_id = 'STD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    });
}
    public function user() 
    
    {
        return $this->belongsTo(User::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id');
         return $this->belongsTo(ParentModel::class, 'parent_id');
        }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_student', 'student_id', 'class_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }
}


