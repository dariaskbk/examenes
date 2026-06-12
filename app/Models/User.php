<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $connection = 'sicore';

    protected $fillable = [
        'name', 'last_name_1', 'last_name_2', 'email', 'password',
        'url', 'phone', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->last_name_1} {$this->last_name_2}");
    }

    /**
     * Teacher's subjects for the currently active year (user_subject_year pivot).
     */
    public function subjects()
    {
        $activeYear = Year::where('status', 1)->orderBy('year', 'desc')->first();

        $q = $this->belongsToMany(Subject::class, 'user_subject_year', 'user_id', 'subject_id')
            ->withPivot('year_id');

        return $activeYear ? $q->wherePivot('year_id', $activeYear->id) : $q;
    }

    /**
     * Teacher's subjects for a specific year_id.
     */
    public function subjectsForYear(int $yearId)
    {
        return $this->belongsToMany(Subject::class, 'user_subject_year', 'user_id', 'subject_id')
            ->withPivot('year_id')
            ->wherePivot('year_id', $yearId);
    }

    /**
     * All sections the teacher is assigned to (section_user_year pivot).
     * Pivot uses `year` column (the year value as integer, not a FK).
     */
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_user_year', 'user_id', 'section_id')
            ->withPivot('year');
    }

    /**
     * Teacher's sections for a specific calendar year value (e.g. 2026).
     */
    public function sectionsForYear(int $yearValue)
    {
        return $this->belongsToMany(Section::class, 'section_user_year', 'user_id', 'section_id')
            ->withPivot('year')
            ->wherePivot('year', $yearValue);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'user_id');
    }
}
