@extends('layouts.app')
@section('title', 'Mis Actividades')
@section('page-title', 'Mis Actividades')

@push('styles')
<style>
    /* Table loading fade */
    #examsTableBody { transition: opacity .18s; }
    #examsTableBody.is-loading { opacity: .28; pointer-events: none; }

    /* Filter pill buttons */
    .filter-select {
        border: 1.5px solid #E2E8F0;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 500;
        color: #374151;
        padding: .32rem .7rem;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394A3B8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .55rem center;
        padding-right: 1.8rem;
    }
    .filter-select:focus { outline: none; border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
    .filter-select.active { border-color: #4F46E5; background-color: #EEF2FF; color: #4F46E5; font-weight: 600; }

    /* Search wrapper */
    .search-wrap { position: relative; flex-grow: 1; max-width: 380px; min-width: 180px; }
    .search-wrap .bi-search { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: .9rem; pointer-events: none; }
    .search-wrap input { padding-left: 2.1rem; padding-right: 2rem; border: 1.5px solid #E2E8F0; border-radius: 8px; font-size: .82rem; height: 36px; width: 100%; transition: border-color .15s; }
    .search-wrap input:focus { border-color: #4F46E5; outline: none; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
    #searchSpinner { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); display: none; }

    /* Active filter chip */
    .active-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 600;
        padding: .22rem .6rem; border-radius: 20px;
        background: #EEF2FF; color: #4F46E5;
        border: 1px solid #C7D2FE;
    }
    .active-chip .rm { cursor: pointer; opacity: .6; transition: opacity .1s; }
    .active-chip .rm:hover { opacity: 1; }
</style>
@endpush

@section('content')

{{-- ── Tabs: Activas / Archivadas ──────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ ($view ?? 'active') === 'active' ? 'active' : '' }}"
           href="{{ route('exams.index') }}">
            <i class="bi bi-grid-1x2 me-1"></i>Activas
            <span class="badge ms-1" style="background:#EEF2FF;color:#4F46E5;font-size:.66rem;">{{ $activeCount ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($view ?? '') === 'archived' ? 'active' : '' }}"
           href="{{ route('exams.index', ['view' => 'archived']) }}">
            <i class="bi bi-archive me-1"></i>Archivadas
            @if(($archivedCount ?? 0) > 0)
            <span class="badge ms-1" style="background:#F1F5F9;color:#64748B;font-size:.66rem;">{{ $archivedCount }}</span>
            @endif
        </a>
    </li>
</ul>

{{-- ── Filter bar ──────────────────────────────────────────────────────────── --}}
<div class="card mb-4 p-3">
    <div class="d-flex flex-wrap align-items-center gap-2">

        {{-- Text search (title + subject) --}}
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="filterQ"
                   placeholder="Buscar por título o materia…"
                   value="{{ request('q') }}"
                   autocomplete="off">
            <span id="searchSpinner">
                <span class="spinner-border spinner-border-sm"
                      style="width:.75rem;height:.75rem;border-width:2px;color:#4F46E5;"></span>
            </span>
        </div>

        <div class="vr d-none d-md-block" style="height:28px;"></div>

        {{-- Tipo de actividad --}}
        <select id="filterType" class="filter-select {{ request('activity_type') ? 'active' : '' }}">
            <option value="">Tipo</option>
            @foreach(\App\Models\Exam::ACTIVITY_TYPES as $key => $meta)
            <option value="{{ $key }}" {{ request('activity_type') === $key ? 'selected' : '' }}>
                {{ $meta['label'] }}
            </option>
            @endforeach
        </select>

        {{-- Estado --}}
        <select id="filterStatus" class="filter-select {{ request('status') ? 'active' : '' }}">
            <option value="">Estado</option>
            <option value="draft"  {{ request('status') === 'draft'  ? 'selected' : '' }}>Borrador</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Cerrado</option>
        </select>

        {{-- Materia --}}
        @if($teacherSubjects->isNotEmpty())
        <select id="filterSubject" class="filter-select {{ request('subject_id') ? 'active' : '' }}">
            <option value="">Materia</option>
            @foreach($teacherSubjects as $subj)
            <option value="{{ $subj->id }}" {{ request('subject_id') == $subj->id ? 'selected' : '' }}>
                {{ $subj->name }}
            </option>
            @endforeach
        </select>
        @endif

        {{-- Disponibilidad --}}
        <select id="filterAvail" class="filter-select {{ request('avail') ? 'active' : '' }}">
            <option value="">Disponibilidad</option>
            <option value="now"      {{ request('avail') === 'now'      ? 'selected' : '' }}>Disponible ahora</option>
            <option value="upcoming" {{ request('avail') === 'upcoming' ? 'selected' : '' }}>Próximos</option>
            <option value="expired"  {{ request('avail') === 'expired'  ? 'selected' : '' }}>Vencidos</option>
            <option value="no_date"  {{ request('avail') === 'no_date'  ? 'selected' : '' }}>Sin fecha límite</option>
        </select>

        {{-- Clear (only when filters active) --}}
        <button type="button" id="clearFilters"
                class="btn btn-sm btn-outline-secondary"
                style="{{ (request('q') || request('status') || request('subject_id') || request('avail') || request('activity_type')) ? '' : 'display:none;' }}">
            <i class="bi bi-x-circle me-1"></i>Limpiar
        </button>

        {{-- New activity --}}
        <div class="ms-auto">
            <a href="{{ route('exams.create') }}" class="btn btn-indigo btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Nueva Actividad
            </a>
        </div>
    </div>
</div>

{{-- ── Result summary + active chips ─────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <p id="examsCount" class="text-muted mb-0" style="font-size:.875rem;">
        {{ $exams->total() }} actividad(es)
    </p>
    <div id="activeChips" class="d-flex gap-1 flex-wrap">
        @if(request('q'))
        <span class="active-chip" data-filter="q">
            <i class="bi bi-search" style="font-size:.65rem;"></i>«{{ request('q') }}»
            <span class="rm" onclick="clearFilter('q')">×</span>
        </span>
        @endif
        @if(request('status'))
        <span class="active-chip" data-filter="status">
            {{ ['draft'=>'Borrador','active'=>'Activo','closed'=>'Cerrado'][request('status')] ?? '' }}
            <span class="rm" onclick="clearFilter('status')">×</span>
        </span>
        @endif
        @if(request('subject_id'))
        <span class="active-chip" data-filter="subject_id">
            <i class="bi bi-book" style="font-size:.65rem;"></i>
            {{ $teacherSubjects->firstWhere('id', request('subject_id'))?->name ?? 'Materia' }}
            <span class="rm" onclick="clearFilter('subject_id')">×</span>
        </span>
        @endif
        @if(request('avail'))
        <span class="active-chip" data-filter="avail">
            <i class="bi bi-calendar" style="font-size:.65rem;"></i>
            {{ ['now'=>'Disponible ahora','upcoming'=>'Próximos','expired'=>'Vencidos','no_date'=>'Sin fecha'][request('avail')] ?? '' }}
            <span class="rm" onclick="clearFilter('avail')">×</span>
        </span>
        @endif
        @if(request('activity_type'))
        <span class="active-chip" data-filter="activity_type">
            <i class="bi bi-grid-1x2" style="font-size:.65rem;"></i>
            {{ \App\Models\Exam::ACTIVITY_TYPES[request('activity_type')]['label'] ?? '' }}
            <span class="rm" onclick="clearFilter('activity_type')">×</span>
        </span>
        @endif
    </div>
</div>

{{-- ── Main table ──────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-3 py-3 fw-600" style="color:#64748B;font-size:.75rem;">ACTIVIDAD</th>
                    <th class="py-3 fw-600" style="color:#64748B;font-size:.75rem;">MATERIA</th>
                    <th class="py-3 fw-600" style="color:#64748B;font-size:.75rem;">CONFIGURACIÓN</th>
                    <th class="py-3 fw-600" style="color:#64748B;font-size:.75rem;">DISPONIBILIDAD</th>
                    <th class="py-3 fw-600" style="color:#64748B;font-size:.75rem;">ESTADO</th>
                    <th class="py-3 fw-600 text-end pe-3" style="color:#64748B;font-size:.75rem;">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="examsTableBody">
                @include('exams._table_rows', [
                    'exams'          => $exams,
                    'subjects'       => $subjects,
                    'questionCounts' => $questionCounts,
                    'attemptCounts'  => $attemptCounts,
                ])
            </tbody>
        </table>
    </div>
    <div id="examsPagination">
        @if($exams->hasPages())
        <div class="px-3 py-2 border-top">{{ $exams->links() }}</div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const BASE_URL      = '{{ route("exams.index") }}';
    const CSRF          = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Filter inputs
    const qInput        = document.getElementById('filterQ');
    const typeSel       = document.getElementById('filterType');
    const statusSel     = document.getElementById('filterStatus');
    const subjectSel    = document.getElementById('filterSubject');   // may be null
    const availSel      = document.getElementById('filterAvail');
    const clearBtn      = document.getElementById('clearFilters');
    const searchSpinner = document.getElementById('searchSpinner');

    // Output targets
    const tableBody     = document.getElementById('examsTableBody');
    const paginationEl  = document.getElementById('examsPagination');
    const countEl       = document.getElementById('examsCount');
    const chipsEl       = document.getElementById('activeChips');

    const AVAIL_LABELS  = { now:'Disponible ahora', upcoming:'Próximos', expired:'Vencidos', no_date:'Sin fecha' };
    const STATUS_LABELS = { draft:'Borrador', active:'Activo', closed:'Cerrado' };
    const TYPE_LABELS   = @json(collect(\App\Models\Exam::ACTIVITY_TYPES)->map(fn($m) => $m['label']));

    let debounceTimer, fetchCtrl;

    /* ── Read current state ─────────────────────────────────────────── */
    function getFilters() {
        return {
            q:             qInput.value.trim(),
            activity_type: typeSel?.value ?? '',
            status:        statusSel?.value ?? '',
            subject_id:    subjectSel?.value ?? '',
            avail:         availSel?.value ?? '',
        };
    }

    /* ── Build URL from state ───────────────────────────────────────── */
    const CURRENT_VIEW = @json($view ?? 'active');
    function buildURL(filters, page) {
        const p = new URLSearchParams();
        if (CURRENT_VIEW === 'archived') p.set('view', 'archived');
        if (filters.q)             p.set('q',             filters.q);
        if (filters.activity_type) p.set('activity_type', filters.activity_type);
        if (filters.status)        p.set('status',        filters.status);
        if (filters.subject_id)    p.set('subject_id',    filters.subject_id);
        if (filters.avail)         p.set('avail',         filters.avail);
        if (page && page > 1)      p.set('page',          page);
        return BASE_URL + (p.toString() ? '?' + p.toString() : '');
    }

    /* ── Sync UI chrome ─────────────────────────────────────────────── */
    function syncUI(filters) {
        // Highlight active selects
        [typeSel, statusSel, subjectSel, availSel].forEach(s => {
            if (!s) return;
            s.classList.toggle('active', s.value !== '');
        });

        // Clear button
        const hasAny = filters.q || filters.activity_type || filters.status || filters.subject_id || filters.avail;
        clearBtn.style.display = hasAny ? '' : 'none';

        // Active chips
        let html = '';
        if (filters.q) {
            html += `<span class="active-chip" data-filter="q"><i class="bi bi-search" style="font-size:.65rem;"></i>«${escHtml(filters.q)}»<span class="rm" onclick="clearFilter('q')">×</span></span>`;
        }
        if (filters.activity_type) {
            html += `<span class="active-chip" data-filter="activity_type"><i class="bi bi-grid-1x2" style="font-size:.65rem;"></i>${TYPE_LABELS[filters.activity_type]||filters.activity_type}<span class="rm" onclick="clearFilter('activity_type')">×</span></span>`;
        }
        if (filters.status) {
            html += `<span class="active-chip" data-filter="status">${STATUS_LABELS[filters.status]||filters.status}<span class="rm" onclick="clearFilter('status')">×</span></span>`;
        }
        if (filters.subject_id && subjectSel) {
            const opt = subjectSel.options[subjectSel.selectedIndex];
            html += `<span class="active-chip" data-filter="subject_id"><i class="bi bi-book" style="font-size:.65rem;"></i>${escHtml(opt?.text||'Materia')}<span class="rm" onclick="clearFilter('subject_id')">×</span></span>`;
        }
        if (filters.avail) {
            html += `<span class="active-chip" data-filter="avail"><i class="bi bi-calendar" style="font-size:.65rem;"></i>${AVAIL_LABELS[filters.avail]||filters.avail}<span class="rm" onclick="clearFilter('avail')">×</span></span>`;
        }
        chipsEl.innerHTML = html;
    }

    /* ── Fetch and render ───────────────────────────────────────────── */
    function fetchExams(url) {
        if (fetchCtrl) fetchCtrl.abort();
        fetchCtrl = new AbortController();

        tableBody.classList.add('is-loading');
        searchSpinner.style.display = '';

        fetch(url, {
            signal: fetchCtrl.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     CSRF,
            }
        })
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => {
            tableBody.innerHTML   = data.html;
            paginationEl.innerHTML = data.pagination
                ? `<div class="px-3 py-2 border-top">${data.pagination}</div>`
                : '';
            const n = data.total ?? 0;
            countEl.textContent = n + ' examen' + (n !== 1 ? 'es' : '');
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            window.location.href = url;
        })
        .finally(() => {
            tableBody.classList.remove('is-loading');
            searchSpinner.style.display = 'none';
        });
    }

    /* ── Main filter trigger ────────────────────────────────────────── */
    function doFilter() {
        const filters = getFilters();
        const url = buildURL(filters);
        history.replaceState(null, '', url);
        syncUI(filters);
        fetchExams(url);
    }

    /* ── Event listeners ────────────────────────────────────────────── */

    // Search: debounce + immediate spinner
    qInput.addEventListener('input', () => {
        searchSpinner.style.display = '';
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doFilter, 320);
    });

    // Selects: instant
    [typeSel, statusSel, subjectSel, availSel].forEach(s => {
        s?.addEventListener('change', () => {
            clearTimeout(debounceTimer);
            doFilter();
        });
    });

    // Clear all
    clearBtn.addEventListener('click', () => {
        qInput.value = '';
        if (typeSel)    typeSel.value    = '';
        if (statusSel)  statusSel.value  = '';
        if (subjectSel) subjectSel.value = '';
        if (availSel)   availSel.value   = '';
        clearTimeout(debounceTimer);
        doFilter();
        qInput.focus();
    });

    // Pagination links → AJAX
    document.addEventListener('click', function (e) {
        const link = e.target.closest('#examsPagination a[href]');
        if (!link) return;
        e.preventDefault();
        try {
            const lu = new URL(link.href);
            qInput.value = lu.searchParams.get('q') || '';
            if (typeSel)    typeSel.value    = lu.searchParams.get('activity_type') || '';
            if (statusSel)  statusSel.value  = lu.searchParams.get('status')        || '';
            if (subjectSel) subjectSel.value = lu.searchParams.get('subject_id')    || '';
            if (availSel)   availSel.value   = lu.searchParams.get('avail')         || '';
        } catch (_) {}
        history.replaceState(null, '', link.href);
        syncUI(getFilters());
        fetchExams(link.href);
    });

    // Init
    syncUI(getFilters());
})();

/* ── Global: clear individual chip filter ───────────────────────────── */
function clearFilter(key) {
    const map = { q: 'filterQ', activity_type: 'filterType', status: 'filterStatus', subject_id: 'filterSubject', avail: 'filterAvail' };
    const el = document.getElementById(map[key]);
    if (!el) return;
    el.tagName === 'INPUT' ? (el.value = '') : (el.value = '');

    // Re-trigger: dispatch change on any select, or input on text
    el.dispatchEvent(new Event(el.tagName === 'INPUT' ? 'input' : 'change'));
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
@endpush
