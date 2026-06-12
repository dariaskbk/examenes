<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamShare extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'exam_id', 'from_user_id', 'to_user_id', 'status', 'message',
        'accepted_exam_id', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** Resolve users from the sicore connection. */
    public function getFromUserAttribute(): ?User
    {
        return User::find($this->from_user_id);
    }

    public function getToUserAttribute(): ?User
    {
        return User::find($this->to_user_id);
    }

    public function acceptedExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'accepted_exam_id');
    }
}
