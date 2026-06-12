<?php

namespace App\Http\Controllers;

use App\Exports\ExamQuestionsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class TemplateController extends Controller
{
    public function downloadQuestionsTemplate()
    {
        return Excel::download(new ExamQuestionsTemplateExport, 'plantilla-preguntas.xlsx');
    }
}
