<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha del paciente {{ $paciente->id }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #22334a; margin: 24px; }
        h1 { font-size: 20pt; border-bottom: 2px solid #25637d; padding-bottom: 12px; }
        dt { font-weight: bold; margin-top: 14px; }
        dd { margin: 5px 0 0; }
        .photo { float: right; width: 110px; height: auto; margin: 0 0 16px 20px; }
        .notice { margin-top: 30px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
    @if($photoDataUri)<img class="photo" src="{{ $photoDataUri }}" alt="Fotografía del paciente">@endif
    <h1>Ficha del paciente</h1>
    <dl>
        <dt>Código</dt><dd>{{ $paciente->id }}</dd>
        <dt>Nombre y apellido</dt><dd>{{ $paciente->nombre }} {{ $paciente->apellido }}</dd>
        <dt>Fecha de nacimiento</dt><dd>{{ $paciente->fecha_nacimiento->format('d/m/Y') }}</dd>
        <dt>Alergias</dt><dd>{{ $paciente->alergias ?: 'Sin alergias registradas' }}</dd>
        <dt>Enfermedades</dt><dd>{{ $paciente->enfermedades }}</dd>
        <dt>Médicos y especialidades</dt>
        <dd>@forelse($paciente->medicos as $medico)<div>{{ $medico->nombre }} — {{ $medico->especialidad }}</div>@empty Sin asignación @endforelse</dd>
        <dt>Fecha de registro</dt><dd>{{ $paciente->created_at->format('d/m/Y H:i') }}</dd>
    </dl>
    <p class="notice">Documento confidencial. Compártalo únicamente con personas autorizadas.</p>
</body>
</html>
