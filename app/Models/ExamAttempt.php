<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'exam_id', 'student_id', 'access_code_id', 'started_at', 'submitted_at',
        'score', 'max_score', 'percentage', 'status', 'ip_address', 'question_order',
        'focus_loss_count', 'cheat_flags', 'paused_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'paused_at' => 'datetime',
            'question_order' => 'array',
            'cheat_flags' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function getStudentAttribute(): ?Student
    {
        return Student::find($this->student_id);
    }

    public function accessCode(): BelongsTo
    {
        return $this->belongsTo(ExamAccessCode::class, 'access_code_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class, 'attempt_id');
    }

    /** Total minutes allowed for this attempt = exam duration + per-student extra time. */
    public function effectiveDurationMinutes(): int
    {
        $extra = $this->accessCode?->extra_minutes ?? 0;
        return $this->exam->duration_minutes + $extra;
    }

    /**
     * Close any in-progress attempt whose effective duration has already elapsed.
     * Idempotent: subsequent calls do nothing once everything is closed.
     * Returns the number of attempts that were closed by this call.
     */
    public static function closeTimedOutForExam(int $examId): int
    {
        $closed = 0;
        $attempts = self::where('exam_id', $examId)
            ->where('status', 'in_progress')
            ->get();

        foreach ($attempts as $a) {
            if ($a->isTimedOut()) {
                $a->gradeAndSubmit(true);
                $closed++;
            }
        }
        return $closed;
    }

    public function isTimedOut(): bool
    {
        if (!$this->started_at || $this->status !== 'in_progress') {
            return false;
        }
        return now()->gt($this->started_at->addMinutes($this->effectiveDurationMinutes()));
    }

    public function getRemainingSecondsAttribute(): int
    {
        if ($this->status !== 'in_progress' || !$this->started_at) {
            return 0;
        }
        $endTime = $this->started_at->addMinutes($this->effectiveDurationMinutes());
        return max(0, (int) now()->diffInSeconds($endTime, false));
    }

    public function gradeAndSubmit(bool $timedOut = false): void
    {
        $totalPoints  = 0;
        $earnedPoints = 0;

        $this->load('answers');

        foreach ($this->answers as $answer) {
            $question = ExamQuestion::with('options')->find($answer->question_id);
            if (!$question) continue;

            $totalPoints += $question->points;

            // Voided by teacher (anti-cheat penalty): scores 0, max still counts
            if (!empty($answer->voided)) {
                $answer->points_earned = 0;
                $answer->is_correct    = false;
                $answer->save();
                continue;
            }

            $earned = match ($question->type) {
                'single_choice', 'multiple_choice', 'true_false' => $this->gradeSingleChoice($answer, $question),
                'multiple_select'                                 => $this->gradeMultipleSelect($answer, $question),
                'matching'                                        => $this->gradeMatching($answer, $question),
                'ordering'                                        => $this->gradeOrdering($answer, $question),
                'identification'                                  => $this->gradeIdentification($answer, $question),
                'completion'                                      => $this->gradeCompletion($answer, $question),
                default                                           => null, // manual grading
            };

            if ($earned !== null) {
                $answer->points_earned = $earned;
                $answer->is_correct    = $earned >= $question->points;
                $answer->save();
                $earnedPoints += $earned;
            }
        }

        $this->score        = round($earnedPoints, 2);
        $this->max_score    = $totalPoints;
        $this->percentage   = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $this->status       = $timedOut ? 'timed_out' : 'submitted';
        $this->submitted_at = now();
        $this->save();
    }

    private function gradeSingleChoice(ExamAttemptAnswer $answer, ExamQuestion $question): float
    {
        if (!$answer->option_id) return 0;
        $option = $question->options->firstWhere('id', $answer->option_id);
        return ($option && $option->is_correct) ? (float) $question->points : 0;
    }

    private function gradeMultipleSelect(ExamAttemptAnswer $answer, ExamQuestion $question): float
    {
        $selected = json_decode($answer->text_answer ?? '[]', true);
        if (!is_array($selected) || empty($selected)) return 0;

        $correctIds  = $question->options->where('is_correct', true)->pluck('id')->toArray();
        $totalCorrect = count($correctIds);
        if ($totalCorrect === 0) return 0;

        $correctSelected = count(array_intersect($selected, $correctIds));
        $wrongSelected   = count(array_diff($selected, $correctIds));

        $ratio = max(0, ($correctSelected - $wrongSelected) / $totalCorrect);
        return round($ratio * $question->points, 2);
    }

    private function gradeMatching(ExamAttemptAnswer $answer, ExamQuestion $question): float
    {
        $studentMap = json_decode($answer->text_answer ?? '{}', true);
        if (!is_array($studentMap) || empty($studentMap)) return 0;

        $pairs = $question->options;
        $totalPairs = $pairs->count();
        if ($totalPairs === 0) return 0;

        $correctCount = 0;
        foreach ($pairs as $pair) {
            $submitted = trim($studentMap[(string) $pair->id] ?? '');
            if (mb_strtolower($submitted) === mb_strtolower(trim($pair->match_text ?? ''))) {
                $correctCount++;
            }
        }

        return round(($correctCount / $totalPairs) * $question->points, 2);
    }

    private function gradeOrdering(ExamAttemptAnswer $answer, ExamQuestion $question): float
    {
        $studentOrder = json_decode($answer->text_answer ?? '[]', true);
        if (!is_array($studentOrder) || empty($studentOrder)) return 0;

        $items = $question->options->keyBy('id');
        $total = $items->count();
        if ($total === 0) return 0;

        $correctCount = 0;
        foreach ($studentOrder as $position => $optionId) {
            $item = $items->get($optionId);
            if ($item && $item->order === ($position + 1)) {
                $correctCount++;
            }
        }

        return round(($correctCount / $total) * $question->points, 2);
    }

    private function gradeIdentification(ExamAttemptAnswer $answer, ExamQuestion $question): float
    {
        // Student answer: JSON {"A": "respuesta", "B": "respuesta", ...}
        $studentMap = json_decode($answer->text_answer ?? '{}', true);
        if (!is_array($studentMap) || empty($studentMap)) return 0;

        $parts = $question->options; // option_text = label, match_text = correct answer
        $total = $parts->count();
        if ($total === 0) return 0;

        $correctCount = 0;
        foreach ($parts as $part) {
            $submitted = mb_strtolower(trim($studentMap[$part->option_text] ?? ''));
            $expected  = mb_strtolower(trim($part->match_text ?? ''));
            if ($submitted !== '' && $submitted === $expected) {
                $correctCount++;
            }
        }

        return round(($correctCount / $total) * $question->points, 2);
    }

    private function gradeCompletion(ExamAttemptAnswer $answer, ExamQuestion $question): float
    {
        // Student answer: JSON {"1": "word", "2": "word2"} — keyed by blank order number
        $studentMap = json_decode($answer->text_answer ?? '{}', true);
        if (!is_array($studentMap) || empty($studentMap)) return 0;

        // Correct answers: is_correct=true, order = blank number (1,2,3…)
        $correctOpts = $question->options->where('is_correct', true)->sortBy('order');
        $total       = $correctOpts->count();
        if ($total === 0) return 0;

        $correctCount = 0;
        foreach ($correctOpts as $opt) {
            $placed   = mb_strtolower(trim($studentMap[(string) $opt->order] ?? ''));
            $expected = mb_strtolower(trim($opt->option_text));
            if ($placed !== '' && $placed === $expected) {
                $correctCount++;
            }
        }

        return round(($correctCount / $total) * $question->points, 2);
    }
}
