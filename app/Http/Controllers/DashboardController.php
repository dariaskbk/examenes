<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamQuestion;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $exams = Exam::where('user_id', $user->id)->latest()->get();

        $examIds = $exams->pluck('id');
        $totalExams   = $exams->count();
        $activeExams  = $exams->where('status', 'active')->count();
        $draftExams   = $exams->where('status', 'draft')->count();
        $totalAttempts = ExamAttempt::whereIn('exam_id', $examIds)->count();

        // Subject names for enrichment
        $subjectIds = $exams->pluck('subject_id')->filter()->unique();
        $subjectNames = Subject::whereIn('id', $subjectIds)->pluck('name', 'id');

        // Recent submitted attempts
        $recentAttempts = ExamAttempt::whereIn('exam_id', $examIds)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->latest('submitted_at')
            ->take(6)
            ->get();

        $studentIds = $recentAttempts->pluck('student_id')->unique();
        $students   = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        // Pending short-answer gradings across all exams
        $shortQIds = ExamQuestion::whereIn('exam_id', $examIds)
            ->where('type', 'short_answer')
            ->pluck('id');

        $pendingGrading = $shortQIds->isNotEmpty()
            ? ExamAttemptAnswer::whereIn('question_id', $shortQIds)
                ->whereNull('is_correct')
                ->whereNotNull('text_answer')
                ->count()
            : 0;

        // Currently active in-progress attempts
        $inProgressCount = $examIds->isNotEmpty()
            ? ExamAttempt::whereIn('exam_id', $examIds)->where('status', 'in_progress')->count()
            : 0;

        return view('dashboard', compact(
            'exams', 'totalExams', 'activeExams', 'draftExams',
            'totalAttempts', 'recentAttempts', 'students', 'subjectNames',
            'pendingGrading', 'inProgressCount'
        ));
    }
}
