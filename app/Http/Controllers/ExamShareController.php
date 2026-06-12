<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamShare;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamShareController extends Controller
{
    /** AJAX: search teachers to share with (typeahead). */
    public function searchTeachers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['teachers' => []]);
        }

        $teachers = User::where('id', '!=', Auth::id())
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('last_name_1', 'like', "%{$q}%")
                  ->orWhere('last_name_2', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('last_name_1')->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'last_name_1', 'last_name_2', 'email']);

        return response()->json([
            'teachers' => $teachers->map(fn($u) => [
                'id'    => $u->id,
                'name'  => $u->full_name,
                'email' => $u->email,
            ])->values(),
        ]);
    }

    /** Create share entries for the selected teachers. */
    public function store(Request $request, Exam $exam)
    {
        if ($exam->user_id !== Auth::id()) {
            abort(403, 'Solo el dueño del examen puede compartirlo.');
        }

        $data = $request->validate([
            'teacher_ids'   => 'required|array|min:1',
            'teacher_ids.*' => 'integer',
            'message'       => 'nullable|string|max:500',
        ], [
            'teacher_ids.required' => 'Selecciona al menos un docente.',
        ]);

        // Validate teachers exist; exclude self
        $teacherIds = User::whereIn('id', $data['teacher_ids'])
            ->where('id', '!=', Auth::id())
            ->pluck('id')->all();

        $created = 0; $skipped = 0;
        foreach ($teacherIds as $tid) {
            // Skip if there's already a non-rejected share to this teacher
            $existing = ExamShare::where('exam_id', $exam->id)
                ->where('to_user_id', $tid)
                ->first();

            if ($existing && in_array($existing->status, ['pending', 'accepted'])) {
                $skipped++;
                continue;
            }

            if ($existing) {
                // Reset a previously-rejected share
                $existing->status       = 'pending';
                $existing->from_user_id = Auth::id();
                $existing->message      = $data['message'] ?? null;
                $existing->responded_at = null;
                $existing->save();
            } else {
                ExamShare::create([
                    'exam_id'      => $exam->id,
                    'from_user_id' => Auth::id(),
                    'to_user_id'   => $tid,
                    'status'       => 'pending',
                    'message'      => $data['message'] ?? null,
                ]);
            }
            $created++;
        }

        $msg = "Se compartió el examen con {$created} docente(s).";
        if ($skipped > 0) {
            $msg .= " {$skipped} ya tenían el examen compartido.";
        }

        return back()->with($created > 0 ? 'success' : 'info', $msg);
    }

    /** "Compartidos" page — both received and sent shares for the current teacher. */
    public function index()
    {
        $received = ExamShare::where('to_user_id', Auth::id())
            ->with(['exam'])
            ->orderByRaw("FIELD(status,'pending','accepted','rejected')")
            ->orderByDesc('created_at')
            ->get();

        $sent = ExamShare::where('from_user_id', Auth::id())
            ->with(['exam'])
            ->orderByDesc('created_at')
            ->get();

        return view('exams.shared-with-me', compact('received', 'sent'));
    }

    /** Count of pending shares (for the sidebar badge). */
    public static function pendingCountForCurrentUser(): int
    {
        if (!Auth::check()) return 0;
        return ExamShare::where('to_user_id', Auth::id())
            ->where('status', 'pending')->count();
    }

    /** Accept the share → clone the exam into the receiving teacher's exams. */
    public function accept(ExamShare $share)
    {
        if ($share->to_user_id !== Auth::id()) abort(403);
        if ($share->status !== 'pending') {
            return back()->with('info', 'Esta invitación ya fue respondida.');
        }

        $source = $share->exam()->with('questions.options')->first();
        if (!$source) {
            $share->update(['status' => 'rejected', 'responded_at' => now()]);
            return back()->with('error', 'El examen original ya no existe.');
        }

        DB::transaction(function () use ($share, $source) {
            $fromUser = User::find($share->from_user_id);
            $fromName = $fromUser?->name ?: 'otro docente';

            // Clone exam (exclude things that shouldn't transfer)
            $new = $source->replicate([
                'status', 'user_id',
            ]);
            $new->title   = $source->title . ' (Compartido de ' . $fromName . ')';
            $new->status  = 'draft';
            $new->user_id = Auth::id();
            // Don't carry over a specific year — let receiver set it (or keep if same)
            $new->save();

            // Clone questions + options
            foreach ($source->questions as $q) {
                $newQ = $q->replicate();
                $newQ->exam_id = $new->id;
                $newQ->save();
                foreach ($q->options as $opt) {
                    $newOpt = $opt->replicate();
                    $newOpt->question_id = $newQ->id;
                    $newOpt->save();
                }
            }

            $share->update([
                'status'           => 'accepted',
                'responded_at'     => now(),
                'accepted_exam_id' => $new->id,
            ]);
        });

        return redirect()->route('exams.show', $share->fresh()->accepted_exam_id)
            ->with('success', 'Examen aceptado y agregado a tus actividades como borrador.');
    }

    /** Reject the share. */
    public function reject(ExamShare $share)
    {
        if ($share->to_user_id !== Auth::id()) abort(403);
        if ($share->status !== 'pending') {
            return back()->with('info', 'Esta invitación ya fue respondida.');
        }

        $share->update(['status' => 'rejected', 'responded_at' => now()]);
        return back()->with('success', 'Invitación rechazada.');
    }
}
