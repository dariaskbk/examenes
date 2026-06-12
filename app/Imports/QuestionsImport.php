<?php

namespace App\Imports;

use App\Models\ExamQuestion;
use App\Models\ExamOption;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;

class QuestionsImport implements WithMultipleSheets
{
    protected int   $examId;
    protected array $mediaFiles; // ['basename.jpg' => '/full/path']
    public array    $errors   = [];
    public int      $imported = 0;

    /** @var QuestionSheetImport[] */
    private array $sheetImporters = [];

    public function __construct(int $examId, array $mediaFiles = [])
    {
        $this->examId     = $examId;
        $this->mediaFiles = $mediaFiles;
    }

    public function sheets(): array
    {
        $this->sheetImporters = [
            1  => new QuestionSheetImport($this->examId, 'single_choice',       $this->mediaFiles),
            2  => new QuestionSheetImport($this->examId, 'multiple_select',     $this->mediaFiles),
            3  => new QuestionSheetImport($this->examId, 'true_false',          $this->mediaFiles),
            4  => new QuestionSheetImport($this->examId, 'short_answer',        $this->mediaFiles),
            5  => new QuestionSheetImport($this->examId, 'matching',            $this->mediaFiles),
            6  => new QuestionSheetImport($this->examId, 'ordering',            $this->mediaFiles),
            7  => new QuestionSheetImport($this->examId, 'identification',      $this->mediaFiles),
            8  => new QuestionSheetImport($this->examId, 'restricted_response', $this->mediaFiles),
            9  => new QuestionSheetImport($this->examId, 'exercise',            $this->mediaFiles),
            10 => new QuestionSheetImport($this->examId, 'written_production',  $this->mediaFiles),
        ];

        return [0 => new SkipSheet()] + $this->sheetImporters;
    }

    public function collect(): void
    {
        foreach ($this->sheetImporters as $sheet) {
            $this->errors   = array_merge($this->errors, $sheet->errors);
            $this->imported += $sheet->imported;
        }
    }
}

class SkipSheet implements ToCollection
{
    public function collection(Collection $rows): void {}
}

class QuestionSheetImport implements ToCollection, WithHeadingRow
{
    private int    $examId;
    private string $type;
    private array  $mediaFiles;
    public array   $errors   = [];
    public int     $imported = 0;

    /**
     * In-session cache: sourcePath → [type, img, audio, video]
     * Prevents re-storing the same file when referenced in multiple rows.
     */
    private array $storedCache = [];

    /** Media extensions → type */
    private const MEDIA_EXTS = [
        'jpg'  => 'image', 'jpeg' => 'image', 'png'  => 'image',
        'gif'  => 'image', 'webp' => 'image', 'bmp'  => 'image',
        'mp3'  => 'audio', 'wav'  => 'audio', 'ogg'  => 'audio',
        'm4a'  => 'audio', 'aac'  => 'audio',
        'mp4'  => 'video', 'webm' => 'video', 'mov'  => 'video',
        'avi'  => 'video', 'mkv'  => 'video',
    ];

    /** Storage directories per media type */
    private const MEDIA_DIRS = [
        'image' => 'exam-images',
        'audio' => 'exam-audio',
        'video' => 'exam-video',
    ];

    public function __construct(int $examId, string $type, array $mediaFiles = [])
    {
        $this->examId     = $examId;
        $this->type       = $type;
        $this->mediaFiles = $mediaFiles;
    }

    public function collection(Collection $rows): void
    {
        $order = ExamQuestion::where('exam_id', $this->examId)->max('order') ?? 0;

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;
            $text   = trim($row['pregunta'] ?? '');

            if (empty($text)) continue;
            if (stripos($text, 'NOTA:') === 0) continue;

            $points = max(0.1, (float) ($row['puntos'] ?? 1));
            $order++;

            // ── Resolve media file if referenced ────────────────────────────
            $imagePath = $audioPath = $videoPath = null;
            $mediaType = 'none';
            $mediaRef  = strtolower(trim($row['media_archivo'] ?? ''));

            if ($mediaRef !== '' && isset($this->mediaFiles[$mediaRef])) {
                $resolved = $this->storeMediaFile($this->mediaFiles[$mediaRef], $rowNum);
                if ($resolved) {
                    [$mediaType, $imagePath, $audioPath, $videoPath] = $resolved;
                }
            } elseif ($mediaRef !== '') {
                $this->errors[] = "Fila {$rowNum}: archivo multimedia '{$mediaRef}' no encontrado en el ZIP.";
            }

            try {
                $question = ExamQuestion::create([
                    'exam_id'          => $this->examId,
                    'type'             => $this->type,
                    'question_text'    => $text,
                    'points'           => $points,
                    'order'            => $order,
                    'media_type'       => $mediaType,
                    'image'            => $imagePath,
                    'audio'            => $audioPath,
                    'video'            => $videoPath,
                    'grading_criteria' => in_array($this->type, \App\Models\ExamQuestion::RUBRIC_TYPES)
                                            ? trim($row['criterio_evaluacion'] ?? '') ?: null
                                            : null,
                ]);
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$rowNum}: error al guardar pregunta — " . $e->getMessage();
                $order--;
                continue;
            }

            try {
                $ok = match ($this->type) {
                    'single_choice'       => $this->importSingleChoice($question, $row, $rowNum),
                    'multiple_select'     => $this->importMultipleSelect($question, $row, $rowNum),
                    'true_false'          => $this->importTrueFalse($question, $row, $rowNum),
                    'matching'            => $this->importMatching($question, $row, $rowNum),
                    'ordering'            => $this->importOrdering($question, $row, $rowNum),
                    'identification'      => $this->importIdentification($question, $row, $rowNum),
                    default               => true, // short_answer, restricted_response, exercise, written_production
                };
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$rowNum}: error al guardar opciones — " . $e->getMessage();
                $question->delete();
                $order--;
                continue;
            }

            if ($ok) {
                $this->imported++;
            } else {
                $question->delete();
                $order--;
            }
        }
    }

    // ── Media file storage ───────────────────────────────────────────────────

    /**
     * Store a media file from the extracted ZIP into Laravel's public disk.
     * Returns [mediaType, imagePath, audioPath, videoPath] or null on failure.
     */
    private function storeMediaFile(string $sourcePath, int $rowNum): ?array
    {
        // Return cached result if this exact source file was already stored in this import
        if (isset($this->storedCache[$sourcePath])) {
            return $this->storedCache[$sourcePath];
        }

        if (!file_exists($sourcePath)) {
            $this->errors[] = "Fila {$rowNum}: el archivo de media no existe en la ruta temporal.";
            return null;
        }

        $ext       = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $mediaType = self::MEDIA_EXTS[$ext] ?? null;

        if (!$mediaType) {
            $this->errors[] = "Fila {$rowNum}: extensión '.{$ext}' no soportada. Use jpg/png/webp, mp3/wav/ogg, mp4/webm.";
            return null;
        }

        // Hash-based filename: identical files get the same path → no duplicate on disk
        $contents = file_get_contents($sourcePath);
        $dir      = self::MEDIA_DIRS[$mediaType];
        $filename = md5($contents) . '.' . $ext;
        $destPath = $dir . '/' . $filename;

        if (!Storage::disk('public')->exists($destPath)) {
            Storage::disk('public')->put($destPath, $contents);
        }

        $result = match($mediaType) {
            'image' => [$mediaType, $destPath, null,      null],
            'audio' => [$mediaType, null,      $destPath, null],
            'video' => [$mediaType, null,      null,      $destPath],
        };

        // Cache so subsequent rows referencing the same file reuse this result instantly
        $this->storedCache[$sourcePath] = $result;

        return $result;
    }

    // ── Per-type option importers ─────────────────────────────────────────────

    private function importSingleChoice($q, $row, int $n): bool
    {
        $letters = ['a','b','c','d','e','f'];
        $options = [];
        foreach ($letters as $l) {
            $t = trim($row["opcion_{$l}"] ?? '');
            if ($t !== '') $options[$l] = $t;
        }
        if (count($options) < 3) {
            $this->errors[] = "Fila {$n} (Selección Única): mínimo 3 opciones (A, B, C).";
            return false;
        }
        $correct = strtolower(trim($row['respuesta_correcta'] ?? 'a'));
        if (!isset($options[$correct])) {
            $this->errors[] = "Fila {$n} (Selección Única): respuesta '{$correct}' no corresponde a ninguna opción.";
            return false;
        }
        $i = 1;
        foreach ($options as $l => $text) {
            ExamOption::create(['question_id'=>$q->id,'option_text'=>$text,'is_correct'=>($l===$correct),'order'=>$i++]);
        }
        return true;
    }

    private function importMultipleSelect($q, $row, int $n): bool
    {
        $letters = ['a','b','c','d','e','f'];
        $options = [];
        foreach ($letters as $l) {
            $t = trim($row["opcion_{$l}"] ?? '');
            if ($t !== '') $options[$l] = $t;
        }
        if (count($options) < 2) {
            $this->errors[] = "Fila {$n} (Selección Múltiple): mínimo 2 opciones.";
            return false;
        }
        $correctKeys = array_filter(array_map('trim', explode(',', strtolower($row['respuesta_correcta'] ?? ''))));
        if (empty($correctKeys)) {
            $this->errors[] = "Fila {$n} (Selección Múltiple): indique respuestas correctas separadas por coma (ej: a,c).";
            return false;
        }
        $i = 1;
        foreach ($options as $l => $text) {
            ExamOption::create(['question_id'=>$q->id,'option_text'=>$text,'is_correct'=>in_array($l,$correctKeys),'order'=>$i++]);
        }
        return true;
    }

    private function importTrueFalse($q, $row, int $n): bool
    {
        $v = strtolower(trim($row['respuesta_correcta'] ?? 'verdadero'));
        $isTrue = in_array($v, ['verdadero','v','true','t','1']);
        ExamOption::create(['question_id'=>$q->id,'option_text'=>'Verdadero','is_correct'=>$isTrue, 'order'=>1]);
        ExamOption::create(['question_id'=>$q->id,'option_text'=>'Falso',    'is_correct'=>!$isTrue,'order'=>2]);
        return true;
    }

    private function importMatching($q, $row, int $n): bool
    {
        $pairs = [];
        for ($i = 1; $i <= 8; $i++) {
            $c = trim($row["concepto_{$i}"] ?? '');
            $d = trim($row["definicion_{$i}"] ?? '');
            if ($c !== '' && $d !== '') $pairs[] = ['c'=>$c,'d'=>$d];
        }
        if (count($pairs) < 2) {
            $this->errors[] = "Fila {$n} (Emparejamiento): mínimo 2 pares concepto/definición.";
            return false;
        }
        foreach ($pairs as $i => $p) {
            ExamOption::create(['question_id'=>$q->id,'option_text'=>$p['c'],'match_text'=>$p['d'],'is_correct'=>true,'order'=>$i+1]);
        }
        return true;
    }

    private function importOrdering($q, $row, int $n): bool
    {
        $items = [];
        for ($i = 1; $i <= 10; $i++) {
            $t = trim($row["item_{$i}"] ?? '');
            if ($t !== '') $items[] = $t;
        }
        if (count($items) < 2) {
            $this->errors[] = "Fila {$n} (Ordenamiento): mínimo 2 ítems.";
            return false;
        }
        foreach ($items as $i => $text) {
            ExamOption::create(['question_id'=>$q->id,'option_text'=>$text,'is_correct'=>true,'order'=>$i+1]);
        }
        return true;
    }

    private function importIdentification($q, $row, int $n): bool
    {
        $labels  = ['a','b','c','d','e'];
        $items   = [];
        foreach ($labels as $l) {
            $lbl = strtoupper(trim($row["etiqueta_{$l}"] ?? ''));
            $ans = trim($row["respuesta_{$l}"] ?? '');
            if ($lbl !== '' && $ans !== '') $items[] = ['label'=>$lbl,'answer'=>$ans];
        }
        if (count($items) < 2) {
            $this->errors[] = "Fila {$n} (Identificación): mínimo 2 etiquetas (etiqueta_a/respuesta_a, etiqueta_b/respuesta_b…).";
            return false;
        }
        // Auto-sync points with number of items
        $q->update(['points' => count($items)]);
        foreach ($items as $i => $item) {
            ExamOption::create(['question_id'=>$q->id,'option_text'=>$item['label'],'match_text'=>$item['answer'],'is_correct'=>true,'order'=>$i+1]);
        }
        return true;
    }
}
