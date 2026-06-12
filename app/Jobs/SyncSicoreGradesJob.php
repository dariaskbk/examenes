<?php

namespace App\Jobs;

use App\Models\BackgroundOperation;
use App\Models\Exam;
use App\Services\SicoreGradeSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Pushes ExamCore grades into SICORE (cross-DB). Heavy for big classes — every
 * student costs 4-6 queries across two databases. Run in queue so the request
 * returns immediately and the teacher polls BackgroundOperation for the result.
 */
class SyncSicoreGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Allow up to 5 min for very large classes; fail rather than hang. */
    public int $timeout = 300;

    /** Retry once on transient errors (e.g. SICORE momentarily unreachable). */
    public int $tries = 2;

    public function __construct(
        public int $operationId,
        public int $examId,
    ) {}

    public function handle(SicoreGradeSync $service): void
    {
        $op = BackgroundOperation::findOrFail($this->operationId);
        $exam = Exam::find($this->examId);
        if (!$exam) {
            $op->markFailed('El examen ya no existe.');
            return;
        }

        $op->markRunning('Sincronizando notas a SICORE…');
        $result = $service->sync($exam);

        if (!($result['ok'] ?? false)) {
            $op->markFailed($result['message'] ?? 'No se pudo sincronizar.');
            return;
        }

        $msg = "Se sincronizaron {$result['synced']} nota(s) a SICORE.";
        if (($result['skipped'] ?? 0) > 0) {
            $names = implode(', ', $result['skipped_names'] ?? []);
            $extra = $result['skipped'] > count($result['skipped_names'] ?? []) ? '…' : '';
            $msg .= " {$result['skipped']} estudiante(s) se omitieron"
                  . ($names ? " ({$names}{$extra})" : '') . '.';
        }
        $op->markDone($result, $msg);
    }

    public function failed(Throwable $e): void
    {
        $op = BackgroundOperation::find($this->operationId);
        $op?->markFailed('Error inesperado: ' . $e->getMessage());
    }
}
