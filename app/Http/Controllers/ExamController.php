<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAccessCode;
use App\Models\ExamQuestion;
use App\Models\ExamOption;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Year;
use App\Imports\QuestionsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExamController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Exam::where('user_id', Auth::id());

        // ── Archived filter: default hides archived ──────────────────────
        $view = $request->get('view', 'active'); // 'active' | 'archived'
        if ($view === 'archived') {
            $query->archived();
        } else {
            $query->active();
            $view = 'active';
        }

        // ── Text search: title AND/OR subject name ───────────────────────
        if ($search = trim($request->get('q', ''))) {
            $matchingSubjectIds = Subject::where('name', 'like', '%' . $search . '%')->pluck('id');
            $query->where(function ($q) use ($search, $matchingSubjectIds) {
                $q->where('title', 'like', '%' . $search . '%');
                if ($matchingSubjectIds->isNotEmpty()) {
                    $q->orWhereIn('subject_id', $matchingSubjectIds);
                }
            });
        }

        // ── Activity type filter ─────────────────────────────────────────
        if ($activityType = $request->get('activity_type')) {
            $query->where('activity_type', $activityType);
        }

        // ── Status filter ────────────────────────────────────────────────
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // ── Subject filter ───────────────────────────────────────────────
        if ($subjectId = $request->get('subject_id')) {
            $query->where('subject_id', $subjectId);
        }

        // ── Availability filter ──────────────────────────────────────────
        $avail = $request->get('avail');
        if ($avail === 'now') {
            $query->where('status', 'active')
                  ->where(fn($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', now()))
                  ->where(fn($q) => $q->whereNull('available_until')->orWhere('available_until', '>=', now()));
        } elseif ($avail === 'upcoming') {
            $query->where('available_from', '>', now());
        } elseif ($avail === 'expired') {
            $query->whereNotNull('available_until')->where('available_until', '<', now());
        } elseif ($avail === 'no_date') {
            $query->whereNull('available_from');
        }

        $exams = $query->latest()->paginate(15)->withQueryString();

        // ── Enrich: subject names (cross-DB) ─────────────────────────────
        $allSubjectIds = $exams->pluck('subject_id')->filter()->unique()->values();
        $subjects      = Subject::whereIn('id', $allSubjectIds)->pluck('name', 'id');

        // ── Enrich: question counts + submitted attempt counts (2 queries) ─
        $examIds        = $exams->pluck('id');
        $questionCounts = ExamQuestion::whereIn('exam_id', $examIds)
            ->selectRaw('exam_id, count(*) as cnt')->groupBy('exam_id')
            ->pluck('cnt', 'exam_id');
        $attemptCounts  = \App\Models\ExamAttempt::whereIn('exam_id', $examIds)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->selectRaw('exam_id, count(*) as cnt')->groupBy('exam_id')
            ->pluck('cnt', 'exam_id');

        // ── Teacher's subjects for filter dropdown ───────────────────────
        [$teacherSubjects] = $this->getTeacherSubjects();

        // ── Counts per tab (so the user can see how many archived they have) ─
        $activeCount   = Exam::where('user_id', Auth::id())->active()->count();
        $archivedCount = Exam::where('user_id', Auth::id())->archived()->count();

        // ── AJAX: return JSON payload ────────────────────────────────────
        if ($request->ajax()) {
            return response()->json([
                'html'       => view('exams._table_rows',
                    compact('exams', 'subjects', 'questionCounts', 'attemptCounts'))->render(),
                'pagination' => $exams->hasPages() ? $exams->links()->toHtml() : null,
                'total'      => $exams->total(),
            ]);
        }

        return view('exams.index',
            compact('exams', 'subjects', 'teacherSubjects', 'questionCounts', 'attemptCounts',
                    'view', 'activeCount', 'archivedCount'));
    }

    /** Archive an exam (soft-archive). Also auto-closes it so no one can access. */
    public function archive(Exam $exam)
    {
        $this->authorizeExam($exam);
        if ($exam->archived_at) {
            return back()->with('info', 'Esta actividad ya estaba archivada.');
        }

        $wasOpen = ($exam->status !== 'closed');
        $exam->archived_at = now();
        if ($wasOpen) {
            $exam->status = 'closed';
        }
        $exam->save();

        $msg = $wasOpen
            ? 'Actividad archivada y marcada como cerrada. La puedes ver en la pestaña "Archivadas".'
            : 'Actividad archivada. La puedes ver en la pestaña "Archivadas".';

        return redirect()->route('exams.index')->with('success', $msg);
    }

    /** Unarchive an exam — bring it back to the active list. */
    public function unarchive(Exam $exam)
    {
        $this->authorizeExam($exam);
        if (!$exam->archived_at) {
            return back()->with('info', 'Esta actividad no estaba archivada.');
        }
        $exam->archived_at = null;
        $exam->save();
        return back()->with('success', 'Actividad restaurada a la lista activa.');
    }

    public function create(Request $request)
    {
        [$subjects, $subjectsFiltered] = $this->getTeacherSubjects();
        $subjectsByCiclo = $this->groupSubjectsByCiclo($subjects);
        $years  = Year::orderBy('year', 'desc')->get();
        $activeYear = Year::where('status', 1)->orderBy('year', 'desc')->first();
        $levels = \App\Models\Level::orderBy('name')->get();
        // Pre-fill from SICORE button: ?evaluation_component_id=X
        $prefill = [];
        $prefillComponentId = $request->input('evaluation_component_id');
        if ($prefillComponentId) {
            $pc = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('evaluation_components as ec')
                ->join('evaluation_subject_year as esy', 'esy.id', '=', 'ec.evaluation_subject_year_id')
                ->join('evaluations as e', 'e.id', '=', 'esy.evaluation_id')
                ->join('subjects as s', 's.id', '=', 'esy.subject_id')
                ->leftJoin('sections as sec', 'sec.id', '=', 'ec.section_id')
                ->where('ec.id', $prefillComponentId)
                ->where('ec.user_id', Auth::id())
                ->select(
                    'ec.id', 'esy.subject_id', 'esy.year', 'e.type as sicore_type',
                    'e.name as evaluation_name', 's.name as subject_name', 'sec.name as section_name', 'ec.group_type'
                )
                ->first();
            if ($pc) {
                $prefill['evaluation_component_id'] = $pc->id;
                $prefill['subject_id']    = $pc->subject_id;
                $prefill['year_id']       = optional(Year::where('year', $pc->year)->first())->id;
                $prefill['activity_type'] = array_search($pc->sicore_type, \App\Models\Exam::SICORE_TYPE_MAP) ?: 'exam';
                $prefill['title']         = trim("{$pc->evaluation_name} — {$pc->subject_name} · {$pc->section_name}" . ($pc->group_type ? " {$pc->group_type}" : ''));
            }
        }

        $activityType = $prefill['activity_type'] ?? 'exam';
        $sicoreType   = \App\Models\Exam::sicoreTypeFor($activityType);
        $prefillSubjectId = $prefill['subject_id'] ?? null;

        $evaluationComponents = $this->teacherComponents($sicoreType, null, $prefillSubjectId);
        $linkedComponentIds   = !empty($prefill['evaluation_component_id']) ? [$prefill['evaluation_component_id']] : [];

        return view('exams.create', compact(
            'subjects', 'subjectsFiltered', 'subjectsByCiclo', 'years', 'activeYear', 'levels',
            'evaluationComponents', 'linkedComponentIds', 'prefill'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateExamRequest($request);
        $validated['user_id'] = Auth::id();

        // Ensure the subject belongs to this teacher
        if (!empty($validated['subject_id'])) {
            $this->validateSubjectOwnership($validated['subject_id']);
        }

        $this->assertNoSectionConflicts($request->input('evaluation_component_ids', []));
        $exam = Exam::create($validated);
        $this->syncExamComponents($exam, $request->input('evaluation_component_ids', []));

        $label = \App\Models\Exam::ACTIVITY_TYPES[$exam->activity_type]['label'] ?? 'Actividad';
        $msg   = $exam->isQuestionBased()
            ? "{$label} creado exitosamente. Ahora puedes agregar las preguntas."
            : "{$label} creada exitosamente.";

        return redirect()->route('exams.show', $exam)->with('success', $msg);
    }

    public function show(Exam $exam)
    {
        $this->authorizeExam($exam);

        $questions = ExamQuestion::with('options')
            ->where('exam_id', $exam->id)
            ->orderBy('order')
            ->get();

        $accessCodes = ExamAccessCode::where('exam_id', $exam->id)->get();
        $studentIds  = $accessCodes->pluck('student_id')->unique();
        $students    = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        // Sections shown in "Asignar Examen" panel:
        //  1. level_id set  → ALL sections of that level for the exam year
        //                      (teacher may assign to sections outside their timetable)
        //  2. subject_id set → sections where teacher has that subject scheduled this year
        //                      fallback: all teacher sections if no schedule entries found
        //  3. neither       → teacher's sections for the year
        $examYear = $exam->year_id ? Year::find($exam->year_id) : Year::where('status', 1)->orderBy('year', 'desc')->first();
        $teacherSections = collect();
        $sectionsFilteredByLevel = false;
        $sectionsFilteredByComponent = false;

        // 0. If the exam is linked to SICORE components, restrict to THOSE sections
        $linkedComponentIds = $exam->linkedComponentIds();

        if ($examYear) {
            if (!empty($linkedComponentIds)) {
                $componentSectionIds = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('evaluation_components')
                    ->whereIn('id', $linkedComponentIds)
                    ->pluck('section_id')
                    ->unique()
                    ->filter()
                    ->values();

                $teacherSections = Section::whereIn('id', $componentSectionIds)
                    ->with('level')->orderBy('name')->get();
                $sectionsFilteredByComponent = true;

            } elseif ($exam->level_id) {
                // Filter by level — ignore teacher schedule, show every section of this level
                $teacherSections = Section::where('level_id', $exam->level_id)
                    ->where('year_id', $examYear->id)
                    ->with('level')
                    ->orderBy('name')
                    ->get();
                // Fallback: if year_id column doesn't exist on sections table, query without it
                if ($teacherSections->isEmpty()) {
                    $teacherSections = Section::where('level_id', $exam->level_id)
                        ->with('level')
                        ->orderBy('name')
                        ->get();
                }
                $sectionsFilteredByLevel = true;

            } elseif ($exam->subject_id) {
                $sectionIds = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('schedules')
                    ->where('user_id', Auth::id())
                    ->where('subject_id', $exam->subject_id)
                    ->where('year_id', $examYear->id)
                    ->distinct()
                    ->pluck('section_id');

                $teacherSections = $sectionIds->isNotEmpty()
                    ? Section::whereIn('id', $sectionIds)->with('level')->orderBy('name')->get()
                    : Auth::user()->sectionsForYear($examYear->year)->with('level')->orderBy('name')->get();

            } else {
                $teacherSections = Auth::user()->sectionsForYear($examYear->year)->with('level')->orderBy('name')->get();
            }
        }

        // Attach student count per section for the exam's year
        if ($examYear && $teacherSections->isNotEmpty()) {
            $yearValue = $examYear->year;
            $sectionIds = $teacherSections->pluck('id');
            $counts = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('section_student_year')
                ->whereIn('section_id', $sectionIds)
                ->where('year', $yearValue)
                ->selectRaw('section_id, count(*) as total')
                ->groupBy('section_id')
                ->pluck('total', 'section_id');
            $teacherSections->each(fn($s) => $s->student_count = $counts[$s->id] ?? 0);
        }

        // Codes already generated per section (for showing coverage)
        $codedStudentIds = $accessCodes->pluck('student_id')->flip();

        $years = Year::orderBy('year', 'desc')->get();
        $subject = $exam->subject_id ? Subject::find($exam->subject_id) : null;

        $attemptsCount  = \App\Models\ExamAttempt::where('exam_id', $exam->id)->count();
        $submittedCount = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'timed_out'])->count();

        // Preload last attempt per access code (avoids N+1 in the codes table)
        $lastAttemptsByCode = \App\Models\ExamAttempt::whereIn('access_code_id', $accessCodes->pluck('id'))
            ->whereIn('status', ['submitted', 'timed_out'])
            ->orderByDesc('submitted_at')
            ->get()
            ->unique('access_code_id')
            ->keyBy('access_code_id');

        // Codes that already have an attempt (any status) → cannot be regenerated
        $codesWithAttempts = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('access_code_id', $accessCodes->pluck('id'))
            ->pluck('access_code_id')
            ->unique()
            ->flip();

        // Sections that actually have codes for this exam (for the PDF section filter)
        $codeSections = collect();
        if ($examYear && $accessCodes->isNotEmpty()) {
            $codeStudentIds = $accessCodes->pluck('student_id')->unique();
            $codeSections = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('section_student_year as ssy')
                ->join('sections as s', 's.id', '=', 'ssy.section_id')
                ->whereIn('ssy.student_id', $codeStudentIds)
                ->where('ssy.year', $examYear->year)
                ->select('s.id', 's.name')
                ->distinct()
                ->orderBy('s.name')
                ->get();
        }

        // Preload coded count per section using the already-loaded accessCodes (avoids extra count queries)
        $sectionCodedCounts = [];
        if ($teacherSections->isNotEmpty() && $examYear) {
            foreach ($teacherSections as $sec) {
                $sids = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('section_student_year')
                    ->where('section_id', $sec->id)
                    ->where('year', $examYear->year)
                    ->pluck('student_id')
                    ->toArray();
                $sectionCodedCounts[$sec->id] = $accessCodes->whereIn('student_id', $sids)->count();
            }
        }

        return view('exams.show', compact(
            'exam', 'questions', 'accessCodes', 'students',
            'teacherSections', 'sectionsFilteredByLevel', 'sectionsFilteredByComponent', 'examYear', 'codedStudentIds',
            'years', 'attemptsCount', 'submittedCount', 'subject',
            'lastAttemptsByCode', 'sectionCodedCounts', 'codesWithAttempts', 'codeSections'
        ));
    }

    public function edit(Exam $exam)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            return back()->with('error', 'No se puede editar un examen que ya tiene intentos completados.');
        }

        [$subjects, $subjectsFiltered] = $this->getTeacherSubjects();
        $subjectsByCiclo = $this->groupSubjectsByCiclo($subjects);
        $years  = Year::orderBy('year', 'desc')->get();
        $activeYear = null; // on edit, year is already saved on the exam
        $levels = \App\Models\Level::orderBy('name')->get();
        $sicoreType = \App\Models\Exam::sicoreTypeFor($exam->activity_type);
        $evaluationComponents = $this->teacherComponents($sicoreType, $exam, $exam->subject_id, $exam->level_id);
        $linkedComponentIds   = $exam->linkedComponentIds();
        $prefill              = [];

        return view('exams.edit', compact(
            'exam', 'subjects', 'subjectsFiltered', 'subjectsByCiclo', 'years', 'activeYear', 'levels',
            'evaluationComponents', 'linkedComponentIds', 'prefill'
        ));
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            return back()->with('error', 'No se puede editar un examen con intentos completados.');
        }

        $validated = $this->validateExamRequest($request);

        if (!empty($validated['subject_id'])) {
            $this->validateSubjectOwnership($validated['subject_id']);
        }

        $this->assertNoSectionConflicts($request->input('evaluation_component_ids', []));
        $exam->update($validated);
        $this->syncExamComponents($exam, $request->input('evaluation_component_ids', []));

        return redirect()->route('exams.show', $exam)->with('success', 'Examen actualizado exitosamente.');
    }

    public function destroy(Exam $exam)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeDeleted()) {
            return back()->with('error', 'No se puede eliminar un examen con intentos completados. Ciérrelo en su lugar.');
        }

        $exam->delete();
        return redirect()->route('exams.index')->with('success', 'Examen eliminado.');
    }

    public function cloneExam(Exam $exam)
    {
        $this->authorizeExam($exam);

        $newExam = $exam->replicate(['status']);
        $newExam->title  = 'Copia de ' . $exam->title;
        $newExam->status = 'draft';
        $newExam->save(); // clone starts with no SICORE component links (pivot not copied)

        foreach (ExamQuestion::with('options')->where('exam_id', $exam->id)->orderBy('order')->get() as $question) {
            $newQ = $question->replicate();
            $newQ->exam_id = $newExam->id;
            $newQ->save();

            foreach ($question->options as $option) {
                $newOpt = $option->replicate();
                $newOpt->question_id = $newQ->id;
                $newOpt->save();
            }
        }

        return redirect()->route('exams.show', $newExam)
            ->with('success', 'Examen duplicado exitosamente. Ahora puedes editarlo.');
    }

    // ── Questions ──────────────────────────────────────────────────────────

    public function storeQuestion(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $allTypes = implode(',', array_keys(\App\Models\ExamQuestion::TYPES)) . ',multiple_choice';

        $request->validate([
            'type'           => "required|in:{$allTypes}",
            'question_text'  => 'required|string',
            'points'         => 'required|numeric|min:0.1|max:100',
            'media_type'     => 'nullable|in:none,image,audio,video',
            'image'          => 'nullable|image|max:8192',
            'audio'          => 'nullable|mimes:mp3,wav,ogg,m4a|max:20480',
            'video'          => 'nullable|mimes:mp4,webm,mov|max:102400',
            'options'        => 'nullable|array|min:2|max:8',
            'options.*.text' => 'nullable|string|max:500',
            'correct_mc'     => 'nullable|integer',
            'correct_ms'     => 'nullable|array',
            'correct_answer' => 'nullable|in:true,false',
            'pairs'          => 'nullable|array|min:2|max:8',
            'pairs.*.concept'    => 'nullable|string|max:500',
            'pairs.*.definition' => 'nullable|string|max:500',
            'ordering_items'     => 'nullable|array|min:2|max:10',
            'ordering_items.*'   => 'nullable|string|max:500',
            'ident_items'        => 'nullable|array|min:1|max:5',
            'ident_items.*.label'  => 'nullable|string|max:10',
            'ident_items.*.answer' => 'nullable|string|max:500',
            'cp_answers'           => 'nullable|array|min:1|max:8',
            'cp_answers.*'         => 'nullable|string|max:200',
            'cp_distractors'       => 'nullable|array|max:6',
            'cp_distractors.*'     => 'nullable|string|max:200',
            'grading_criteria'   => 'nullable|string',
            'rubric_json'        => 'nullable|string',
        ]);

        // Store media files
        $imagePath = $audioPath = $videoPath = null;
        if ($request->hasFile('image')) $imagePath = $this->storeUpload($request->file('image'), 'exam-images');
        if ($request->hasFile('audio')) $audioPath = $this->storeUpload($request->file('audio'), 'exam-audio');
        if ($request->hasFile('video')) $videoPath = $this->storeUpload($request->file('video'), 'exam-video');

        // If no file uploaded, check for a reused path from another question in this exam
        if (!$imagePath && !$audioPath && !$videoPath) {
            $reusePath = trim($request->input('media_reuse_path', ''));
            if ($reusePath !== '') {
                $validReuse = ExamQuestion::where('exam_id', $exam->id)
                    ->where(fn($q) => $q->where('image', $reusePath)
                        ->orWhere('audio', $reusePath)
                        ->orWhere('video', $reusePath))
                    ->exists();
                if ($validReuse) {
                    $ext = strtolower(pathinfo($reusePath, PATHINFO_EXTENSION));
                    $reuseType = match(true) {
                        in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']) => 'image',
                        in_array($ext, ['mp3','wav','ogg','m4a'])               => 'audio',
                        default                                                  => 'video',
                    };
                    match($reuseType) {
                        'image' => $imagePath = $reusePath,
                        'audio' => $audioPath = $reusePath,
                        'video' => $videoPath = $reusePath,
                    };
                }
            }
        }

        $mediaType = $request->input('media_type', 'none');
        // If media_type is 'none' but files were uploaded, detect automatically
        if ($mediaType === 'none') {
            if ($audioPath) $mediaType = 'audio';
            elseif ($videoPath) $mediaType = 'video';
            elseif ($imagePath) $mediaType = 'image';
        }

        $order = ExamQuestion::where('exam_id', $exam->id)->max('order') + 1;
        $type  = $request->type === 'multiple_choice' ? 'single_choice' : $request->type;

        $question = ExamQuestion::create([
            'exam_id'           => $exam->id,
            'type'              => $type,
            'question_text'     => $request->question_text,
            'points'            => $request->points,
            'image'             => $imagePath,
            'audio'             => $audioPath,
            'video'             => $videoPath,
            'media_type'        => $mediaType,
            'order'             => $order,
            'grading_criteria'  => in_array($type, \App\Models\ExamQuestion::RUBRIC_TYPES)
                                    ? $request->input('grading_criteria') : null,
            'rubric'            => in_array($type, \App\Models\ExamQuestion::RUBRIC_TYPES)
                                    ? $this->parseRubricInput($request->input('rubric_json')) : null,
        ]);

        match ($type) {
            'single_choice'   => $this->saveChoiceOptions($question, $request, false),
            'multiple_select' => $this->saveChoiceOptions($question, $request, true),
            'true_false'      => $this->saveTrueFalseOptions($question, $request),
            'matching'        => $this->saveMatchingPairs($question, $request),
            'ordering'        => $this->saveOrderingItems($question, $request),
            'identification'  => $this->saveIdentificationItems($question, $request),
            'completion'      => $this->saveCompletionItems($question, $request),
            default           => null,
        };

        return back()->with('success', 'Pregunta agregada correctamente.');
    }

    public function updateQuestion(Request $request, Exam $exam, ExamQuestion $question)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            return back()->with('error', 'No se puede editar con intentos activos.');
        }

        $request->validate([
            'question_text'      => 'required|string',
            'points'             => 'required|numeric|min:0.1|max:100',
            'media_type'         => 'nullable|in:none,image,audio,video',
            'remove_media'       => 'nullable',
            'image'              => 'nullable|image|max:8192',
            'audio'              => 'nullable|mimes:mp3,wav,ogg,m4a|max:20480',
            'video'              => 'nullable|mimes:mp4,webm,mov|max:102400',
            'options'            => 'nullable|array',
            'options.*.text'     => 'nullable|string|max:500',
            'correct_mc'         => 'nullable|integer',
            'correct_ms'         => 'nullable|array',
            'correct_answer'     => 'nullable|in:true,false',
            'pairs'              => 'nullable|array',
            'pairs.*.concept'    => 'nullable|string|max:500',
            'pairs.*.definition' => 'nullable|string|max:500',
            'ordering_items'     => 'nullable|array',
            'ordering_items.*'   => 'nullable|string|max:500',
            'ident_items'        => 'nullable|array|min:1|max:5',
            'ident_items.*.label'  => 'nullable|string|max:10',
            'ident_items.*.answer' => 'nullable|string|max:500',
            'cp_answers'           => 'nullable|array|min:1|max:8',
            'cp_answers.*'         => 'nullable|string|max:200',
            'cp_distractors'       => 'nullable|array|max:6',
            'cp_distractors.*'     => 'nullable|string|max:200',
            'grading_criteria'   => 'nullable|string',
            'rubric_json'        => 'nullable|string',
        ]);

        $imagePath = $question->image;
        $audioPath = $question->audio;
        $videoPath = $question->video;
        $mediaType = $request->input('media_type', $question->media_type ?? 'none');

        // Self-heal: if media_type says 'audio'/'image'/'video' but the file column is empty, reset to 'none'
        if ($mediaType === 'image' && !$imagePath) $mediaType = 'none';
        if ($mediaType === 'audio' && !$audioPath) $mediaType = 'none';
        if ($mediaType === 'video' && !$videoPath) $mediaType = 'none';

        // Remove media if requested or media_type set to none
        if ($request->filled('remove_media') || $mediaType === 'none') {
            if ($question->image) $this->safeDeleteMedia($question->image);
            if ($question->audio) $this->safeDeleteMedia($question->audio);
            if ($question->video) $this->safeDeleteMedia($question->video);
            $imagePath = $audioPath = $videoPath = null;
            $mediaType = 'none';
        }

        if ($request->hasFile('image')) {
            if ($question->image) $this->safeDeleteMedia($question->image);
            $imagePath = $this->storeUpload($request->file('image'), 'exam-images');
            $mediaType = 'image';
        }
        if ($request->hasFile('audio')) {
            if ($question->audio) $this->safeDeleteMedia($question->audio);
            $audioPath = $this->storeUpload($request->file('audio'), 'exam-audio');
            $mediaType = 'audio';
        }
        if ($request->hasFile('video')) {
            if ($question->video) $this->safeDeleteMedia($question->video);
            $videoPath = $this->storeUpload($request->file('video'), 'exam-video');
            $mediaType = 'video';
        }

        // If no new file was uploaded, check for a reused path from another question in this exam
        if (!$request->hasFile('image') && !$request->hasFile('audio') && !$request->hasFile('video')
            && !$request->filled('remove_media') && $mediaType === 'none') {
            $reusePath = trim($request->input('media_reuse_path', ''));
            if ($reusePath !== '') {
                $validReuse = ExamQuestion::where('exam_id', $exam->id)
                    ->where(fn($q) => $q->where('image', $reusePath)
                        ->orWhere('audio', $reusePath)
                        ->orWhere('video', $reusePath))
                    ->exists();
                if ($validReuse) {
                    $ext = strtolower(pathinfo($reusePath, PATHINFO_EXTENSION));
                    $reuseType = match(true) {
                        in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']) => 'image',
                        in_array($ext, ['mp3','wav','ogg','m4a'])               => 'audio',
                        default                                                  => 'video',
                    };
                    match($reuseType) {
                        'image' => $imagePath = $reusePath,
                        'audio' => $audioPath = $reusePath,
                        'video' => $videoPath = $reusePath,
                    };
                    $mediaType = $reuseType;
                }
            }
        }

        $question->update([
            'question_text'    => $request->question_text,
            'points'           => $request->points,
            'image'            => $imagePath,
            'audio'            => $audioPath,
            'video'            => $videoPath,
            'media_type'       => $mediaType,
            'grading_criteria' => in_array($question->type, \App\Models\ExamQuestion::RUBRIC_TYPES)
                                    ? $request->input('grading_criteria') : $question->grading_criteria,
            'rubric'           => in_array($question->type, \App\Models\ExamQuestion::RUBRIC_TYPES)
                                    ? $this->parseRubricInput($request->input('rubric_json')) : $question->rubric,
        ]);

        // Delete and recreate options
        $question->options()->delete();

        match ($question->type) {
            'single_choice', 'multiple_choice' => $this->saveChoiceOptions($question, $request, false),
            'multiple_select'                  => $this->saveChoiceOptions($question, $request, true),
            'true_false'                        => $this->saveTrueFalseOptions($question, $request),
            'matching'                          => $this->saveMatchingPairs($question, $request),
            'ordering'                          => $this->saveOrderingItems($question, $request),
            'identification'                    => $this->saveIdentificationItems($question, $request),
            'completion'                        => $this->saveCompletionItems($question, $request),
            default                             => null,
        };

        // Redirect back to the show page anchored on the edited question so the
        // browser scrolls right where the teacher was, not to the top.
        return redirect(route('exams.show', $exam) . '#q-' . $question->id)
            ->with('success', 'Pregunta actualizada correctamente.')
            ->with('scrolled_question_id', $question->id);
    }

    /**
     * Store an uploaded file using its MD5 hash as filename.
     * If an identical file already exists on disk it is reused — no duplicate storage.
     * PHP 8.4 + Windows safe (avoids getRealPath()).
     */
    private function storeUpload(\Illuminate\Http\UploadedFile $file, string $directory): string
    {
        $contents = file_get_contents($file->getPathname());
        $ext      = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $filename = md5($contents) . ($ext ? '.' . $ext : '');
        $path     = $directory . '/' . $filename;

        // Only write if the file is not already stored (deduplication)
        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $contents);
        }

        return $path;
    }

    /**
     * Delete a media file from storage only when no other question still references it.
     */
    private function safeDeleteMedia(string $path): void
    {
        $stillUsed = \App\Models\ExamQuestion::where(function ($q) use ($path) {
            $q->where('image', $path)
              ->orWhere('audio', $path)
              ->orWhere('video', $path);
        })->exists();

        if (!$stillUsed) {
            Storage::disk('public')->delete($path);
        }
    }

    private function saveChoiceOptions(ExamQuestion $question, $request, bool $multipleCorrect): void
    {
        $options = $request->input('options', []);
        $correctMc = (int) $request->input('correct_mc', 0);
        $correctMs = array_map('intval', $request->input('correct_ms', []));

        $order = 1;
        foreach ($options as $i => $optData) {
            $text = trim($optData['text'] ?? '');
            if (empty($text)) continue;

            $optImage = null;
            if ($request->hasFile("options.{$i}.image")) {
                $optImage = $this->storeUpload($request->file("options.{$i}.image"), 'exam-images');
            }

            ExamOption::create([
                'question_id' => $question->id,
                'option_text' => $text,
                'is_correct'  => $multipleCorrect ? in_array($i, $correctMs) : ($i === $correctMc),
                'image'       => $optImage,
                'order'       => $order++,
            ]);
        }
    }

    private function saveTrueFalseOptions(ExamQuestion $question, $request): void
    {
        $isTrue = $request->correct_answer === 'true';
        ExamOption::create(['question_id' => $question->id, 'option_text' => 'Verdadero', 'is_correct' => $isTrue,  'order' => 1]);
        ExamOption::create(['question_id' => $question->id, 'option_text' => 'Falso',     'is_correct' => !$isTrue, 'order' => 2]);
    }

    private function saveMatchingPairs(ExamQuestion $question, $request): void
    {
        foreach ($request->input('pairs', []) as $i => $pair) {
            $concept = trim($pair['concept'] ?? '');
            $def     = trim($pair['definition'] ?? '');
            if (empty($concept) || empty($def)) continue;
            ExamOption::create([
                'question_id' => $question->id,
                'option_text' => $concept,
                'match_text'  => $def,
                'is_correct'  => true,
                'order'       => $i + 1,
            ]);
        }
    }

    private function saveOrderingItems(ExamQuestion $question, $request): void
    {
        $items = array_filter(array_map('trim', $request->input('ordering_items', [])));
        foreach (array_values($items) as $i => $text) {
            ExamOption::create([
                'question_id' => $question->id,
                'option_text' => $text,
                'is_correct'  => true,
                'order'       => $i + 1,
            ]);
        }
    }

    private function saveIdentificationItems(ExamQuestion $question, $request): void
    {
        $labels = ['A','B','C','D','E'];
        foreach ($request->input('ident_items', []) as $i => $item) {
            $label  = strtoupper(trim($item['label'] ?? ($labels[$i] ?? '')));
            $answer = trim($item['answer'] ?? '');
            if (empty($label) || empty($answer)) continue;
            ExamOption::create([
                'question_id' => $question->id,
                'option_text' => $label,    // e.g. "A", "B", "1", "2"
                'match_text'  => $answer,   // correct answer text
                'is_correct'  => true,
                'order'       => $i + 1,
            ]);
        }
    }

    private function saveCompletionItems(ExamQuestion $question, $request): void
    {
        // Correct answers — order = blank number (1, 2, 3…), is_correct = true
        $answers = array_filter(array_map('trim', $request->input('cp_answers', [])));
        $blankNum = 0;
        foreach (array_values($answers) as $word) {
            if (empty($word)) continue;
            $blankNum++;
            ExamOption::create([
                'question_id' => $question->id,
                'option_text' => $word,
                'is_correct'  => true,
                'order'       => $blankNum,
            ]);
        }
        // Auto-sync points = number of blanks
        if ($blankNum > 0) {
            $question->update(['points' => $blankNum]);
        }

        // Distractors — is_correct = false, order = 0
        $distractors = array_filter(array_map('trim', $request->input('cp_distractors', [])));
        foreach ($distractors as $word) {
            if (empty($word)) continue;
            ExamOption::create([
                'question_id' => $question->id,
                'option_text' => $word,
                'is_correct'  => false,
                'order'       => 0,
            ]);
        }
    }

    public function destroyQuestion(Exam $exam, ExamQuestion $question)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No se pueden eliminar preguntas con intentos activos.'], 403);
            }
            return back()->with('error', 'No se pueden eliminar preguntas con intentos activos.');
        }

        $question->delete();

        if (request()->expectsJson()) {
            $remaining = $exam->questions()->count();
            return response()->json(['success' => true, 'count' => $remaining]);
        }

        return back()->with('success', 'Pregunta eliminada.');
    }

    public function destroyAllQuestions(Exam $exam)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            return back()->with('error', 'No se pueden eliminar preguntas con intentos activos.');
        }

        $count = ExamQuestion::where('exam_id', $exam->id)->count();

        if ($count === 0) {
            return back()->with('info', 'Este examen no tiene preguntas.');
        }

        // Delete options first (cascade not guaranteed via ORM)
        $questionIds = ExamQuestion::where('exam_id', $exam->id)->pluck('id');
        \App\Models\ExamOption::whereIn('question_id', $questionIds)->delete();
        ExamQuestion::where('exam_id', $exam->id)->delete();

        return back()->with('success', "Se eliminaron {$count} pregunta(s) correctamente.");
    }

    // ── Excel import ───────────────────────────────────────────────────────

    public function importQuestions(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $request->validate([
            'excel_file' => 'required|file|max:102400',
        ], [
            'excel_file.required' => 'Selecciona un archivo Excel o ZIP.',
            'excel_file.file'     => 'El campo debe ser un archivo válido.',
            'excel_file.max'      => 'El archivo no debe superar los 100 MB.',
        ]);

        $file = $request->file('excel_file');
        $ext  = strtolower($file->getClientOriginalExtension());

        // ── Validate file type ──────────────────────────────────────────────
        $allowedExtensions = ['xlsx', 'xls', 'zip'];
        if (!in_array($ext, $allowedExtensions)) {
            return back()->with('error',
                "Tipo de archivo no permitido (.{$ext}). Solo se aceptan archivos .xlsx, .xls o .zip con la plantilla de preguntas."
            );
        }

        // Extra MIME check to reject disguised files
        $mime          = $file->getMimeType() ?? '';
        $allowedMimes  = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.ms-excel',                                           // xls
            'application/zip',
            'application/x-zip-compressed',
            'application/octet-stream', // some systems report this for xlsx/zip
        ];
        if (!empty($mime) && !in_array($mime, $allowedMimes)) {
            return back()->with('error',
                "El archivo no parece ser un Excel o ZIP válido (MIME: {$mime}). Verifica que estés subiendo la plantilla correcta."
            );
        }

        $tempDir = storage_path('app/imports-temp/' . uniqid('imp_'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $excelPath  = null;
        $mediaFiles = []; // ['filename.jpg' => '/full/path']
        $dispatched = false; // when true, the queued job owns $tempDir cleanup

        try {
            if ($ext === 'zip') {
                // ── ZIP bundle: extract and locate Excel + media files ──────
                $zipPath = $tempDir . '/bundle.zip';
                $file->move($tempDir, 'bundle.zip');

                $zip = new \ZipArchive();
                if ($zip->open($zipPath) !== true) {
                    return back()->with('error', 'No se pudo abrir el archivo ZIP. Asegúrese de que no esté corrupto.');
                }
                $zip->extractTo($tempDir);
                $zip->close();
                unlink($zipPath);

                // Find the Excel file (first .xlsx or .xls found, any subfolder)
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir));
                foreach ($iterator as $f) {
                    if ($f->isFile()) {
                        $fExt = strtolower($f->getExtension());
                        if (!$excelPath && in_array($fExt, ['xlsx','xls'])) {
                            $excelPath = $f->getPathname();
                        } else {
                            // Any other file is treated as a potential media file
                            $mediaFiles[strtolower($f->getFilename())] = $f->getPathname();
                        }
                    }
                }

                if (!$excelPath) {
                    return back()->with('error', 'El ZIP no contiene ningún archivo Excel (.xlsx). Incluya la plantilla dentro del ZIP.');
                }
            } else {
                // ── Plain Excel upload (backward compatible) ────────────────
                $filename  = 'plantilla.' . $ext;
                $file->move($tempDir, $filename);
                $excelPath = $tempDir . '/' . $filename;
            }

            // Hand off to the queue. The job owns the tempdir from here and
            // will clean it up after the import finishes (success or fail).
            $op = \App\Models\BackgroundOperation::create([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'exam_id' => $exam->id,
                'type'    => 'import_questions',
                'status'  => 'pending',
                'message' => 'En cola para procesar el archivo…',
                'payload' => ['filename' => $file->getClientOriginalName(), 'media_count' => count($mediaFiles)],
            ]);
            \App\Jobs\ImportQuestionsJob::dispatch($op->id, $exam->id, $tempDir, $excelPath, $mediaFiles);
            $dispatched = true;

            return back()->with('info',
                'Importación en curso. Recargá en unos segundos para ver cuántas preguntas se importaron.'
            );
        } finally {
            // Only clean up if we didn't hand the tempdir off to the queue.
            if (!$dispatched) {
                $this->cleanDir($tempDir);
            }
        }
    }

    // ── Question bank (reuse questions from the teacher's other exams) ───────

    /** JSON list of reusable questions from the teacher's OTHER exams. */
    public function questionBank(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        // Teacher's exams except the current one
        $myExamIds = Exam::where('user_id', Auth::id())
            ->where('id', '!=', $exam->id)
            ->pluck('id');

        // Optionally restrict to the same subject
        if ($request->boolean('same_subject') && $exam->subject_id) {
            $myExamIds = Exam::where('user_id', Auth::id())
                ->where('id', '!=', $exam->id)
                ->where('subject_id', $exam->subject_id)
                ->pluck('id');
        }

        if ($myExamIds->isEmpty()) {
            return response()->json(['questions' => []]);
        }

        $query = ExamQuestion::whereIn('exam_id', $myExamIds)
            ->withCount('options')
            ->with('exam:id,title')
            ->orderByDesc('id');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($search = trim((string) $request->input('search'))) {
            $query->where('question_text', 'like', '%' . $search . '%');
        }

        $questions = $query->limit(150)->get()->map(fn($q) => [
            'id'            => $q->id,
            'type'          => $q->type,
            'type_label'    => \App\Models\ExamQuestion::TYPES[$q->type] ?? $q->type,
            'text'          => \Illuminate\Support\Str::limit(trim(strip_tags($q->question_text ?? '')), 130) ?: '(sin enunciado)',
            'points'        => (float) $q->points,
            'options_count' => $q->options_count,
            'exam_title'    => $q->exam?->title ?? '—',
        ]);

        return response()->json(['questions' => $questions]);
    }

    /** Clone selected bank questions (and their options) into this exam. */
    public function importFromBank(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            return back()->with('error', 'No se pueden agregar preguntas a un examen con intentos activos.');
        }

        $validated = $request->validate([
            'question_ids'   => 'required|array|min:1',
            'question_ids.*' => 'integer',
        ], [
            'question_ids.required' => 'Selecciona al menos una pregunta del banco.',
        ]);

        // Security: only clone questions that belong to the teacher's own exams
        $myExamIds = Exam::where('user_id', Auth::id())->pluck('id');

        $sources = ExamQuestion::with('options')
            ->whereIn('id', $validated['question_ids'])
            ->whereIn('exam_id', $myExamIds)
            ->get();

        if ($sources->isEmpty()) {
            return back()->with('error', 'No se encontraron preguntas válidas para agregar.');
        }

        $order = ExamQuestion::where('exam_id', $exam->id)->max('order') ?? 0;
        $count = 0;

        foreach ($sources as $source) {
            $order++;
            $newQuestion = $source->replicate(['exam_id', 'order']);
            $newQuestion->exam_id = $exam->id;
            $newQuestion->order   = $order;
            $newQuestion->save();

            foreach ($source->options as $opt) {
                $newOption = $opt->replicate(['question_id']);
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
            $count++;
        }

        return back()->with('success', "Se agregaron {$count} pregunta(s) desde el banco.");
    }

    /**
     * Parse and sanitize the rubric JSON coming from the form. Returns an array
     * structure or null if invalid/empty. Drops empty criteria.
     */
    private function parseRubricInput(?string $json): ?array
    {
        if (empty($json)) return null;
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['levels']) || empty($data['criteria'])) return null;

        $levels = [];
        foreach ($data['levels'] as $lvl) {
            $name = trim((string) ($lvl['name'] ?? ''));
            $pts  = (float) ($lvl['points'] ?? 0);
            if ($name === '') continue;
            $levels[] = ['name' => $name, 'points' => $pts];
        }
        if (empty($levels)) return null;

        $criteria = [];
        foreach ($data['criteria'] as $crit) {
            $name = trim((string) ($crit['name'] ?? ''));
            if ($name === '') continue;
            $descriptors = array_map(fn($d) => trim((string) $d), (array) ($crit['descriptors'] ?? []));
            // Pad / trim to match levels count
            $descriptors = array_pad(array_slice($descriptors, 0, count($levels)), count($levels), '');
            $criteria[] = ['name' => $name, 'descriptors' => $descriptors];
        }
        if (empty($criteria)) return null;

        return ['levels' => $levels, 'criteria' => $criteria];
    }

    /** Recursively delete a temporary directory */
    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }

    // ── Access codes ───────────────────────────────────────────────────────

    /**
     * Generate access codes for sections and/or individual students.
     * Accepts: section_ids[] (array) and/or student_ids[] (array).
     */
    public function generateCodes(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $request->validate([
            'section_ids'   => 'nullable|array',
            'section_ids.*' => 'integer',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'integer',
        ]);

        if (empty($request->section_ids) && empty($request->student_ids)) {
            return back()->with('error', 'Selecciona al menos una sección o un estudiante.');
        }

        $examYear = $exam->year_id ? Year::find($exam->year_id) : Year::where('status', 1)->orderBy('year', 'desc')->first();
        $yearValue = $examYear?->year;

        $studentIds = collect($request->student_ids ?? []);
        $sicore     = \Illuminate\Support\Facades\DB::connection('sicore');

        // Guard: if the exam is linked to SICORE components, codes may ONLY be
        // generated for those components' sections (so grades can sync). Drop the rest.
        $linkedComponentIds = $exam->linkedComponentIds();
        if (!empty($linkedComponentIds)) {
            $allowedSectionIds = $sicore->table('evaluation_components')
                ->whereIn('id', $linkedComponentIds)
                ->pluck('section_id')->unique()->filter()->all();

            $requestSectionIds = collect($request->section_ids ?? [])
                ->filter(fn($id) => in_array((int) $id, $allowedSectionIds))->values();
            $request->merge(['section_ids' => $requestSectionIds->all()]);

            // Keep only individually-selected students that belong to those sections
            if ($studentIds->isNotEmpty() && !empty($allowedSectionIds)) {
                $studentIds = collect($sicore->table('section_student_year')
                    ->whereIn('section_id', $allowedSectionIds)
                    ->where('year', $yearValue)
                    ->whereIn('student_id', $studentIds->all())
                    ->pluck('student_id')->all());
            } else {
                $studentIds = collect();
            }

            if ($requestSectionIds->isEmpty() && $studentIds->isEmpty()) {
                return back()->with('error', 'Las secciones seleccionadas no corresponden a los componentes vinculados en SICORE.');
            }
        }

        // Add all students from selected sections — same filters as sectionStudents()
        foreach ($request->section_ids ?? [] as $sectionId) {
            $section = Section::find($sectionId);
            if (!$section) continue;

            // Determine group_type for sub_grupo filter (A/B = half-group, C/null = full)
            $groupType = null;
            if ($exam->subject_id && $examYear) {
                $groupType = $sicore->table('schedules')
                    ->where('user_id', Auth::id())
                    ->where('section_id', $sectionId)
                    ->where('subject_id', $exam->subject_id)
                    ->where('year_id', $examYear->id)
                    ->value('group_type');
            }

            $secQuery = $sicore->table('section_student_year')
                ->where('section_id', $sectionId)
                ->where('year', $yearValue)
                ->whereIn('condition', ['regular', 'repitente']);

            if ($groupType && in_array(strtoupper($groupType), ['A', 'B'])) {
                $secQuery->where('sub_grupo', strtoupper($groupType));
            }

            $secStudentIds = $secQuery->pluck('student_id');

            // Further filter by student_subject_year for the exam's subject
            if ($exam->subject_id && $secStudentIds->isNotEmpty()) {
                $secStudentIds = $sicore->table('student_subject_year')
                    ->where('subject_id', $exam->subject_id)
                    ->where('year', $yearValue)
                    ->whereIn('student_id', $secStudentIds)
                    ->pluck('student_id');
            }

            $studentIds = $studentIds->merge($secStudentIds);
        }

        $studentIds = $studentIds->unique()->values();

        if ($studentIds->isEmpty()) {
            return back()->with('error', 'No se encontraron estudiantes en las secciones seleccionadas.');
        }

        $generated = 0;
        foreach ($studentIds as $sid) {
            $exists = ExamAccessCode::where('exam_id', $exam->id)
                ->where('student_id', $sid)->exists();

            if (!$exists) {
                ExamAccessCode::create([
                    'exam_id'    => $exam->id,
                    'student_id' => $sid,
                    'code'       => $this->generateUniqueCode(),
                    'expires_at' => $exam->available_until,
                ]);
                $generated++;
            }
        }

        $skipped = $studentIds->count() - $generated;
        $msg = "Se generaron {$generated} código(s) nuevo(s).";
        if ($skipped > 0) $msg .= " {$skipped} estudiante(s) ya tenían código.";

        return back()->with($generated > 0 ? 'success' : 'info', $msg);
    }

    /**
     * AJAX: returns students in a section for the exam's year.
     */
    public function sectionStudents(Exam $exam, Section $section)
    {
        $this->authorizeExam($exam);

        $examYear = $exam->year_id ? Year::find($exam->year_id) : Year::where('status', 1)->orderBy('year', 'desc')->first();
        $yearValue = $examYear?->year;

        $sicore = \Illuminate\Support\Facades\DB::connection('sicore');

        // Determine the teacher's group_type for this section+subject (A/B = half-group, C or null = full group)
        $groupType = null;
        if ($exam->subject_id) {
            $groupType = $sicore->table('schedules')
                ->where('user_id', Auth::id())
                ->where('section_id', $section->id)
                ->where('subject_id', $exam->subject_id)
                ->where('year_id', $examYear->id)
                ->value('group_type');
        }

        // Fetch enrolled students: condition regular or repitente, optionally filtered by sub_grupo
        $sectionStudentQuery = $sicore->table('section_student_year')
            ->where('section_id', $section->id)
            ->where('year', $yearValue)
            ->whereIn('condition', ['regular', 'repitente']);

        if ($groupType && in_array(strtoupper($groupType), ['A', 'B'])) {
            $sectionStudentQuery->where('sub_grupo', strtoupper($groupType));
        }

        $sectionStudentIds = $sectionStudentQuery->pluck('student_id');

        // Further filter to students enrolled in the exam's subject for this year
        if ($exam->subject_id && $sectionStudentIds->isNotEmpty()) {
            $subjectStudentIds = $sicore->table('student_subject_year')
                ->where('subject_id', $exam->subject_id)
                ->where('year', $yearValue)
                ->whereIn('student_id', $sectionStudentIds)
                ->pluck('student_id');
            $studentIds = $subjectStudentIds;
        } else {
            $studentIds = $sectionStudentIds;
        }

        $students = Student::whereIn('id', $studentIds)->orderBy('name')->get(['id', 'name', 'last_name_1', 'last_name_2', 'cedula']);

        $codedIds = ExamAccessCode::where('exam_id', $exam->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->flip();

        return response()->json([
            'students' => $students->map(fn($s) => [
                'id'       => $s->id,
                'name'     => $s->full_name,
                'cedula'   => $s->cedula,
                'has_code' => $codedIds->has($s->id),
            ]),
        ]);
    }

    public function generateCodeForStudent(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $request->validate(['student_id' => 'required|integer']);

        $existing = ExamAccessCode::where('exam_id', $exam->id)
            ->where('student_id', $request->student_id)->first();

        if ($existing) {
            return back()->with('info', "El estudiante ya tiene código asignado: {$existing->code}");
        }

        $code = ExamAccessCode::create([
            'exam_id'    => $exam->id,
            'student_id' => $request->student_id,
            'code'       => $this->generateUniqueCode(),
            'expires_at' => $exam->available_until,
        ]);

        return back()->with('success', "Código generado: {$code->code}");
    }

    /**
     * Regenerate access codes. Scope: 'one'/'selected' (needs code_ids) or 'all'.
     * Codes whose student already started or finished the exam are skipped, so a
     * regeneration never invalidates an in-progress or completed attempt's access.
     */
    public function regenerateCodes(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $scope = $request->input('scope', 'selected');

        $query = ExamAccessCode::where('exam_id', $exam->id);
        if ($scope !== 'all') {
            $validated = $request->validate([
                'code_ids'   => 'required|array|min:1',
                'code_ids.*' => 'integer',
            ], ['code_ids.required' => 'Selecciona al menos un código para regenerar.']);
            $query->whereIn('id', $validated['code_ids']);
        }

        $codes = $query->get();
        if ($codes->isEmpty()) {
            return back()->with('info', 'No hay códigos para regenerar.');
        }

        // Codes blocked: the student already has an attempt (in progress or finished)
        $blocked = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('access_code_id', $codes->pluck('id'))
            ->pluck('access_code_id')
            ->unique()
            ->flip();

        $regenerated = 0;
        $skipped     = 0;
        foreach ($codes as $code) {
            if ($blocked->has($code->id)) {
                $skipped++;
                continue;
            }
            $code->code = $this->generateUniqueCode();
            $code->save();
            $regenerated++;
        }

        $msg = "Se regeneraron {$regenerated} código(s).";
        if ($skipped > 0) {
            $msg .= " {$skipped} se omitieron porque el estudiante ya inició o realizó el examen.";
        }

        return back()->with($regenerated > 0 ? 'success' : 'info', $msg);
    }

    /**
     * Set per-student extra exam time (accommodations). Applies to the access
     * code, so the student's attempt timer = exam duration + extra_minutes.
     */
    public function setExtraTime(Request $request, Exam $exam, ExamAccessCode $code)
    {
        $this->authorizeExam($exam);

        if ($code->exam_id !== $exam->id) {
            abort(404);
        }

        $validated = $request->validate([
            'extra_minutes' => 'required|integer|min:0|max:240',
        ], [
            'extra_minutes.max' => 'El tiempo extra no puede superar 240 minutos.',
        ]);

        $code->extra_minutes = $validated['extra_minutes'];
        $code->save();

        $student = Student::find($code->student_id);
        $name    = $student?->full_name ?? 'el estudiante';
        $msg = $validated['extra_minutes'] > 0
            ? "Se asignaron +{$validated['extra_minutes']} min de tiempo extra a {$name}."
            : "Se quitó el tiempo extra a {$name}.";

        return back()->with('success', $msg);
    }

    /**
     * Alphabet for access codes: NO vowels (can't spell real words) and no
     * confusing characters (0/O, 1/I/L) → safe from offensive words & legible.
     */
    private const CODE_ALPHABET = 'BCDFGHJKMNPQRSTVWXYZ23456789';

    /**
     * Defense-in-depth blacklist. Offensive substrings (uppercase, hyphen
     * stripped) that force the code to be regenerated. Most can't even form
     * with a vowel-less alphabet, but this protects against edge cases.
     */
    private const CODE_BLACKLIST = [
        'KKK', 'XXX', 'SEX', 'SXX',
        // Español
        'PTA', 'PTO', 'KLO', 'VRG', 'CGR', 'MMN', 'MMD', 'PRR', 'ZRR', 'JTO',
        'MRC', 'CBRN', 'CRJ', 'PCH', 'CHMB', 'GNRR', 'MLPRD', 'PNDJ',
        // Inglés
        'FCK', 'SHT', 'CNT', 'DCK', 'NGGR', 'FAG', 'WTF', 'NZI',
    ];

    protected function generateUniqueCode(): string
    {
        do {
            $code = $this->randomCodeSegment(4) . '-' . $this->randomCodeSegment(4);
        } while ($this->codeHasBlacklistedWord($code) || ExamAccessCode::where('code', $code)->exists());

        return $code;
    }

    /** Build a random segment from the safe alphabet using a CSPRNG. */
    private function randomCodeSegment(int $length): string
    {
        $alphabet = self::CODE_ALPHABET;
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    /** True if the code contains a blacklisted substring (hyphen ignored). */
    private function codeHasBlacklistedWord(string $code): bool
    {
        $flat = str_replace('-', '', strtoupper($code));
        foreach (self::CODE_BLACKLIST as $bad) {
            if (str_contains($flat, $bad)) {
                return true;
            }
        }
        return false;
    }

    // ── Preview (teacher draft view) ───────────────────────────────────────

    public function preview(Exam $exam)
    {
        $this->authorizeExam($exam);

        $questions = ExamQuestion::with(['options' => fn($q) => $q->orderBy('order')])
            ->where('exam_id', $exam->id)
            ->orderBy('order')
            ->get();

        $institution = \Illuminate\Support\Facades\DB::connection('sicore')
            ->table('institution_settings')
            ->orderByDesc('id')
            ->value('name') ?? '';

        $subject = $exam->subject_id ? Subject::find($exam->subject_id) : null;

        // ── Render the REAL student exam view in preview mode (faithful preview) ─
        // Build in-memory stubs (no DB writes) for $attempt and $accessCode so the
        // view renders without needing real records.
        $attempt = new \App\Models\ExamAttempt([
            'exam_id'    => $exam->id,
            'student_id' => 0,
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);
        $attempt->id = 0;
        $attempt->setRelation('exam', $exam);

        $accessCode = new ExamAccessCode([
            'exam_id'    => $exam->id,
            'student_id' => 0,
            'code'       => 'PREVIEW',
        ]);
        $accessCode->id = 0;

        $existingAnswers = [];
        $textAnswers     = [];
        $sectionName     = null;
        $previewMode     = true;

        return view('student.exam', compact(
            'exam', 'attempt', 'questions', 'existingAnswers', 'textAnswers',
            'accessCode', 'institution', 'subject', 'sectionName', 'previewMode'
        ));
    }

    // ── Results ────────────────────────────────────────────────────────────

    public function results(Exam $exam, Request $request)
    {
        $this->authorizeExam($exam);

        // Auto-close any in-progress attempt whose time already elapsed (student abandoned)
        \App\Models\ExamAttempt::closeTimedOutForExam($exam->id);

        $sectionId = $request->input('section_id') ?: null;
        $sectionId = $sectionId ? (int) $sectionId : null;

        $attemptsQuery = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->orderByDesc('submitted_at');

        $sectionFilterLabel = null;
        if ($sectionId && $exam->year_id) {
            $year = Year::find($exam->year_id);
            if ($year) {
                $sectionStudentIds = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('section_student_year')
                    ->where('section_id', $sectionId)
                    ->where('year', $year->year)
                    ->pluck('student_id');
                $attemptsQuery->whereIn('student_id', $sectionStudentIds);
                $sectionFilterLabel = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('sections')->where('id', $sectionId)->value('name');
            }
        }

        $attempts = $attemptsQuery->get();

        $studentIds = $attempts->pluck('student_id')->unique();
        $students   = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $subject = $exam->subject_id ? Subject::find($exam->subject_id) : null;

        // Sections with attempts (for the filter dropdown) — derived from ALL attempts
        $resultSections = $this->resultSectionsForExam($exam);

        // Detect attempts with ungraded short answers (pending manual grading)
        $shortAnswerQIds = ExamQuestion::where('exam_id', $exam->id)
            ->where('type', 'short_answer')
            ->pluck('id');

        $pendingTotal = 0;
        if ($shortAnswerQIds->isNotEmpty() && $attempts->isNotEmpty()) {
            $pendingByAttempt = \App\Models\ExamAttemptAnswer::whereIn('attempt_id', $attempts->pluck('id'))
                ->whereIn('question_id', $shortAnswerQIds)
                ->whereNull('is_correct')
                ->whereNotNull('text_answer')
                ->selectRaw('attempt_id, count(*) as cnt')
                ->groupBy('attempt_id')
                ->pluck('cnt', 'attempt_id');
            $attempts->each(fn($a) => $a->pending_grading = $pendingByAttempt[$a->id] ?? 0);
            $pendingTotal = $pendingByAttempt->sum();
        } else {
            $attempts->each(fn($a) => $a->pending_grading = 0);
        }

        // ── Analytics (on the filtered attempts) ────────────────────────────
        $analytics = $this->buildResultsAnalytics($exam, $attempts);

        return view('exams.results', compact(
            'exam', 'attempts', 'students', 'subject', 'pendingTotal', 'analytics',
            'resultSections', 'sectionId', 'sectionFilterLabel'
        ));
    }

    /** Sections (from SICORE) that have at least one attempt in this exam. */
    protected function resultSectionsForExam(Exam $exam): \Illuminate\Support\Collection
    {
        if (!$exam->year_id) return collect();
        $year = Year::find($exam->year_id);
        if (!$year) return collect();

        $studentIds = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->pluck('student_id')->unique();
        if ($studentIds->isEmpty()) return collect();

        return \Illuminate\Support\Facades\DB::connection('sicore')
            ->table('section_student_year as ssy')
            ->join('sections as s', 's.id', '=', 'ssy.section_id')
            ->whereIn('ssy.student_id', $studentIds)
            ->where('ssy.year', $year->year)
            ->select('s.id', 's.name')
            ->distinct()
            ->orderBy('s.name')
            ->get();
    }

    // ── SICORE grade sync ────────────────────────────────────────────────────

    public function syncGrades(Exam $exam)
    {
        $this->authorizeExam($exam);

        if (empty($exam->linkedComponentIds())) {
            return back()->with('error', 'Este examen no está vinculado a ningún componente de SICORE.');
        }

        // Guard: block while there are manual answers still pending grading
        $manualQIds = ExamQuestion::where('exam_id', $exam->id)
            ->whereIn('type', \App\Models\ExamQuestion::RUBRIC_TYPES)
            ->pluck('id');

        if ($manualQIds->isNotEmpty()) {
            $finishedAttemptIds = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->pluck('id');

            $pending = \App\Models\ExamAttemptAnswer::whereIn('attempt_id', $finishedAttemptIds)
                ->whereIn('question_id', $manualQIds)
                ->whereNull('points_earned')
                ->whereNotNull('text_answer')
                ->where('text_answer', '!=', '')
                ->count();

            if ($pending > 0) {
                return back()->with('error', "Hay {$pending} respuesta(s) pendiente(s) de calificar. Califícalas antes de sincronizar las notas.");
            }
        }

        // Dispatch async — the sync touches another DB and is per-student heavy.
        $op = \App\Models\BackgroundOperation::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'exam_id' => $exam->id,
            'type'    => 'sync_grades',
            'status'  => 'pending',
            'message' => 'En cola para sincronizar…',
        ]);
        \App\Jobs\SyncSicoreGradesJob::dispatch($op->id, $exam->id);

        return back()->with('info', 'Sincronización en curso. Recargá en unos segundos para ver el resultado.');
    }

    // ── Live monitoring ──────────────────────────────────────────────────────

    public function monitor(Exam $exam)
    {
        $this->authorizeExam($exam);
        $subject = $exam->subject_id ? Subject::find($exam->subject_id) : null;
        return view('exams.monitor', compact('exam', 'subject'));
    }

    /** JSON snapshot of in-progress attempts for the live monitor (polled). */
    public function monitorData(Exam $exam)
    {
        $this->authorizeExam($exam);

        // Auto-close abandoned attempts whose time already elapsed
        \App\Models\ExamAttempt::closeTimedOutForExam($exam->id);

        $inProgress = \App\Models\ExamAttempt::with(['accessCode', 'exam'])
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->get();

        $attemptIds = $inProgress->pluck('id');

        // Count "answered" responses per attempt (a real selection or non-empty text)
        $answered = \App\Models\ExamAttemptAnswer::whereIn('attempt_id', $attemptIds)
            ->where(function ($q) {
                $q->whereNotNull('option_id')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('text_answer')
                         ->where('text_answer', '!=', '')
                         ->where('text_answer', '!=', '[]')
                         ->where('text_answer', '!=', '{}');
                  });
            })
            ->selectRaw('attempt_id, count(*) as c')
            ->groupBy('attempt_id')
            ->pluck('c', 'attempt_id');

        $studentIds = $inProgress->pluck('student_id')->unique();
        $students   = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $fallbackTotal = $exam->questions_per_exam ?: ExamQuestion::where('exam_id', $exam->id)->count();

        $rows = $inProgress->map(function ($a) use ($answered, $students, $fallbackTotal) {
            $total = (is_array($a->question_order) && count($a->question_order))
                ? count($a->question_order)
                : $fallbackTotal;

            // Last screen_leave incident — to know which question they were on
            $lastLeave = null;
            foreach (array_reverse($a->cheat_flags ?? []) as $f) {
                if (($f['type'] ?? null) === 'screen_leave' && !empty($f['question_index'])) {
                    $lastLeave = $f; break;
                }
            }

            return [
                'id'             => $a->id,
                'student'        => $students[$a->student_id]->full_name ?? ('ID ' . $a->student_id),
                'answered'       => (int) ($answered[$a->id] ?? 0),
                'total'          => $total,
                'remaining'      => $a->remaining_seconds,
                'focus_loss'     => (int) ($a->focus_loss_count ?? 0),
                'started_at'     => $a->started_at?->copy()->setTimezone('America/Costa_Rica')->format('H:i'),
                'paused'         => (bool) $a->paused_at,
                'last_q_id'      => $lastLeave['question_id'] ?? null,
                'last_q_index'   => $lastLeave['question_index'] ?? null,
            ];
        })->sortBy([['paused', 'desc'], ['focus_loss', 'desc']])->values();

        $submittedCount = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        return response()->json([
            'in_progress'       => $rows,
            'in_progress_count' => $rows->count(),
            'submitted_count'   => $submittedCount,
            'total_codes'       => ExamAccessCode::where('exam_id', $exam->id)->count(),
            'server_time'       => now()->setTimezone('America/Costa_Rica')->format('H:i:s'),
        ]);
    }

    /**
     * Build statistics for the results dashboard: median, lowest score,
     * grade distribution (deciles) and per-question item analysis.
     */
    protected function buildResultsAnalytics(Exam $exam, $attempts): array
    {
        $submitted = $attempts->whereIn('status', ['submitted', 'timed_out']);

        $percentages = $submitted->pluck('percentage')
            ->filter(fn($p) => $p !== null)
            ->map(fn($p) => (float) $p)
            ->sort()
            ->values();

        // Median & lowest
        $median = null;
        $lowest = null;
        if ($percentages->count()) {
            $cnt = $percentages->count();
            $mid = intdiv($cnt, 2);
            $median = $cnt % 2
                ? round($percentages[$mid], 1)
                : round(($percentages[$mid - 1] + $percentages[$mid]) / 2, 1);
            $lowest = round($percentages->first(), 1);
        }

        // Grade distribution: 10 deciles (0-9, 10-19, …, 90-100)
        $distribution = array_fill(0, 10, 0);
        foreach ($percentages as $p) {
            $bucket = min(9, (int) floor($p / 10));
            $distribution[$bucket]++;
        }
        $distMax = count($distribution) ? max($distribution) : 0;

        // Item analysis: average score ratio per question
        $itemStats = [];
        if ($submitted->isNotEmpty()) {
            $examQuestions = ExamQuestion::where('exam_id', $exam->id)->orderBy('order')->get();
            $answersByQ = \App\Models\ExamAttemptAnswer::whereIn('attempt_id', $submitted->pluck('id'))
                ->get()
                ->groupBy('question_id');

            foreach ($examQuestions as $q) {
                $ans      = $answersByQ->get($q->id, collect());
                $answered = $ans->count();
                $graded   = $ans->filter(fn($a) => $a->points_earned !== null);
                $avgPct   = ($graded->count() && $q->points > 0)
                    ? round($graded->avg(fn($a) => $a->points_earned / $q->points) * 100, 1)
                    : null;

                $itemStats[] = [
                    'id'       => $q->id,
                    'text'     => \Illuminate\Support\Str::limit(trim(strip_tags($q->question_text ?? '')), 90) ?: '(sin enunciado)',
                    'type'     => $q->type,
                    'points'   => $q->points,
                    'answered' => $answered,
                    'avg'      => $avgPct,
                ];
            }
        }

        return [
            'median'       => $median,
            'lowest'       => $lowest,
            'distribution' => $distribution,
            'distMax'      => $distMax,
            'itemStats'    => $itemStats,
            'gradedCount'  => $percentages->count(),
        ];
    }

    public function exportCodesPdf(Exam $exam, \Illuminate\Http\Request $request)
    {
        $this->authorizeExam($exam);

        $sectionId = $request->input('section_id') ?: null;
        $sectionId = $sectionId ? (int) $sectionId : null;

        $accessCodesQuery = ExamAccessCode::where('exam_id', $exam->id)->orderBy('id');

        // If a section filter is provided → restrict to students of that section in the exam's year
        $sectionFilterLabel = null;
        if ($sectionId) {
            $year = $exam->year_id ? Year::find($exam->year_id) : null;
            if ($year) {
                $sectionStudentIds = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('section_student_year')
                    ->where('section_id', $sectionId)
                    ->where('year', $year->year)
                    ->pluck('student_id');
                $accessCodesQuery->whereIn('student_id', $sectionStudentIds);
                $sectionFilterLabel = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('sections')->where('id', $sectionId)->value('name');
            }
        }

        $accessCodes = $accessCodesQuery->get();
        $studentIds  = $accessCodes->pluck('student_id')->unique();
        $students    = Student::whereIn('id', $studentIds)->get()->keyBy('id');
        $subject     = $exam->subject_id ? Subject::find($exam->subject_id) : null;

        // Try to get section names for each student in the exam's year
        $sectionNames = collect();
        if ($exam->year_id) {
            $year = Year::find($exam->year_id);
            if ($year) {
                $rows = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('section_student_year as ssy')
                    ->join('sections as s', 's.id', '=', 'ssy.section_id')
                    ->whereIn('ssy.student_id', $studentIds)
                    ->where('ssy.year', $year->year)
                    ->select('ssy.student_id', 's.name as section_name')
                    ->get();
                $sectionNames = $rows->pluck('section_name', 'student_id');
            }
        }

        // Preload last attempt per code
        $lastAttempts = \App\Models\ExamAttempt::whereIn('access_code_id', $accessCodes->pluck('id'))
            ->whereIn('status', ['submitted', 'timed_out'])
            ->orderByDesc('submitted_at')
            ->get()
            ->unique('access_code_id')
            ->keyBy('access_code_id');

        $format      = $request->input('format', 'list'); // 'list' | 'slips' | 'padron'
        // Try to get institution name from sicore's settings table
        try {
            $institution = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('institution_settings')
                ->value('institution_name') ?? 'SICORE';
        } catch (\Throwable) {
            $institution = 'SICORE';
        }
        $entryUrl    = route('student.entry');

        $suffix   = match($format) {
            'slips'  => 'tiquetes',
            'padron' => 'padron',
            default  => 'codigos',
        };
        $sectionPart = $sectionFilterLabel ? '_' . Str::slug($sectionFilterLabel) : '';
        $filename    = Str::slug($exam->title) . '_' . $suffix . $sectionPart . '_' . now()->format('Ymd') . '.pdf';
        $orientation = 'portrait'; // always portrait

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exams.codes-pdf', compact(
            'exam', 'accessCodes', 'students', 'subject',
            'sectionNames', 'lastAttempts', 'format', 'institution', 'entryUrl'
        ))->setPaper('letter', $orientation);

        return $pdf->download($filename);
    }

    public function exportResults(Exam $exam, Request $request)
    {
        $this->authorizeExam($exam);

        $sectionId = $request->input('section_id') ?: null;
        $sectionId = $sectionId ? (int) $sectionId : null;

        $query = \App\Models\ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->orderByDesc('submitted_at');

        $sectionLabel = null;
        if ($sectionId && $exam->year_id) {
            $year = Year::find($exam->year_id);
            if ($year) {
                $secStudentIds = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('section_student_year')
                    ->where('section_id', $sectionId)
                    ->where('year', $year->year)
                    ->pluck('student_id');
                $query->whereIn('student_id', $secStudentIds);
                $sectionLabel = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('sections')->where('id', $sectionId)->value('name');
            }
        }

        $attempts   = $query->get();
        $studentIds = $attempts->pluck('student_id')->unique();
        $students   = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $subject     = $exam->subject_id ? Subject::find($exam->subject_id) : null;
        $sectionPart = $sectionLabel ? '_' . Str::slug($sectionLabel) : '';
        $filename    = Str::slug($exam->title) . '_resultados' . $sectionPart . '_' . now()->format('Ymd') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ExamResultsExport(
                $attempts,
                $students,
                (float) $exam->passing_score,
                $exam->title,
                $subject?->name
            ),
            $filename
        );
    }

    public function reorderQuestions(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        if (!$exam->canBeEdited()) {
            return response()->json(['error' => 'No se puede reordenar con intentos activos.'], 422);
        }

        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($request->order as $position => $questionId) {
            ExamQuestion::where('id', $questionId)
                ->where('exam_id', $exam->id) // security: only own questions
                ->update(['order' => $position + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function attemptDetail(Exam $exam, \App\Models\ExamAttempt $attempt)
    {
        $this->authorizeExam($exam);

        $answers  = \App\Models\ExamAttemptAnswer::where('attempt_id', $attempt->id)->get();
        $questionIds = $answers->pluck('question_id')->unique();
        $questions   = ExamQuestion::with('options')->whereIn('id', $questionIds)->get()->keyBy('id');
        $student  = Student::find($attempt->student_id);

        return view('exams.attempt-detail', compact('exam', 'attempt', 'answers', 'questions', 'student'));
    }

    /**
     * Resume a paused (strict-proctoring) attempt. Optionally void the answer
     * for a specific question (penalty for confirmed cheating).
     */
    public function resumePausedAttempt(Request $request, Exam $exam, \App\Models\ExamAttempt $attempt)
    {
        $this->authorizeExam($exam);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }
        if (!$attempt->paused_at) {
            return back()->with('info', 'Este intento no está pausado.');
        }

        $voidQuestionId = $request->input('void_question_id');

        $voidedMsg = '';
        if ($voidQuestionId) {
            $answer = \App\Models\ExamAttemptAnswer::where('attempt_id', $attempt->id)
                ->where('question_id', $voidQuestionId)
                ->first();
            if ($answer) {
                $answer->voided        = true;
                $answer->points_earned = 0;
                $answer->is_correct    = false;
                $answer->save();
            } else {
                // No answer row yet — create a voided placeholder so the question
                // counts in max_score with 0 earned at grading time.
                \App\Models\ExamAttemptAnswer::create([
                    'attempt_id'   => $attempt->id,
                    'question_id'  => (int) $voidQuestionId,
                    'voided'       => true,
                    'points_earned'=> 0,
                    'is_correct'   => false,
                ]);
            }
            $voidedMsg = ' La pregunta #' . (int) $voidQuestionId . ' fue anulada (0 pts).';
        }

        $attempt->paused_at = null;
        $attempt->save();

        return back()->with('success', 'Examen reanudado.' . $voidedMsg);
    }

    /**
     * Force-close an in-progress attempt (student abandoned the exam).
     * Grades whatever they saved and marks the attempt as timed_out.
     */
    public function closeAttempt(Exam $exam, \App\Models\ExamAttempt $attempt)
    {
        $this->authorizeExam($exam);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }
        if ($attempt->status !== 'in_progress') {
            return back()->with('info', 'Este intento ya estaba cerrado.');
        }

        $attempt->gradeAndSubmit(true);

        return back()->with('success', 'Intento cerrado y calificado con las respuestas que el estudiante alcanzó a guardar.');
    }

    /** Individual student report card (PDF) for one attempt. */
    public function attemptPdf(Exam $exam, \App\Models\ExamAttempt $attempt)
    {
        $this->authorizeExam($exam);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        $student = Student::find($attempt->student_id);
        $subject = $exam->subject_id ? Subject::find($exam->subject_id) : null;
        $teacher = $exam->user_id ? \App\Models\User::find($exam->user_id) : null;

        try {
            $institution = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('institution_settings')
                ->value('institution_name') ?? 'SICORE';
        } catch (\Throwable) {
            $institution = 'SICORE';
        }

        $sectionName = null;
        if ($student) {
            $sectionName = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('section_student_year as ssy')
                ->join('sections', 'sections.id', '=', 'ssy.section_id')
                ->where('ssy.student_id', $student->id)
                ->orderByDesc('ssy.year')
                ->value('sections.name');
        }

        // Questions in the order the student saw them
        $order = $attempt->question_order ?: ExamQuestion::where('exam_id', $exam->id)
            ->orderBy('order')->pluck('id')->toArray();
        $questions = ExamQuestion::with('options')->whereIn('id', $order)->get()->keyBy('id');
        $answers   = \App\Models\ExamAttemptAnswer::where('attempt_id', $attempt->id)->get()->keyBy('question_id');

        $report = [];
        $num = 0;
        foreach ($order as $qid) {
            $q = $questions->get($qid);
            if (!$q) continue;
            $num++;
            $ans      = $answers->get($qid);
            $earned   = $ans?->points_earned;
            $isManual = in_array($q->type, ExamQuestion::RUBRIC_TYPES);
            $hasAnswer = $ans && ($ans->option_id || ($ans->text_answer !== null && $ans->text_answer !== ''));

            if ($earned === null) {
                $status = $isManual ? 'pending' : ($hasAnswer ? 'incorrect' : 'blank');
            } elseif ($earned >= $q->points) {
                $status = 'correct';
            } elseif ($earned > 0) {
                $status = 'partial';
            } else {
                $status = $hasAnswer ? 'incorrect' : 'blank';
            }

            [$studentStr, $correctStr] = $this->formatReportAnswer($q, $ans);

            $report[] = [
                'num'        => $num,
                'text'       => trim(strip_tags($q->question_text ?? '')) ?: '(sin enunciado)',
                'type_label' => \App\Models\ExamQuestion::TYPES[$q->type] ?? $q->type,
                'points'     => (float) $q->points,
                'earned'     => $earned !== null ? (float) $earned : null,
                'status'     => $status,
                'student'    => $studentStr,
                'correct'    => $correctStr,
                'feedback'   => $ans?->feedback,
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exams.attempt-pdf', compact(
            'exam', 'attempt', 'student', 'subject', 'teacher', 'institution', 'sectionName', 'report'
        ))->setPaper('letter', 'portrait');

        $filename = Str::slug(($student?->full_name ?? 'estudiante') . '-' . $exam->title) . '_boletin.pdf';
        return $pdf->download($filename);
    }

    /** Build [studentAnswer, correctAnswer] strings for the report (per type). */
    private function formatReportAnswer(ExamQuestion $q, ?\App\Models\ExamAttemptAnswer $ans): array
    {
        if (!$ans) {
            return [null, $this->correctAnswerString($q)];
        }

        switch ($q->type) {
            case 'single_choice':
            case 'multiple_choice':
            case 'true_false':
                $opt = $q->options->firstWhere('id', $ans->option_id);
                return [$opt?->option_text, $this->correctAnswerString($q)];

            case 'multiple_select':
                $ids = json_decode($ans->text_answer ?? '[]', true) ?: [];
                $sel = $q->options->whereIn('id', $ids)->pluck('option_text')->implode(', ');
                return [$sel !== '' ? $sel : null, $this->correctAnswerString($q)];

            case 'short_answer':
            case 'restricted_response':
            case 'exercise':
            case 'written_production':
                return [$ans->text_answer !== '' ? $ans->text_answer : null, null];

            default: // matching / ordering / identification / completion
                $has = $ans->text_answer !== null && $ans->text_answer !== '' && $ans->text_answer !== '[]' && $ans->text_answer !== '{}';
                return [$has ? 'Respondido' : null, null];
        }
    }

    /** Correct-answer string for auto-graded choice/select/true-false. */
    private function correctAnswerString(ExamQuestion $q): ?string
    {
        return match ($q->type) {
            'single_choice', 'multiple_choice', 'true_false', 'multiple_select'
                => $q->options->where('is_correct', true)->pluck('option_text')->implode(', ') ?: null,
            default => null,
        };
    }

    public function gradeAnswer(Request $request, Exam $exam, \App\Models\ExamAttempt $attempt, \App\Models\ExamAttemptAnswer $answer)
    {
        $this->authorizeExam($exam);

        $question = ExamQuestion::findOrFail($answer->question_id);

        $request->validate([
            'points_earned'           => "nullable|numeric|min:0|max:{$question->points}",
            'feedback'                => 'nullable|string|max:2000',
            'rubric_choices'          => 'nullable|array',
            'rubric_choices.*'        => 'nullable|integer|min:0',
        ]);

        $gradingChoices = null;
        $rubric         = $question->rubric;
        $usingRubric    = is_array($rubric) && !empty($rubric['levels']) && !empty($rubric['criteria'])
                          && $request->has('rubric_choices');

        if ($usingRubric) {
            // Compute total from rubric choices (one level index per criterion)
            $choices = (array) $request->input('rubric_choices', []);
            $points  = 0.0;
            $clean   = [];
            foreach ($rubric['criteria'] as $i => $crit) {
                $lvlIdx = isset($choices[$i]) && $choices[$i] !== '' && $choices[$i] !== null
                    ? (int) $choices[$i] : null;
                if ($lvlIdx !== null && isset($rubric['levels'][$lvlIdx])) {
                    $points += (float) ($rubric['levels'][$lvlIdx]['points'] ?? 0);
                    $clean[$i] = $lvlIdx;
                }
            }
            // Cap at the question's max points
            $points = min($points, (float) $question->points);
            $gradingChoices = $clean;
        } else {
            $points = round((float) $request->input('points_earned', 0), 2);
        }

        $answer->points_earned   = round($points, 2);
        $answer->is_correct      = $answer->points_earned >= $question->points;
        $answer->feedback        = $request->input('feedback') ?: null;
        $answer->grading_choices = $gradingChoices;
        $answer->save();

        // Recalculate attempt totals
        $allAnswers = $attempt->answers()->with('question')->get();
        $earned = $allAnswers->sum(fn($a) => $a->points_earned ?? 0);
        $max    = $allAnswers->sum(fn($a) => $a->question?->points ?? 0);

        $attempt->score      = round($earned, 2);
        $attempt->max_score  = $max;
        $attempt->percentage = $max > 0 ? round(($earned / $max) * 100, 2) : 0;
        $attempt->save();

        return response()->json([
            'points_earned' => $points,
            'max_points'    => $question->points,
            'attempt_score' => $attempt->score,
            'attempt_max'   => $attempt->max_score,
            'percentage'    => $attempt->percentage,
            'feedback'      => $answer->feedback,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function validateExamRequest(Request $request): array
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'instructions'       => 'nullable|string|max:2000',
            'subject_id'         => 'nullable|integer',
            'level_id'           => 'nullable|integer',
            'year_id'            => 'nullable|integer',
            'duration_minutes'   => 'required|integer|min:1|max:480',
            'available_from'     => 'nullable|date',
            'available_until'    => 'nullable|date|after_or_equal:available_from',
            'shuffle_questions'  => 'nullable',
            'shuffle_answers'    => 'nullable',
            'max_attempts'       => 'required|integer|min:1|max:10',
            'show_results'       => 'nullable',
            'show_correct_answers' => 'nullable',
            'proctoring'         => 'nullable',
            'proctoring_strict'  => 'nullable',
            'proctoring_threshold' => 'nullable|integer|min:1|max:10',
            'passing_score'      => 'required|numeric|min:0|max:100',
            'questions_per_exam' => 'nullable|integer|min:1',
            'status'             => 'required|in:draft,active,closed',
            'activity_type'      => 'nullable|in:exam,quiz,assignment,project,lab,presentation',
            'evaluation_component_ids'   => 'nullable|array',
            'evaluation_component_ids.*' => 'integer',
        ]);

        $validated['shuffle_questions']    = $request->boolean('shuffle_questions');
        $validated['shuffle_answers']      = $request->boolean('shuffle_answers');
        $validated['show_results']         = $request->boolean('show_results');
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers');
        $validated['proctoring']           = $request->boolean('proctoring');
        $validated['proctoring_strict']    = $request->boolean('proctoring_strict');
        $validated['proctoring_threshold'] = max(1, min(10, (int) ($request->input('proctoring_threshold') ?: 2)));
        $validated['activity_type']        = $request->input('activity_type', 'exam');

        // The SICORE component links are NOT a column — handled via syncExamComponents()
        unset($validated['evaluation_component_ids']);

        // Block activation when required media is missing on any question
        if ($validated['status'] === 'active') {
            $examId = request()->route('exam')?->id;
            if ($examId) {
                $missing = ExamQuestion::where('exam_id', $examId)
                    ->where('media_type', '!=', 'none')
                    ->get()
                    ->filter(fn($q) => $q->mediaIsMissing());

                if ($missing->isNotEmpty()) {
                    $list = $missing->map(fn($q) => '"'.mb_substr($q->question_text, 0, 40).'"')->implode(', ');
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => "No se puede activar el examen: las siguientes preguntas requieren media (imagen/audio/video) que no ha sido subida: {$list}",
                    ]);
                }
            }
        }

        return $validated;
    }

    /**
     * Returns [subjects collection, bool $wasFiltered].
     * Uses user_subject_year pivot filtered by the active year.
     * Falls back to all subjects when no assignments exist (dev/test mode).
     */
    /**
     * Agrupa una colección de Subject por el campo `ciclo`.
     * Devuelve un array asociativo ['Ciclo III' => Collection, 'General' => Collection, ...].
     * Los subjects sin ciclo van al grupo 'General'.
     */
    protected function groupSubjectsByCiclo(\Illuminate\Support\Collection $subjects): array
    {
        $groups = [];
        foreach ($subjects as $subject) {
            $key = (string) ($subject->ciclo ?: 'General');
            $groups[$key][] = $subject;
        }
        ksort($groups);
        // Put 'General' last if it exists
        if (isset($groups['General']) && count($groups) > 1) {
            $general = $groups['General'];
            unset($groups['General']);
            $groups['General'] = $general;
        }
        return $groups;
    }

    /**
     * SICORE evaluation components owned by the current teacher, filtered by
     * the desired SICORE evaluation type (TESTS, PROJECT, DAILY_WORK, HOMEWORK).
     * Components already linked to a DIFFERENT exam get a `taken_by_title`
     * and `taken_by_exam_id` so the UI can show them disabled with a link.
     */
    protected function teacherComponents(?string $sicoreType, ?Exam $exam = null, ?int $subjectId = null, ?int $levelId = null): \Illuminate\Support\Collection
    {
        if (empty($sicoreType)) {
            return collect();
        }

        $sicore = \Illuminate\Support\Facades\DB::connection('sicore');

        $activeYear = Year::where('status', 1)->orderBy('year', 'desc')->first();
        $yearValue  = $exam?->year_id ? optional(Year::find($exam->year_id))->year : $activeYear?->year;
        $subjectId  = $subjectId ?: $exam?->subject_id;

        // Without a subject the link to SICORE has no meaning (teachers can't know
        // which sections they'd teach for an undefined subject) → formative only.
        if (!$subjectId) {
            return collect();
        }

        // Map: evaluation_component_id => ['title' => …, 'exam_id' => …]
        $takenMap = [];
        $otherLinks = \App\Models\ExamEvaluationComponent::with('exam:id,title')
            ->when($exam, fn($q) => $q->where('exam_id', '!=', $exam->id))
            ->get();
        foreach ($otherLinks as $link) {
            $takenMap[$link->evaluation_component_id] = [
                'title'   => $link->exam?->title ?? '(otro examen)',
                'exam_id' => $link->exam_id,
            ];
        }

        $components = $sicore->table('evaluation_components as ec')
            ->join('evaluation_subject_year as esy', 'esy.id', '=', 'ec.evaluation_subject_year_id')
            ->join('evaluations as e', 'e.id', '=', 'esy.evaluation_id')
            ->join('subjects as s', 's.id', '=', 'esy.subject_id')
            ->leftJoin('periods as p', 'p.id', '=', 'ec.period_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'ec.section_id')
            ->where('ec.user_id', Auth::id())
            ->where('e.type', $sicoreType)
            ->when($yearValue, fn($q) => $q->where('esy.year', $yearValue))
            ->when($subjectId, fn($q) => $q->where('esy.subject_id', $subjectId))
            ->select(
                'ec.id', 'ec.name', 'ec.value', 'ec.max_points', 'ec.group_type',
                's.name as subject_name', 'e.name as evaluation_name', 'e.percentage as evaluation_pct',
                'p.name as period_name', 'sec.name as section_name'
            )
            ->orderBy('sec.name')
            ->orderBy('s.name')
            ->orderByDesc('ec.id')
            ->get();

        return $components->map(function ($c) use ($takenMap) {
            $info                     = $takenMap[$c->id] ?? null;
            $c->taken_by_title        = $info['title'] ?? null;
            $c->taken_by_exam_id      = $info['exam_id'] ?? null;
            return $c;
        });
    }

    /** AJAX endpoint: components for a given activity_type (used on type change). */
    public function componentsByType(Request $request)
    {
        $activityType = $request->input('activity_type', 'exam');
        $sicoreType   = Exam::sicoreTypeFor($activityType);
        $subjectId    = $request->input('subject_id') ?: null;
        $subjectId    = $subjectId ? (int) $subjectId : null;
        $levelId      = $request->input('level_id') ?: null;
        $levelId      = $levelId ? (int) $levelId : null;

        $exam = null;
        if ($id = $request->input('exam_id')) {
            $exam = Exam::find($id);
            if ($exam && $exam->user_id !== Auth::id()) {
                $exam = null; // ignore foreign exam
            }
        }

        $components       = $this->teacherComponents($sicoreType, $exam, $subjectId, $levelId);
        $linkedIds        = $exam ? $exam->linkedComponentIds() : [];
        $missingLinked    = $exam ? $exam->linked_components_info->filter(
            fn($c) => !$components->pluck('id')->map(fn($i) => (int)$i)->contains((int)$c->id)
        ) : collect();

        return response()->view('exams.partials.components-selector', [
            'components'         => $components,
            'linkedComponentIds' => $linkedIds,
            'missingLinked'      => $missingLinked,
            'sicoreType'         => $sicoreType,
            'noSubject'          => !$subjectId && !$exam?->subject_id,
        ]);
    }

    /**
     * Fail fast if the teacher selected MORE than one component for the same
     * (section, group). An exam cannot feed two components of the same section.
     * Thrown before saving the exam so nothing gets persisted on a bad request.
     */
    protected function assertNoSectionConflicts(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (count($ids) <= 1) return;

        $rows = \Illuminate\Support\Facades\DB::connection('sicore')
            ->table('evaluation_components as ec')
            ->leftJoin('sections as s', 's.id', '=', 'ec.section_id')
            ->whereIn('ec.id', $ids)
            ->select('ec.id', 'ec.section_id', 'ec.group_type', 's.name as section_name')
            ->get();

        $byGroup = [];
        foreach ($rows as $r) {
            $key = $r->section_id . '|' . ($r->group_type ?? '');
            $byGroup[$key][] = $r;
        }
        foreach ($byGroup as $rs) {
            if (count($rs) > 1) {
                $label = trim(($rs[0]->section_name ?? '—') . ' ' . ($rs[0]->group_type ?? ''));
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'evaluation_component_ids' =>
                        "No puedes asociar dos componentes a la misma sección/grupo ({$label}). " .
                        "Un examen alimenta a un solo componente por sección.",
                ]);
            }
        }
    }

    /**
     * Sync the exam ↔ SICORE component links. Accepts only components owned by
     * the teacher whose evaluation type is supported (TESTS/PROJECT/DAILY_WORK/
     * HOMEWORK) and not already linked to another exam.
     */
    protected function syncExamComponents(Exam $exam, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        $valid = [];
        if (!empty($ids)) {
            $supportedTypes = array_values(\App\Models\Exam::SICORE_TYPE_MAP); // unique types possible
            $supportedTypes = array_values(array_unique($supportedTypes));

            $valid = \Illuminate\Support\Facades\DB::connection('sicore')
                ->table('evaluation_components as ec')
                ->join('evaluation_subject_year as esy', 'esy.id', '=', 'ec.evaluation_subject_year_id')
                ->join('evaluations as e', 'e.id', '=', 'esy.evaluation_id')
                ->whereIn('ec.id', $ids)
                ->where('ec.user_id', Auth::id())
                ->whereIn('e.type', $supportedTypes)
                ->pluck('ec.id')
                ->all();

            // Exclude components already linked to a DIFFERENT exam
            $takenByOthers = \App\Models\ExamEvaluationComponent::whereIn('evaluation_component_id', $valid)
                ->where('exam_id', '!=', $exam->id)
                ->pluck('evaluation_component_id')
                ->all();
            $valid = array_values(array_diff($valid, $takenByOthers));

            // Enforce: at most ONE component per (section_id, group_type).
            // The UI does this with JS — backend validates as defense-in-depth.
            if (!empty($valid)) {
                $rows = \Illuminate\Support\Facades\DB::connection('sicore')
                    ->table('evaluation_components')
                    ->whereIn('id', $valid)
                    ->select('id', 'section_id', 'group_type')
                    ->get();
                $byGroup = [];
                foreach ($rows as $r) {
                    $key = $r->section_id . '|' . ($r->group_type ?? '');
                    $byGroup[$key][] = (int) $r->id;
                }
                $valid = [];
                foreach ($byGroup as $ids) {
                    // Keep only the first per (section, group); silently drop extras
                    $valid[] = $ids[0];
                }
            }
        }

        // Remove links no longer selected
        \App\Models\ExamEvaluationComponent::where('exam_id', $exam->id)
            ->when(!empty($valid), fn($q) => $q->whereNotIn('evaluation_component_id', $valid))
            ->delete();

        // Add new links
        $existing = \App\Models\ExamEvaluationComponent::where('exam_id', $exam->id)
            ->pluck('evaluation_component_id')->all();
        foreach (array_diff($valid, $existing) as $cid) {
            \App\Models\ExamEvaluationComponent::create([
                'exam_id'                 => $exam->id,
                'evaluation_component_id' => $cid,
            ]);
        }
    }

    protected function getTeacherSubjects(): array
    {
        $activeYear = Year::where('status', 1)->orderBy('year', 'desc')->first();
        $assigned   = $activeYear
            ? Auth::user()->subjectsForYear($activeYear->id)->orderBy('name')->get()
            : collect();

        if ($assigned->isNotEmpty()) {
            return [$assigned, true];
        }
        return [Subject::orderBy('name')->get(), false];
    }

    protected function validateSubjectOwnership(int $subjectId): void
    {
        $activeYear = Year::where('status', 1)->orderBy('year', 'desc')->first();
        if (!$activeYear) return;

        $assigned = Auth::user()->subjectsForYear($activeYear->id)->pluck('subjects.id');
        if ($assigned->isEmpty()) return; // fallback mode — no restriction
        if (!$assigned->contains($subjectId)) {
            abort(403, 'No tienes permiso para crear exámenes en esta materia.');
        }
    }

    protected function authorizeExam(Exam $exam): void
    {
        if ($exam->user_id !== Auth::id()) {
            abort(403, 'No tienes acceso a este examen.');
        }
    }
}
