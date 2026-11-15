@extends('adminlte::page')
@section('title', 'Pacientes')
@section('content_header')<h1>Pacientes</h1>@stop
@section('content')
@include('partials.alerts')
<a href="{{ route('paciente.create') }}" class="btn btn-primary mb-3">Registrar paciente</a>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Acciones</th><th>Código</th><th>Nombre</th><th>Fecha de nacimiento</th><th>Fotografía</th><th>Alergias</th><th>Enfermedades</th><th>Médicos y especialidades</th></tr></thead>
        <tbody>
        @forelse($pacientes as $paciente)
            <tr>
                <td>
                    <div class="d-flex flex-wrap" style="gap: .35rem">
                        <a class="btn btn-sm btn-success" href="{{ route('paciente.edit', $paciente) }}">Editar</a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('paciente.pdf', $paciente) }}">PDF</a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('paciente.qr', $paciente) }}" target="_blank" rel="noopener noreferrer">QR</a>
                        <form method="POST" action="{{ route('paciente.delete', $paciente) }}" onsubmit="return confirm('¿Eliminar este paciente y su fotografía? Esta acción no se puede deshacer.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                        </form>
                    </div>
                </td>
                <th scope="row">{{ $paciente->id }}</th>
                <td>{{ $paciente->nombre }} {{ $paciente->apellido }}</td>
                <td>{{ $paciente->fecha_nacimiento->format('d/m/Y') }}</td>
                <td>@if($paciente->url)<img src="{{ route('paciente.photo', $paciente) }}" alt="Foto de {{ $paciente->nombre }}" width="80" loading="lazy">@else Sin fotografía @endif</td>
                <td>{{ $paciente->alergias ?: 'Sin alergias registradas' }}</td>
                <td>{{ $paciente->enfermedades }}</td>
                <td>@forelse($paciente->medicos as $medico)<div>{{ $medico->nombre }} — {{ $medico->especialidad }}</div>@empty Sin asignación @endforelse</td>
            </tr>
        @empty
            <tr><td colspan="8">No hay pacientes registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $pacientes->links() }}
@stop
