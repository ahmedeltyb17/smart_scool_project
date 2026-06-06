<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;
    protected $fillable = [
    'title',
    'description',
    'class_id',
    'teacher_id',
    'due_date',
    'max_score',
    'type',
    'is_published',
    'attachment_path'
];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class);
    }
    public function teacher()
    {   
        return $this->belongsTo(Teacher::class);
    }

    public function grades()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
    public function submissions()
{
    return $this->hasMany(AssignmentSubmission::class);
}
}
