<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $connection = 'sicore';

    protected $fillable = ['name', 'last_name_1', 'last_name_2', 'photo', 'cedula', 'nacionalidad', 'gender', 'qr'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->last_name_1} {$this->last_name_2}");
    }

    public function accessCodes()
    {
        return $this->hasMany(ExamAccessCode::class, 'student_id');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class, 'student_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_student_year', 'student_id', 'section_id')
            ->withPivot('year', 'sub_grupo');
    }
}
