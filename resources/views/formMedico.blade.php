@extends('adminlte::page')
@section('title', isset($medico) ? 'Editar médico' : 'Registrar médico')
@section('content_header')<h1>{{ isset($medico) ? 'Editar médico' : 'Registrar médico' }}</h1>@stop
@section('content')
@include('partials.alerts')
<div class="row"><div class="col-12 col-lg-8">
<form class="card card-body" method="POST" action="{{ isset($medico) ? route('medico.update', $medico) : route('medico.store') }}">
    @csrf
    @isset($medico) @method('PUT') @endisset
    <div class="form-group">
        <label for="nombre">Nombre y apellido</label>
        <input id="nombre" name="nombre" class="form-control" required maxlength="255" value="{{ old('nombre', $medico->nombre ?? '') }}">
    </div>
    <div class="form-group">
        <label for="especialidad">Especialidad</label>
        <input id="especialidad" name="especialidad" class="form-control" required maxlength="255" value="{{ old('especialidad', $medico->especialidad ?? '') }}">
    </div>
    <div><button class="btn btn-primary" type="submit">{{ isset($medico) ? 'Guardar cambios' : 'Registrar médico' }}</button>
    <a href="{{ route('medico.index') }}" class="btn btn-secondary">Cancelar</a></div>
</form>
</div></div>
@stop
