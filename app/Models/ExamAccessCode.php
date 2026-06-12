<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAccessCode extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['exam_id', 'student_id', 'code', 'expires_at', 'extra_minutes'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'extra_minutes' => 'integer'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function getStudentAttribute(): ?Student
    {
        return Student::find($this->student_id);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'access_code_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function attemptsUsed(): int
    {
        return $this->attempts()->count();
    }

    public function hasRemainingAttempts(): bool
    {
        return $this->attemptsUsed() < $this->exam->max_attempts;
    }
}
