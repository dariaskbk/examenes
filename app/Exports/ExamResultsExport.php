<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExamResultsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected Collection $attempts;
    protected Collection $students;
    protected float      $passingScore;
    protected string     $examTitle;
    protected ?string    $subjectName;

    public function __construct(Collection $attempts, Collection $students, float $passingScore, string $examTitle, ?string $subjectName = null)
    {
        $this->attempts     = $attempts;
        $this->students     = $students;
        $this->passingScore = $passingScore;
        $this->examTitle    = $examTitle;
        $this->subjectName  = $subjectName;
    }

    public function title(): string
    {
        return 'Resultados';
    }

    public function headings(): array
    {
        return [
            'N°',
            'Estudiante',
            'Cédula',
            'Inicio',
            'Entrega',
            'Puntos obtenidos',
            'Puntos totales',
            'Porcentaje (%)',
            'Estado',
        ];
    }

    public function collection(): Collection
    {
        return $this->attempts->map(function ($attempt, $index) {
            $student = $this->students[$attempt->student_id] ?? null;
            $passed  = ($attempt->percentage ?? 0) >= $this->passingScore;

            return [
                'num'        => $index + 1,
                'student'    => $student?->full_name ?? ('ID: ' . $attempt->student_id),
                'cedula'     => $student?->cedula ?? '—',
                'started_at' => $attempt->started_at?->format('d/m/Y H:i') ?? '—',
                'submitted'  => $attempt->submitted_at?->format('d/m/Y H:i') ?? '—',
                'score'      => round($attempt->score ?? 0, 2),
                'max_score'  => round($attempt->max_score ?? 0, 2),
                'percentage' => round($attempt->percentage ?? 0, 2),
                'status'     => $passed ? 'Aprobado' : 'No aprobado',
            ];
        });
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        // Header row style
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];

        // Zebra striping + status colour on data rows
        for ($row = 2; $row <= $lastRow; $row++) {
            $statusCell  = $sheet->getCell('I' . $row)->getValue();
            $isApproved  = $statusCell === 'Aprobado';
            $bgColor     = ($row % 2 === 0) ? 'F8FAFC' : 'FFFFFF';

            $styles[$row] = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ];

            // Colour the status cell
            $sheet->getStyle('I' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $isApproved ? '065F46' : '991B1B']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isApproved ? 'D1FAE5' : 'FEE2E2']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Outer border for the whole table
        if ($lastRow >= 2) {
            $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                    'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '4F46E5']],
                ],
            ]);
        }

        // Fix row height for header
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Center numeric columns
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $styles;
    }
}
