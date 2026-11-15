@extends('adminlte::page')
@section('title', isset($paciente) ? 'Editar paciente' : 'Registrar paciente')
@section('content_header')
    <h1>{{ isset($paciente) ? 'Editar paciente' : 'Registrar paciente' }}</h1>
@stop
@section('content')
@php
    $oldText = collect(session()->getOldInput())->map(fn ($value) => is_scalar($value) ? $value : '');
    $allergyRows = old('alergia', isset($paciente) && $paciente->alergias ? explode(', ', $paciente->alergias) : ['']);
    $allergyRows = is_array($allergyRows) ? array_filter($allergyRows, 'is_string') : [''];
    $selectedDoctors = old('medico_id', isset($paciente) ? $paciente->medicos->modelKeys() : []);
    $selectedDoctors = is_array($selectedDoctors) ? $selectedDoctors : [];
@endphp
@include('partials.alerts')
@if($datos->isEmpty())
    <div class="alert alert-info">Primero <a href="{{ route('medico.create') }}">registre un médico</a> para poder asignar el paciente.</div>
@endif
<form id="patient-form" method="POST" action="{{ isset($paciente) ? route('paciente.update', $paciente) : route('paciente.store') }}" enctype="multipart/form-data">
    @csrf
    @isset($paciente) @method('PUT') @endisset
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card card-body">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" required maxlength="255" class="form-control" value="{{ $oldText['nombre'] ?? ($paciente->nombre ?? '') }}">
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input id="apellido" name="apellido" required maxlength="255" class="form-control" value="{{ $oldText['apellido'] ?? ($paciente->apellido ?? '') }}">
                </div>
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required max="{{ now()->format('Y-m-d') }}" class="form-control" value="{{ $oldText['fecha_nacimiento'] ?? (isset($paciente) ? $paciente->fecha_nacimiento->format('Y-m-d') : '') }}">
                </div>
                <div class="form-group">
                    <label for="enfermedades">Enfermedades</label>
                    <input id="enfermedades" name="enfermedades" required maxlength="255" class="form-control" value="{{ $oldText['enfermedades'] ?? ($paciente->enfermedades ?? '') }}">
                </div>
                <fieldset class="form-group">
                    <legend class="h6 font-weight-bold">Alergias (opcional)</legend>
                    <div id="alergias-container">
                        @foreach($allergyRows ?: [''] as $alergia)
                            <div class="input-group mb-2 allergy-row">
                                <input name="alergia[]" class="form-control" maxlength="100" aria-label="Alergia" value="{{ $alergia }}">
                                <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-allergy" aria-label="Quitar alergia">Quitar</button></div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-allergy" class="btn btn-outline-secondary btn-sm">Añadir alergia</button>
                </fieldset>
                <div class="form-group">
                    <label for="medico_id">Médicos asignados</label>
                    <select id="medico_id" name="medico_id[]" class="form-control" multiple required size="5" aria-describedby="medicos-help">
                        @foreach($datos as $medico)
                            <option value="{{ $medico->id }}" @selected(in_array($medico->id, $selectedDoctors))>{{ $medico->nombre }} — {{ $medico->especialidad }}</option>
                        @endforeach
                    </select>
                    <small id="medicos-help" class="form-text text-muted">Seleccione uno o varios. En un ordenador use Ctrl o ⌘ para seleccionar más de uno.</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card card-body">
                <label for="url">Fotografía (opcional)</label>
                <input type="file" id="url" name="url" accept="image/jpeg,image/png,image/webp" class="form-control-file mb-2">
                <small class="text-muted">JPG, PNG o WebP. Máximo 4 MiB y 3000 × 3000 píxeles.</small>
                <img id="preview" class="img-fluid rounded my-3" src="{{ isset($paciente) && $paciente->url ? route('paciente.photo', $paciente) : asset('images/photo-placeholder.svg') }}" alt="Vista previa de la fotografía">
                <button type="button" id="open-camera-btn" class="btn btn-outline-primary">Usar cámara</button>
                <p id="photo-status" class="mt-2" role="status" aria-live="polite"></p>
                <div id="camera-container" hidden>
                    <video id="video" autoplay muted playsinline class="w-100" aria-label="Vista de cámara"></video>
                    <button type="button" id="capture-btn" class="btn btn-primary my-2" disabled>Capturar foto</button>
                    <button type="button" id="close-camera-btn" class="btn btn-secondary my-2">Cerrar cámara</button>
                    <canvas id="canvas" hidden></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="pb-4">
        <button type="submit" class="btn btn-primary" @disabled($datos->isEmpty())>{{ isset($paciente) ? 'Guardar cambios' : 'Registrar paciente' }}</button>
        <a href="{{ route('paciente.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
<template id="allergy-template">
    <div class="input-group mb-2 allergy-row">
        <input name="alergia[]" class="form-control" maxlength="100" aria-label="Alergia">
        <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-allergy" aria-label="Quitar alergia">Quitar</button></div>
    </div>
</template>
@stop
@section('js')
    @vite('resources/js/patient-form.js')
@stop
