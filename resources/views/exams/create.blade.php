@extends('layouts.app')
@section('title', 'Crear Examen')
@section('breadcrumb')
    <a href="{{ route('exams.index') }}">Mis Exámenes</a>
    <span class="mx-1 text-muted">/</span>
    <span class="fw-600 text-dark">Crear Examen</span>
@endsection

@section('content')
<form method="POST" action="{{ route('exams.store') }}">
    @csrf
    @include('exams._form', ['exam' => null])
    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('exams.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" name="status" value="draft" class="btn btn-outline-primary">
            <i class="bi bi-save me-1"></i>Guardar Borrador
        </button>
        <button type="submit" name="status" value="active" class="btn btn-indigo">
            <i class="bi bi-check-circle me-1"></i>Crear y Activar
        </button>
    </div>
</form>
@endsection
