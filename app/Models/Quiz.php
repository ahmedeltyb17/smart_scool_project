<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'class_id',
        'title',
        'description',
        'quiz_date',
        'total_marks'
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
    public function results()
{
    return $this->hasMany(QuizResult::class);
}

}