@php
    $avg = $it['avg'];
    $barColor = $avg === null ? '#CBD5E1' : ($avg < 50 ? '#DC2626' : ($avg < 70 ? '#D97706' : '#059669'));
@endphp
<div class="d-flex align-items-center gap-3">
    <div class="flex-shrink-0 fw-700 text-muted" style="width:24px;font-size:.78rem;">{{ $idx + 1 }}</div>
    <div class="flex-grow-1" style="min-width:0;">
        <div style="font-size:.8rem;color:#1E293B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $it['text'] }}</div>
        <div class="progress mt-1" style="height:6px;border-radius:4px;">
            <div class="progress-bar" style="width:{{ $avg === null ? 0 : $avg }}%;background:{{ $barColor }};"></div>
        </div>
    </div>
    <div class="flex-shrink-0 text-end" style="width:92px;">
        <div class="fw-700" style="font-size:.85rem;color:{{ $barColor }};">{{ $avg === null ? 'Pend.' : $avg . '%' }}</div>
        <div style="font-size:.62rem;color:#94A3B8;">{{ $it['answered'] }} resp.</div>
    </div>
</div>
