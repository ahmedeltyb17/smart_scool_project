<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parent_Student extends Model
{
        public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id');
    }
}
