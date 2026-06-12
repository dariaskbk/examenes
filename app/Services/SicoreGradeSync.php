<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Models\Year;
use Illuminate\Support\Facades\DB;

/**
 * Pushes ExamCore exam results into SICORE grades.
 *
 * Faithful replica of SICORE's App\Http\Controllers\EvaluationComponentController::storeGradeComponents()
 * (the authoritative consolidation logic). For each student we:
 *   1. write grade_components.grade = round(best% / 100 * component.max_points, 2)
 *   2. recompute the rubro grade (TESTS → Σ (grade/max_points)*value, clamped to evaluation.percentage)
 *   3. upsert grades.nota and link grade_components.grade_id
 *
 * Multi-section: each student is graded in the linked component of THEIR section
 * (resolved via the component's pre-created grade_components roster). Students that
 * don't belong to any linked component are skipped and reported.
 *
 * SOURCE OF TRUTH: keep this in sync with storeGradeComponents in SICORE.
 */
class SicoreGradeSync
{
    private $sicore;
    private array $yearCache = [];

    public function sync(Exam $exam): array
    {
        $this->sicore = DB::connection('sicore');
        $teacherId    = $exam->user_id;

        $componentIds = $exam->linkedComponentIds();
        if (empty($componentIds)) {
            return ['ok' => false, 'message' => 'Este examen no está vinculado a ningún componente de SICORE.'];
        }

        // Linked components with the context needed to grade
        $components = $this->sicore->table('evaluation_components as ec')
            ->join('evaluation_subject_year as esy', 'esy.id', '=', 'ec.evaluation_subject_year_id')
            ->join('evaluations as e', 'e.id', '=', 'esy.evaluation_id')
            ->whereIn('ec.id', $componentIds)
            ->select(
                'ec.id', 'ec.max_points', 'ec.value', 'ec.section_id', 'ec.group_type',
                'ec.period_id', 'ec.evaluation_subject_year_id',
                'esy.subject_id', 'esy.evaluation_id', 'esy.year',
                'e.percentage as eval_pct', 'e.type as eval_type'
            )
            ->get()
            ->keyBy('id');

        // Authoritative roster: student_id -> component_id (from SICORE pre-created grade_components)
        $studentToComponent = [];
        foreach ($this->sicore->table('grade_components')->whereIn('evaluation_component_id', $componentIds)
                     ->select('student_id', 'evaluation_component_id')->get() as $row) {
            $studentToComponent[$row->student_id] = $row->evaluation_component_id;
        }

        // Best finished attempt % per student
        $bestPct = [];
        foreach (ExamAttempt::where('exam_id', $exam->id)
                     ->whereIn('status', ['submitted', 'timed_out'])
                     ->get(['student_id', 'percentage']) as $a) {
            $p = (float) ($a->percentage ?? 0);
            if (!isset($bestPct[$a->student_id]) || $p > $bestPct[$a->student_id]) {
                $bestPct[$a->student_id] = $p;
            }
        }

        if (empty($bestPct)) {
            return ['ok' => false, 'message' => 'No hay intentos finalizados para sincronizar.'];
        }

        $synced = 0;
        $skipped = [];

        $this->sicore->transaction(function () use ($bestPct, $studentToComponent, $components, $teacherId, $exam, &$synced, &$skipped) {
            foreach ($bestPct as $studentId => $pct) {
                $compId = $studentToComponent[$studentId] ?? null;
                if (!$compId || !$components->has($compId)) {
                    $skipped[] = $studentId; // not in any linked component's roster
                    continue;
                }
                $component = $components->get($compId);

                // 1) Write the component's raw points (best% scaled to max_points)
                $gradePoints = round($pct / 100 * (float) $component->max_points, 2);
                $this->sicore->table('grade_components')
                    ->where('evaluation_component_id', $component->id)
                    ->where('student_id', $studentId)
                    ->update([
                        'grade'       => $gradePoints,
                        'observation' => 'Calificado por ExamCore — ' . $exam->title,
                        'updated_at'  => now(),
                    ]);

                // 2) Recompute the rubro grade + 3) link grade_id
                $this->consolidate($component, (int) $studentId, $teacherId);
                $synced++;
            }
        });

        // Names for the skipped report (limited)
        $skippedNames = [];
        if (!empty($skipped)) {
            $skippedNames = Student::whereIn('id', array_slice($skipped, 0, 8))
                ->get()->map(fn($s) => $s->full_name)->all();
        }

        return [
            'ok'            => true,
            'synced'        => $synced,
            'skipped'       => count($skipped),
            'skipped_names' => $skippedNames,
        ];
    }

    /**
     * Replica of storeGradeComponents consolidation. Branches by SICORE evaluation
     * type: TESTS/PROJECT use value-weighted formula; HOMEWORK/DAILY_WORK use a
     * pool-of-points formula. Identical math to SICORE.
     */
    private function consolidate($component, int $studentId, int $teacherId): void
    {
        // All the student's components of the same rubro + period
        $misComponentes = $this->sicore->table('grade_components as gc')
            ->join('evaluation_components as ec', 'ec.id', '=', 'gc.evaluation_component_id')
            ->where('gc.student_id', $studentId)
            ->where('ec.evaluation_subject_year_id', $component->evaluation_subject_year_id)
            ->where('ec.period_id', $component->period_id)
            ->select('gc.grade', 'ec.max_points', 'ec.value')
            ->get();

        $notaFinal = 0.0;

        if (in_array($component->eval_type, ['TESTS', 'PROJECT'], true)) {
            // Value-weighted: Σ (grade/max_points)*value
            foreach ($misComponentes as $mc) {
                $maxP = (float) $mc->max_points;
                $val  = (float) $mc->value;
                if ($maxP > 0 && $val > 0) {
                    $notaFinal += ((float) $mc->grade / $maxP) * $val;
                }
            }
        } else {
            // HOMEWORK / DAILY_WORK: pool of points × rubro percentage
            $totalObtenido = 0.0;
            $totalMaximo   = 0.0;
            foreach ($misComponentes as $mc) {
                $totalObtenido += (float) $mc->grade;
                $totalMaximo   += (float) $mc->max_points;
            }
            $notaFinal = $totalMaximo > 0
                ? ($totalObtenido / $totalMaximo) * (float) $component->eval_pct
                : 0.0;
        }

        $notaFinal = round(min(max($notaFinal, 0), (float) $component->eval_pct), 2);

        $yearId = $this->resolveYearId($component->year);

        // Upsert grades (key = year, student, subject, evaluation, period — no user_id, audit only)
        $key = [
            'year_id'       => $yearId,
            'student_id'    => $studentId,
            'subject_id'    => $component->subject_id,
            'evaluation_id' => $component->evaluation_id,
            'period_id'     => $component->period_id,
        ];

        $gradeId = $this->sicore->table('grades')->where($key)->value('id');
        if ($gradeId) {
            $this->sicore->table('grades')->where('id', $gradeId)
                ->update(['nota' => $notaFinal, 'user_id' => $teacherId, 'updated_at' => now()]);
        } else {
            $gradeId = $this->sicore->table('grades')->insertGetId($key + [
                'nota'       => $notaFinal,
                'user_id'    => $teacherId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Link the component grade to the consolidated grade row
        $this->sicore->table('grade_components')
            ->where('evaluation_component_id', $component->id)
            ->where('student_id', $studentId)
            ->update(['grade_id' => $gradeId, 'updated_at' => now()]);
    }

    private function resolveYearId(int $yearValue): ?int
    {
        if (!array_key_exists($yearValue, $this->yearCache)) {
            $this->yearCache[$yearValue] = optional(Year::where('year', $yearValue)->first())->id;
        }
        return $this->yearCache[$yearValue];
    }
}
