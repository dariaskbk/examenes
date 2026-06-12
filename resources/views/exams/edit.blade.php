@extends('layouts.app')
@section('title', 'Editar Examen')
@section('breadcrumb')
    <a href="{{ route('exams.index') }}">Mis Exámenes</a>
    <span class="mx-1 text-muted">/</span>
    <a href="{{ route('exams.show', $exam) }}">{{ Str::limit($exam->title, 30) }}</a>
    <span class="mx-1 text-muted">/</span>
    <span class="fw-600 text-dark">Editar</span>
@endsection

@section('content')
<form method="POST" action="{{ route('exams.update', $exam) }}">
    @csrf @method('PUT')
    @include('exams._form', ['exam' => $exam])
    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('exams.show', $exam) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-indigo">
            <i class="bi bi-save me-1"></i>Guardar Cambios
        </button>
    </div>
</form>
@endsection
