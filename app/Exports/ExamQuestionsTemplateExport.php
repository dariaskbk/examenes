<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExamQuestionsTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new InstructionsSheet(),
            new SingleChoiceSheet(),
            new MultipleSelectSheet(),
            new TrueFalseSheet(),
            new ShortAnswerSheet(),
            new MatchingSheet(),
            new OrderingSheet(),
            new IdentificationSheet(),
            new RestrictedResponseSheet(),
            new ExerciseSheet(),
            new WrittenProductionSheet(),
        ];
    }
}

// ── Shared style helpers ──────────────────────────────────────────────────────
trait TemplateStyles
{
    protected function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }

    protected function mediaHeaderStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }

    protected function exampleStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F9FF']],
            'font' => ['italic' => true, 'color' => ['rgb' => '374151']],
            'alignment' => ['wrapText' => true],
        ];
    }

    protected function noteStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
            'font' => ['color' => ['rgb' => '854D0E'], 'size' => 9],
            'alignment' => ['wrapText' => true],
        ];
    }
}

// ── Instructions sheet ────────────────────────────────────────────────────────
class InstructionsSheet implements FromArray, WithTitle, WithStyles
{
    use TemplateStyles;

    public function title(): string { return 'Instrucciones'; }

    public function array(): array
    {
        return [
            ['PLANTILLA DE PREGUNTAS — ExamCore'],
            [''],
            ['Este archivo contiene 10 hojas, una por tipo de pregunta. Llena los datos en cada hoja según el tipo que necesites.'],
            [''],
            ['CÓMO INCLUIR IMÁGENES, AUDIOS O VIDEOS'],
            ['1. En la columna "media_archivo" escribe EXACTAMENTE el nombre del archivo (ej: figura_01.jpg).'],
            ['2. Guarda este Excel junto con todos los archivos multimedia en una misma carpeta.'],
            ['3. Selecciona todos y comprime en un archivo ZIP.'],
            ['4. En ExamCore, sube el ZIP (en lugar del Excel solo).'],
            ['   ✓ Formatos de imagen: jpg, jpeg, png, gif, webp'],
            ['   ✓ Formatos de audio:  mp3, wav, ogg, m4a'],
            ['   ✓ Formatos de video:  mp4, webm, mov'],
            ['   Si no necesita multimedia, deje la celda vacía y suba el Excel directamente.'],
            [''],
            ['HOJA', 'TIPO', 'DESCRIPCIÓN'],
            ['Seleccion_Unica',    'single_choice',       'Una sola respuesta correcta. Mínimo 3 opciones (A, B, C), D es opcional.'],
            ['Seleccion_Multiple', 'multiple_select',     'Varias respuestas correctas. Separa las correctas con coma (ej: a,c).'],
            ['Verdadero_Falso',    'true_false',           'Solo indica si la respuesta es verdadero o falso.'],
            ['Respuesta_Corta',    'short_answer',         'El estudiante escribe una respuesta libre. Requiere revisión manual.'],
            ['Emparejamiento',     'matching',             'Relaciona conceptos con definiciones.'],
            ['Ordenamiento',       'ordering',             'El estudiante ordena los ítems (escríbalos en orden correcto).'],
            ['Identificacion',     'identification',       'El estudiante identifica etiquetas en una imagen/diagrama.'],
            ['Resp_Restringida',   'restricted_response',  'Respuesta estructurada. Puede incluir rúbrica de evaluación.'],
            ['Ejercicio',          'exercise',             'Ejercicio con desarrollo. Puede incluir rúbrica de evaluación.'],
            ['Prod_Escrita',       'written_production',   'Producción escrita extensa. Puede incluir rúbrica de evaluación.'],
            [''],
            ['NOTAS GENERALES:'],
            ['- La columna "puntos" es opcional (por defecto 1 punto).'],
            ['- Para Identificación, los puntos se calculan automáticamente según el número de etiquetas.'],
            ['- Las filas vacías y las que empiezan con "NOTA:" se ignoran automáticamente.'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A3:G3');
        $sheet->mergeCells('A5:G5');
        return [
            1  => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4F46E5']]],
            5  => ['font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '059669']]],
            15 => $this->headerStyle(),
            28 => ['font' => ['bold' => true]],
        ];
    }
}

// ── Single Choice sheet ───────────────────────────────────────────────────────
class SingleChoiceSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Seleccion_Unica'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'opcion_a', 'opcion_b', 'opcion_c', 'opcion_d', 'respuesta_correcta', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['¿Cuál es el organelo responsable de la síntesis de proteínas?', 1, 'Mitocondria', 'Ribosoma', 'Lisosoma', 'Aparato de Golgi', 'b', ''],
            ['¿Cuánto es 2 + 2?', 1, '3', '4', '5', '', 'b', ''],
            ['Observe la figura. ¿Qué estructura se señala con la flecha?', 1, 'Núcleo', 'Membrana', 'Ribosoma', '', 'a', 'celula_01.png'],
            ['NOTA: opcion_d es opcional. respuesta_correcta: a/b/c/d. media_archivo: nombre del archivo en el ZIP (vacío si no aplica).', '', '', '', '', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => $this->headerStyle(),
            // media_archivo header column (H) in green
            'H1' => $this->mediaHeaderStyle(),
            2 => $this->exampleStyle(),
            3 => $this->exampleStyle(),
            4 => $this->exampleStyle(),
            5 => $this->noteStyle(),
        ];
    }

    public function columnWidths(): array
    {
        return ['A'=>55,'B'=>8,'C'=>25,'D'=>25,'E'=>25,'F'=>25,'G'=>18,'H'=>22];
    }
}

// ── Multiple Select sheet ─────────────────────────────────────────────────────
class MultipleSelectSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Seleccion_Multiple'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'opcion_a', 'opcion_b', 'opcion_c', 'opcion_d', 'opcion_e', 'opcion_f', 'respuesta_correcta', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Seleccione TODAS las afirmaciones correctas sobre la mitosis:', 2,
             'Produce dos células hijas idénticas', 'Ocurre en células somáticas',
             'Reduce a la mitad el número de cromosomas', 'Incluye profase, metafase, anafase y telofase',
             '', '', 'a,b,d', ''],
            ['NOTA: respuesta_correcta debe ser las letras correctas separadas por coma (ej: a,c o a,b,d)', '', '', '', '', '', '', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'J1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>55,'B'=>8,'C'=>28,'D'=>28,'E'=>28,'F'=>28,'G'=>18,'H'=>18,'I'=>18,'J'=>22];
    }
}

// ── True/False sheet ──────────────────────────────────────────────────────────
class TrueFalseSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Verdadero_Falso'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'respuesta_correcta', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['El agua hierve a 100°C al nivel del mar.', 1, 'verdadero', ''],
            ['El sol gira alrededor de la tierra.', 1, 'falso', ''],
            ['NOTA: respuesta_correcta: verdadero / falso', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'D1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->exampleStyle(), 4=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>55,'B'=>8,'C'=>15,'D'=>22];
    }
}

// ── Short Answer sheet ────────────────────────────────────────────────────────
class ShortAnswerSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Respuesta_Corta'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Explica el proceso de fotosíntesis con tus propias palabras.', 2, ''],
            ['¿Cuál es la diferencia entre mitosis y meiosis?', 3, ''],
            ['NOTA: Las respuestas cortas requieren revisión manual del docente.', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'C1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->exampleStyle(), 4=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>65,'B'=>8,'C'=>22];
    }
}

// ── Matching sheet ────────────────────────────────────────────────────────────
class MatchingSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Emparejamiento'; }

    public function headings(): array
    {
        return ['pregunta','puntos','concepto_1','definicion_1','concepto_2','definicion_2','concepto_3','definicion_3','concepto_4','definicion_4','media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Relacione cada orgánulo con su función principal:', 2,
             'Mitocondria','Producción de ATP',
             'Ribosoma','Síntesis de proteínas',
             'Lisosoma','Digestión intracelular',
             'Cloroplasto','Fotosíntesis', ''],
            ['NOTA: Agregue más pares con concepto_5/definicion_5, etc. (hasta 8 pares)', '','','','','','','','','',''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'K1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>45,'B'=>8,'C'=>22,'D'=>25,'E'=>22,'F'=>25,'G'=>22,'H'=>25,'I'=>22,'J'=>25,'K'=>22];
    }
}

// ── Ordering sheet ────────────────────────────────────────────────────────────
class OrderingSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Ordenamiento'; }

    public function headings(): array
    {
        return ['pregunta','puntos','item_1','item_2','item_3','item_4','item_5','item_6','media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Ordene las etapas del ciclo celular (de primera a última):', 2,
             'Fase G1 — crecimiento celular',
             'Fase S — replicación del ADN',
             'Fase G2 — preparación mitótica',
             'Fase M — división celular',
             '','', ''],
            ['NOTA: Escriba los ítems EN EL ORDEN CORRECTO. El sistema los mezclará aleatoriamente para el estudiante.','','','','','','','',''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'I1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>45,'B'=>8,'C'=>30,'D'=>30,'E'=>30,'F'=>30,'G'=>25,'H'=>25,'I'=>22];
    }
}

// ── Identification sheet ──────────────────────────────────────────────────────
class IdentificationSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Identificacion'; }

    public function headings(): array
    {
        return ['pregunta','puntos','etiqueta_a','respuesta_a','etiqueta_b','respuesta_b','etiqueta_c','respuesta_c','etiqueta_d','respuesta_d','etiqueta_e','respuesta_e','media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Observe el diagrama y escriba el nombre de cada estructura señalada:', 0,
             'A','Núcleo',
             'B','Membrana plasmática',
             'C','Mitocondria',
             '','',
             '','',
             'diagrama_celula.png'],
            ['NOTA: Los puntos se calculan automáticamente (1 punto por etiqueta). La columna "puntos" se ignora. Mínimo 2 etiquetas. Suba la imagen en media_archivo.','','','','','','','','','','','',''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'M1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>50,'B'=>8,'C'=>12,'D'=>25,'E'=>12,'F'=>25,'G'=>12,'H'=>25,'I'=>12,'J'=>25,'K'=>12,'L'=>25,'M'=>22];
    }
}

// ── Restricted Response sheet ─────────────────────────────────────────────────
class RestrictedResponseSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Resp_Restringida'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'criterio_evaluacion', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Explique en no más de 3 oraciones el proceso de ósmosis.', 3,
             'Menciona el gradiente de concentración (1 pt). Describe el movimiento del agua (1 pt). Usa vocabulario científico correcto (1 pt).', ''],
            ['NOTA: criterio_evaluacion es la rúbrica que verá el docente al calificar. Requiere revisión manual.', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'D1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>60,'B'=>8,'C'=>60,'D'=>22];
    }
}

// ── Exercise sheet ────────────────────────────────────────────────────────────
class ExerciseSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Ejercicio'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'criterio_evaluacion', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Resuelva el siguiente ejercicio: Calcule el área de un triángulo rectángulo con catetos de 6 cm y 8 cm.', 5,
             'Fórmula correcta: A = (b×h)/2 (1 pt). Sustitución: A = (6×8)/2 (1 pt). Resultado: 24 cm² (2 pt). Unidades correctas (1 pt).', 'triangulo.png'],
            ['NOTA: Los ejercicios requieren revisión manual del docente. Puede adjuntar una imagen con el ejercicio en media_archivo.', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'D1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>60,'B'=>8,'C'=>60,'D'=>22];
    }
}

// ── Written Production sheet ──────────────────────────────────────────────────
class WrittenProductionSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    use TemplateStyles;

    public function title(): string { return 'Prod_Escrita'; }

    public function headings(): array
    {
        return ['pregunta', 'puntos', 'criterio_evaluacion', 'media_archivo'];
    }

    public function array(): array
    {
        return [
            ['Redacte un texto expositivo de al menos 150 palabras sobre el impacto del cambio climático en Costa Rica.', 20,
             'Coherencia y cohesión (5 pt). Vocabulario y registro formal (5 pt). Desarrollo de ideas con argumentos (5 pt). Ortografía y puntuación (5 pt).', ''],
            ['NOTA: La producción escrita requiere revisión manual del docente con base en el criterio de evaluación indicado.', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1=>$this->headerStyle(), 'D1'=>$this->mediaHeaderStyle(), 2=>$this->exampleStyle(), 3=>$this->noteStyle()];
    }

    public function columnWidths(): array
    {
        return ['A'=>65,'B'=>8,'C'=>65,'D'=>22];
    }
}
