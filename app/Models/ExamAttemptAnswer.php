<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptAnswer extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['attempt_id', 'question_id', 'option_id', 'text_answer', 'is_correct', 'points_earned', 'feedback', 'voided', 'grading_choices'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'voided' => 'boolean', 'grading_choices' => 'array'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ExamOption::class, 'option_id');
    }
}
