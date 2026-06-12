<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'exam_id', 'type', 'question_text', 'image', 'audio', 'video',
        'media_type', 'points', 'order', 'grading_criteria', 'rubric',
    ];

    protected function casts(): array
    {
        return ['rubric' => 'array'];
    }

    /** Maximum points the rubric can yield = top level × number of criteria. */
    public function rubricMaxPoints(): float
    {
        $r = $this->rubric ?? null;
        if (!is_array($r) || empty($r['levels']) || empty($r['criteria'])) return 0.0;
        $topPoints = collect($r['levels'])->max(fn($l) => (float) ($l['points'] ?? 0));
        return $topPoints * count($r['criteria']);
    }

    const TYPES = [
        'single_choice'       => 'Selección Única',
        'multiple_select'     => 'Selección Múltiple',
        'true_false'          => 'Verdadero / Falso',
        'short_answer'        => 'Respuesta Corta',
        'matching'            => 'Emparejamiento',
        'ordering'            => 'Ordenamiento',
        'identification'      => 'Identificación',
        'completion'          => 'Completar',
        'restricted_response' => 'Resp. Restringida',
        'exercise'            => 'Ejercicio',
        'written_production'  => 'Prod. Escrita',
    ];

    const AUTO_GRADED = ['single_choice', 'multiple_choice', 'multiple_select', 'true_false', 'matching', 'ordering', 'identification', 'completion'];

    /** Types that need manual grading and may carry a rubric */
    const RUBRIC_TYPES = ['short_answer', 'restricted_response', 'exercise', 'written_production'];

    public function isAutoGraded(): bool
    {
        return in_array($this->type, self::AUTO_GRADED);
    }

    public function mediaIsMissing(): bool
    {
        return match ($this->media_type) {
            'image' => empty($this->image),
            'audio' => empty($this->audio),
            'video' => empty($this->video),
            default => false,
        };
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ExamOption::class, 'question_id')->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class, 'question_id');
    }
}
