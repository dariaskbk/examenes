<?php

namespace App\Jobs;

use App\Imports\QuestionsImport;
use App\Models\BackgroundOperation;
use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Parses an Excel/ZIP bundle of exam questions in the background.
 *
 * Payload (set by the controller):
 *   work_dir   : tempdir holding the extracted files (will be cleaned at the end)
 *   excel_path : path to the .xlsx/.xls
 *   media      : [filename => /full/path, ...]  (empty for plain .xlsx)
 */
class ImportQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;  // ZIP with many images can take several minutes
    public int $tries   = 1;    // imports are not idempotent — don't retry

    public function __construct(
        public int $operationId,
        public int $examId,
        public string $workDir,
        public string $excelPath,
        public array $mediaFiles = [],
    ) {}

    public function handle(): void
    {
        $op = BackgroundOperation::findOrFail($this->operationId);
        $exam = Exam::find($this->examId);
        if (!$exam) {
            $op->markFailed('El examen ya no existe.');
            $this->cleanDir($this->workDir);
            return;
        }

        $op->markRunning('Procesando el archivo…');

        try {
            $import = new QuestionsImport($exam->id, $this->mediaFiles);
            Excel::import($import, $this->excelPath);
            $import->collect();

            $imported = (int) $import->imported;
            $errors   = (array) $import->errors;

            if ($imported > 0) {
                $msg = "Se importaron {$imported} pregunta(s).";
                if (!empty($errors)) {
                    $msg .= ' Advertencias: ' . implode(' | ', array_slice($errors, 0, 3));
                }
                $op->markDone(['imported' => $imported, 'errors' => $errors], $msg);
            } else {
                $op->markFailed('No se importaron preguntas. ' . implode(' | ', array_slice($errors, 0, 5)));
            }
        } catch (\Maatwebsite\Excel\Exceptions\SheetNotFoundException $e) {
            $op->markFailed('El Excel no tiene el formato de la plantilla de SICORE.');
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            $op->markFailed('El archivo no pudo leerse como Excel válido.');
        } finally {
            $this->cleanDir($this->workDir);
        }
    }

    public function failed(Throwable $e): void
    {
        $op = BackgroundOperation::find($this->operationId);
        $op?->markFailed('Error inesperado: ' . $e->getMessage());
        $this->cleanDir($this->workDir);
    }

    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
