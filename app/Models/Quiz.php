<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'max_score'
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