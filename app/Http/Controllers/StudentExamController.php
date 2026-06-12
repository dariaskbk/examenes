<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAccessCode;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamOption;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class StudentExamController extends Controller
{
    // Show code entry page
    public function showCodeEntry()
    {
        return view('student.code-entry');
    }

    // Verify code and show student info
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ], ['code.required' => 'Ingrese su código de acceso.']);

        $code = strtoupper(str_replace(' ', '', $request->code));

        $accessCode = ExamAccessCode::with(['exam'])
            ->where('code', $code)
            ->first();

        if (!$accessCode) {
            return back()->withErrors(['code' => 'Código inválido. Verifique e intente nuevamente.']);
        }

        $exam = $accessCode->exam;

        // Check for an active in-progress attempt FIRST so a student who closed
        // the window can always resume — even with max_attempts = 1, and even if
        // the availability window has since changed (the per-attempt timer rules).
        $activeAttempt = ExamAttempt::where('access_code_id', $accessCode->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeAttempt && $activeAttempt->isTimedOut()) {
            $activeAttempt->gradeAndSubmit(true);
            $activeAttempt = null;
        }

        // Availability and attempts limit only gate STARTING a new attempt.
        if (!$activeAttempt) {
            if (!$exam->isAvailable()) {
                return back()->withErrors(['code' => 'Este examen no está disponible en este momento.']);
            }
            if (!$accessCode->hasRemainingAttempts()) {
                return back()->withErrors(['code' => 'Ha alcanzado el número máximo de intentos para este examen.']);
            }
        }

        return view('student.confirm', compact('accessCode', 'exam', 'activeAttempt'));
    }

    // Start or resume exam
    public function startExam(Request $request, string $code)
    {
        $accessCode = ExamAccessCode::with(['exam.questions.options'])
            ->where('code', strtoupper($code))
            ->firstOrFail();

        $exam = $accessCode->exam;

        // Resume an existing in-progress attempt first — always allowed, so a
        // student who closed the window can finish even if availability changed.
        $attempt = ExamAttempt::where('access_code_id', $accessCode->id)
            ->where('status', 'in_progress')
            ->first();

        if ($attempt) {
            if ($attempt->isTimedOut()) {
                $attempt->gradeAndSubmit(true);
                return redirect()->route('student.results', $code);
            }
            return redirect()->route('student.exam', $code);
        }

        // Starting a NEW attempt: enforce availability + attempts limit.
        if (!$exam->isAvailable()) {
            return redirect()->route('student.entry')->withErrors(['code' => 'El examen ya no está disponible.']);
        }
        if (!$accessCode->hasRemainingAttempts()) {
            return redirect()->route('student.entry')->withErrors(['code' => 'No tiene más intentos disponibles.']);
        }

        // Get questions (possibly shuffled and limited)
        $questions = $exam->questions;

        if ($exam->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        if ($exam->questions_per_exam && $questions->count() > $exam->questions_per_exam) {
            $questions = $questions->take($exam->questions_per_exam);
        }

        $questionOrder = $questions->pluck('id')->toArray();

        ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $accessCode->student_id,
            'access_code_id' => $accessCode->id,
            'started_at' => now(),
            'status' => 'in_progress',
            'ip_address' => $request->ip(),
            'question_order' => $questionOrder,
        ]);

        return redirect()->route('student.exam', $code);
    }

    // Resume in-progress exam (GET — safe to refresh)
    public function resumeExam(string $code)
    {
        $accessCode = ExamAccessCode::with(['exam'])
            ->where('code', strtoupper($code))
            ->firstOrFail();

        $exam = $accessCode->exam;

        $attempt = ExamAttempt::where('access_code_id', $accessCode->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt) {
            return redirect()->route('student.entry')
                ->withErrors(['code' => 'No hay un examen activo para este código.']);
        }

        if ($attempt->isTimedOut()) {
            $attempt->gradeAndSubmit(true);
            return redirect()->route('student.results', $code);
        }

        return $this->showExamPage($attempt, $accessCode);
    }

    protected function showExamPage(ExamAttempt $attempt, ExamAccessCode $accessCode)
    {
        $exam = $attempt->exam;
        $questionIds = $attempt->question_order ?? $exam->questions()->pluck('id')->toArray();
        $questions = ExamQuestion::with(['options' => function ($q) use ($exam) {
            if ($exam->shuffle_answers) {
                $q->inRandomOrder();
            } else {
                $q->orderBy('order');
            }
        }])->whereIn('id', $questionIds)
          ->orderByRaw('FIELD(id, ' . implode(',', $questionIds) . ')')
          ->get();

        $existingAnswers = $attempt->answers()->pluck('option_id', 'question_id')->toArray();
        $textAnswers = $attempt->answers()->whereNotNull('text_answer')->pluck('text_answer', 'question_id')->toArray();

        // Extra context for the top bar
        $institution = DB::connection('sicore')
            ->table('institution_settings')
            ->orderByDesc('id')
            ->value('name') ?? '';

        $subject = $exam->subject;

        $student     = $accessCode->student;
        $sectionName = null;
        if ($student) {
            $sectionName = DB::connection('sicore')
                ->table('section_student_year as ssy')
                ->join('sections', 'sections.id', '=', 'ssy.section_id')
                ->where('ssy.student_id', $student->id)
                ->orderByDesc('ssy.year')
                ->value('sections.name');
        }

        return view('student.exam', compact(
            'exam', 'attempt', 'questions', 'existingAnswers', 'textAnswers',
            'accessCode', 'institution', 'subject', 'sectionName'
        ));
    }

    // Auto-save answer
    public function saveAnswer(Request $request, int $attemptId)
    {
        $attempt = ExamAttempt::findOrFail($attemptId);

        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'El examen ya fue enviado.'], 422);
        }

        if ($attempt->isTimedOut()) {
            $attempt->gradeAndSubmit(true);
            return response()->json(['redirect' => route('student.results', $attempt->accessCode->code)]);
        }

        $request->validate([
            'question_id' => 'required|exists:exam_questions,id',
            'option_id' => 'nullable|exists:exam_options,id',
            'text_answer' => 'nullable|string|max:50000',
        ]);

        ExamAttemptAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $request->question_id],
            [
                'option_id' => $request->option_id,
                'text_answer' => $request->text_answer,
            ]
        );

        return response()->json(['saved' => true]);
    }

    // Log an anti-cheat incident (tab/focus loss, fullscreen exit, copy/paste, etc.)
    public function logIncident(Request $request, int $attemptId)
    {
        $attempt = ExamAttempt::find($attemptId);

        // Fail silently — never disrupt the student's exam over telemetry
        if (!$attempt || $attempt->status !== 'in_progress') {
            return response()->json(['ok' => false]);
        }

        $allowedTypes = ['screen_leave', 'fullscreen_exit', 'copy', 'paste', 'contextmenu'];
        $type = $request->input('type');
        if (!in_array($type, $allowedTypes, true)) {
            return response()->json(['ok' => false]);
        }

        $questionId    = $request->input('question_id');
        $questionIndex = $request->input('question_index');

        // "screen_leave" is the headline metric counted in focus_loss_count
        if ($type === 'screen_leave') {
            $attempt->increment('focus_loss_count');
            $attempt->refresh();
        }

        // Append to the detailed log, capped to the last 100 entries
        $flags = $attempt->cheat_flags ?? [];
        $entry = ['type' => $type, 'at' => now()->toIso8601String()];
        if ($questionId)    $entry['question_id']    = (int) $questionId;
        if ($questionIndex !== null) $entry['question_index'] = (int) $questionIndex;
        $flags[] = $entry;
        if (count($flags) > 100) {
            $flags = array_slice($flags, -100);
        }
        $attempt->cheat_flags = $flags;

        // STRICT mode: pause the attempt if we crossed the threshold
        $paused = false;
        $exam = $attempt->exam;
        if ($type === 'screen_leave'
            && $exam?->proctoring
            && $exam?->proctoring_strict
            && !$attempt->paused_at
            && $attempt->focus_loss_count > (int) ($exam->proctoring_threshold ?: 2)) {
            $attempt->paused_at = now();
            $paused = true;
        }

        $attempt->save();

        return response()->json([
            'ok'     => true,
            'count'  => $attempt->focus_loss_count,
            'paused' => (bool) $attempt->paused_at,
        ]);
    }

    /** Lightweight status endpoint polled by the student page while paused. */
    public function examStatus(int $attemptId)
    {
        $attempt = ExamAttempt::find($attemptId);
        if (!$attempt) return response()->json(['ok' => false]);

        return response()->json([
            'ok'        => true,
            'status'    => $attempt->status,
            'paused'    => (bool) $attempt->paused_at,
            'paused_at' => $attempt->paused_at?->toIso8601String(),
        ]);
    }

    // Submit exam
    public function submitExam(Request $request, string $code)
    {
        $accessCode = ExamAccessCode::where('code', strtoupper($code))->firstOrFail();

        $attempt = ExamAttempt::where('access_code_id', $accessCode->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        // Save any remaining answers from the form
        $answers = $request->input('answers', []);
        foreach ($answers as $questionId => $data) {
            ExamAttemptAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                [
                    'option_id' => $data['option_id'] ?? null,
                    'text_answer' => $data['text_answer'] ?? null,
                ]
            );
        }

        $attempt->gradeAndSubmit();

        return redirect()->route('student.results', $code);
    }

    // Show results
    public function showResults(string $code)
    {
        $accessCode = ExamAccessCode::with(['exam'])->where('code', strtoupper($code))->firstOrFail();
        $exam = $accessCode->exam;

        $attempt = ExamAttempt::with(['answers.question.options', 'answers.option'])
            ->where('access_code_id', $accessCode->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->latest()
            ->firstOrFail();

        if (!$exam->show_results) {
            return view('student.results-hidden', compact('attempt', 'exam', 'accessCode'));
        }

        return view('student.results', compact('attempt', 'exam', 'accessCode'));
    }
}
