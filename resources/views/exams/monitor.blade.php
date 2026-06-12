@extends('layouts.app')
@section('title', 'Monitoreo — ' . $exam->title)
@section('breadcrumb')
    <a href="{{ route('exams.index') }}">Mis Exámenes</a>
    <span class="mx-1 text-muted">/</span>
    <a href="{{ route('exams.show', $exam) }}">{{ Str::limit($exam->title, 30) }}</a>
    <span class="mx-1 text-muted">/</span>
    <span class="fw-600 text-dark">Monitoreo en vivo</span>
@endsection

@section('content')

{{-- Header --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="bi bi-broadcast me-2" style="color:#0891B2;"></i>Monitoreo en vivo
        </h5>
        <div class="text-muted" style="font-size:.8rem;">
            {{ $exam->title }}@if($subject) · {{ $subject->name }}@endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="liveDot" style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#10B981;"></span>
        <span class="text-muted" style="font-size:.76rem;">
            Actualizado <span id="lastUpdate">—</span> · servidor <span id="serverTime">—</span>
        </span>
        <button id="refreshBtn" class="btn btn-sm btn-outline-info" onclick="fetchMonitor()" style="font-size:.76rem;">
            <i class="bi bi-arrow-clockwise me-1" id="refreshIcon"></i><span id="refreshLabel">Actualizar</span>
        </button>
    </div>
</div>

{{-- Summary --}}
<div class="row g-3 mb-3">
    @php
        $cards = [
            ['key'=>'in_progress_count','label'=>'En curso',  'icon'=>'hourglass-split','color'=>'#0891B2','bg'=>'#ECFEFF'],
            ['key'=>'submitted_count',  'label'=>'Entregados','icon'=>'check-circle',   'color'=>'#059669','bg'=>'#D1FAE5'],
            ['key'=>'total_codes',      'label'=>'Códigos',   'icon'=>'person-badge',   'color'=>'#7C3AED','bg'=>'#F5F3FF'],
        ];
    @endphp
    @foreach($cards as $c)
    <div class="col-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:44px;height:44px;background:{{ $c['bg'] }};">
                <i class="bi bi-{{ $c['icon'] }}" style="color:{{ $c['color'] }};font-size:1.1rem;"></i>
            </div>
            <div>
                <div class="fw-800" id="stat-{{ $c['key'] }}" style="font-size:1.4rem;color:{{ $c['color'] }};line-height:1;">—</div>
                <div style="font-size:.7rem;color:#64748B;margin-top:2px;">{{ $c['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Live table --}}
<div class="card">
    <div class="card-head">
        <h6><i class="bi bi-people me-2" style="color:#0891B2;"></i>Estudiantes rindiendo ahora</h6>
        <span class="text-muted" style="font-size:.72rem;"><i class="bi bi-hand-index me-1"></i>Actualización manual</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-3 py-2 fw-600 text-muted" style="font-size:.7rem;">ESTUDIANTE</th>
                    <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">PROGRESO</th>
                    <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">TIEMPO RESTANTE</th>
                    <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">SALIDAS</th>
                    <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">INICIO</th>
                </tr>
            </thead>
            <tbody id="monitorBody">
                <tr><td colspan="5" class="text-center py-5 text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.ExamMonitor = {
    urls: {
        data:          "{{ route('exams.monitor-data', $exam) }}",
        attemptsBase:  "{{ url('exams/'.$exam->id.'/attempts') }}",
    },
    csrf: "{{ csrf_token() }}",
};
</script>
<script src="{{ asset('js/exams/monitor.js') }}?v={{ filemtime(public_path('js/exams/monitor.js')) }}"></script>
@endpush
