<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackgroundOperation extends Model
{
    protected $fillable = [
        'user_id', 'exam_id', 'type', 'status', 'payload', 'result', 'message',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'result'      => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function markRunning(?string $message = null): void
    {
        $this->forceFill([
            'status'     => 'running',
            'started_at' => now(),
            'message'    => $message,
        ])->save();
    }

    public function markDone(array $result, string $message): void
    {
        $this->forceFill([
            'status'      => 'done',
            'result'      => $result,
            'message'     => $message,
            'finished_at' => now(),
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status'      => 'failed',
            'message'     => $message,
            'finished_at' => now(),
        ])->save();
    }
}
