@extends('adminlte::page')
@section('title', 'Inicio')
@section('content_header')<h1>Medicina · Gestión clínica</h1>@stop
@section('content')
@can('access-clinic')
<div class="row">
    <div class="col-12 col-md-6"><a class="card card-body" href="{{ route('paciente.index') }}">Gestionar pacientes</a></div>
    <div class="col-12 col-md-6"><a class="card card-body" href="{{ route('medico.index') }}">Gestionar médicos</a></div>
</div>
<p class="text-muted">La información de pacientes es confidencial. Utilice únicamente datos ficticios en esta demostración.</p>
@else
<div class="alert alert-info" role="status">Su cuenta está registrada y pendiente de autorización. El responsable del sistema debe habilitar su acceso clínico.</div>
@endcan
@stop
