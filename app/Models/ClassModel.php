<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    
    use HasFactory;
    protected $fillable = [
        'name',
        'teacher_id'
    ];
    protected $table = 'classes';


    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }
    public function schedules()
{
        return $this->hasMany(Schedule::class,'class_id');
}
    }

