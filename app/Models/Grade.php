<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'subject',
        'score',
        'total',
        'exam_date',
    ];
    
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}

