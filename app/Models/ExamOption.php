<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamOption extends Model
{
    protected $connection = 'mysql';

    // match_text: used by 'matching' type — option_text=concept, match_text=correct definition
    protected $fillable = ['question_id', 'option_text', 'match_text', 'image', 'is_correct', 'order'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
}
