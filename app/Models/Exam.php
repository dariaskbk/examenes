<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exam extends Model
{
    protected $connection = 'mysql';

    /** Tipos de actividad disponibles con metadatos de UI */
    public const ACTIVITY_TYPES = [
        'exam'         => ['label' => 'Examen',        'icon' => 'bi-journal-text',      'color' => '#4F46E5', 'bg' => '#EEF2FF'],
        'quiz'         => ['label' => 'Quiz',           'icon' => 'bi-lightning-charge',  'color' => '#7C3AED', 'bg' => '#F5F3FF'],
        'assignment'   => ['label' => 'Tarea',          'icon' => 'bi-pencil-square',     'color' => '#059669', 'bg' => '#ECFDF5'],
        'project'      => ['label' => 'Proyecto',       'icon' => 'bi-kanban',            'color' => '#D97706', 'bg' => '#FFFBEB'],
        'lab'          => ['label' => 'Laboratorio',    'icon' => 'bi-thermometer-half',  'color' => '#0891B2', 'bg' => '#ECFEFF'],
        'presentation' => ['label' => 'Presentación',   'icon' => 'bi-easel2',            'color' => '#9F1239', 'bg' => '#FFF1F2'],
    ];

    /** Tipos que usan el sistema de preguntas */
    public const QUESTION_BASED_TYPES = ['exam', 'quiz', 'lab'];

    /** Mapeo de activity_type → tipo de evaluación de SICORE para sugerir componentes. */
    public const SICORE_TYPE_MAP = [
        'exam'         => 'TESTS',
        'quiz'         => 'TESTS',
        'assignment'   => 'HOMEWORK',
        'project'      => 'PROJECT',
        'presentation' => 'PROJECT',
        'lab'          => 'DAILY_WORK',
    ];

    /** Devuelve el tipo SICORE sugerido para un activity_type (o null). */
    public static function sicoreTypeFor(?string $activityType): ?string
    {
        return self::SICORE_TYPE_MAP[$activityType] ?? null;
    }

    protected $fillable = [
        'title', 'activity_type', 'description', 'instructions', 'subject_id', 'level_id', 'year_id', 'user_id',
        'duration_minutes', 'available_from', 'available_until', 'shuffle_questions',
        'shuffle_answers', 'max_attempts', 'show_results', 'show_correct_answers',
        'passing_score', 'status', 'questions_per_exam', 'proctoring',
        'proctoring_strict', 'proctoring_threshold', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
            'show_correct_answers' => 'boolean',
            'proctoring' => 'boolean',
            'proctoring_strict' => 'boolean',
            'proctoring_threshold' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeActive($query) { return $query->whereNull('archived_at'); }
    public function scopeArchived($query) { return $query->whereNotNull('archived_at'); }
    public function isArchived(): bool { return $this->archived_at !== null; }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('order');
    }

    public function accessCodes(): HasMany
    {
        return $this->hasMany(ExamAccessCode::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    // Cross-DB: resolve via separate query (sicore DB)
    public function getSubjectAttribute(): ?Subject
    {
        return $this->subject_id ? Subject::find($this->subject_id) : null;
    }

    public function getLevelAttribute(): ?Level
    {
        return $this->level_id ? Level::find($this->level_id) : null;
    }

    /** Pivot rows linking this exam to SICORE evaluation components (one per section). */
    public function evaluationComponents(): HasMany
    {
        return $this->hasMany(ExamEvaluationComponent::class);
    }

    /** IDs of the SICORE evaluation components linked to this exam. */
    public function linkedComponentIds(): array
    {
        return $this->evaluationComponents()->pluck('evaluation_component_id')->all();
    }

    /**
     * Collection of linked SICORE components with full context (subject, section,
     * rubro, %, max_points, period). Empty collection if none (formative).
     */
    public function getLinkedComponentsInfoAttribute(): \Illuminate\Support\Collection
    {
        $ids = $this->linkedComponentIds();
        if (empty($ids)) {
            return collect();
        }

        return \Illuminate\Support\Facades\DB::connection('sicore')
            ->table('evaluation_components as ec')
            ->join('evaluation_subject_year as esy', 'esy.id', '=', 'ec.evaluation_subject_year_id')
            ->join('evaluations as e', 'e.id', '=', 'esy.evaluation_id')
            ->join('subjects as s', 's.id', '=', 'esy.subject_id')
            ->leftJoin('periods as p', 'p.id', '=', 'ec.period_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'ec.section_id')
            ->whereIn('ec.id', $ids)
            ->select(
                'ec.id', 'ec.name', 'ec.value', 'ec.max_points', 'ec.group_type',
                'ec.section_id', 'ec.period_id', 'esy.subject_id', 'esy.year',
                's.name as subject_name', 'e.name as evaluation_name', 'e.percentage as evaluation_pct',
                'e.type as evaluation_type', 'p.name as period_name', 'sec.name as section_name'
            )
            ->orderBy('s.name')
            ->get();
    }

    public function getYearAttribute(): ?Year
    {
        return $this->year_id ? Year::find($this->year_id) : null;
    }

    public function getTeacherAttribute(): ?User
    {
        return User::find($this->user_id);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        $now = now();
        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }
        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }
        return true;
    }

    public function getTotalPointsAttribute(): float
    {
        return (float) $this->questions()->sum('points');
    }

    public function canBeDeleted(): bool
    {
        return $this->attempts()->whereIn('status', ['submitted', 'timed_out'])->count() === 0;
    }

    public function canBeEdited(): bool
    {
        return $this->attempts()->whereIn('status', ['submitted', 'timed_out'])->count() === 0;
    }

    /** ¿Este tipo de actividad usa preguntas? */
    public function isQuestionBased(): bool
    {
        return in_array($this->activity_type ?? 'exam', self::QUESTION_BASED_TYPES);
    }

    /** ¿Este tipo requiere entrega del estudiante sin preguntas? */
    public function isSubmissionBased(): bool
    {
        return !$this->isQuestionBased();
    }

    /** Metadatos de UI del tipo actual */
    public function activityMeta(): array
    {
        return self::ACTIVITY_TYPES[$this->activity_type ?? 'exam']
            ?? self::ACTIVITY_TYPES['exam'];
    }

    /** Etiqueta legible del tipo */
    public function activityLabel(): string
    {
        return $this->activityMeta()['label'];
    }
}
