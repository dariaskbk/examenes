<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $connection = 'sicore';

    protected $fillable = ['name', 'half'];

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'subject_user', 'subject_id', 'user_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'subject_id');
    }
}
