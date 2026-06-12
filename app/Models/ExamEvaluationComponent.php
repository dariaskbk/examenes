<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamEvaluationComponent extends Model
{
    protected $connection = 'mysql';

    protected $table = 'exam_evaluation_components';

    protected $fillable = ['exam_id', 'evaluation_component_id'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
