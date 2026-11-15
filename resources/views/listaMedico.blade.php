@extends('adminlte::page')
@section('title', 'Médicos')
@section('content_header')<h1>Médicos</h1>@stop
@section('content')
@include('partials.alerts')
<a href="{{ route('medico.create') }}" class="btn btn-primary mb-3">Registrar médico</a>
<div class="table-responsive">
<table class="table table-striped table-bordered">
    <thead><tr><th>Acciones</th><th>Código</th><th>Nombre y apellido</th><th>Especialidad</th><th>Pacientes asignados</th></tr></thead>
    <tbody>
    @forelse($medicos as $medico)
        <tr>
            <td><div class="d-flex flex-wrap" style="gap: .35rem">
                <a class="btn btn-sm btn-success" href="{{ route('medico.edit', $medico) }}">Editar</a>
                <form method="POST" action="{{ route('medico.delete', $medico) }}" onsubmit="return confirm('¿Eliminar este médico?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
            </div></td>
            <th scope="row">{{ $medico->id }}</th><td>{{ $medico->nombre }}</td><td>{{ $medico->especialidad }}</td><td>{{ $medico->pacientes_count }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No hay médicos registrados.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $medicos->links() }}
@stop
