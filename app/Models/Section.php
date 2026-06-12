<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $connection = 'sicore';

    protected $fillable = ['name', 'guia_id', 'year_id', 'level_id'];

    /**
     * Students enrolled in this section (section_student_year).
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'section_student_year', 'section_id', 'student_id')
            ->withPivot('year', 'sub_grupo', 'condition');
    }

    /**
     * Students enrolled for a specific year value (e.g. 2026).
     */
    public function studentsForYear(int $yearValue)
    {
        return $this->students()->wherePivot('year', $yearValue)->orderBy('students.name')->get();
    }

    /**
     * Teachers assigned to this section (section_user_year).
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'section_user_year', 'section_id', 'user_id')
            ->withPivot('year');
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }
}
