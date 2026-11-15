<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medicina · Gestión clínica</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #203448; background: #f2f6f8; font: 18px/1.6 system-ui, sans-serif; }
        header, main, footer { max-width: 1080px; margin: auto; padding: 24px; }
        header { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
        a { color: #155e75; }
        .hero { background: #155e75; color: #fff; padding: clamp(28px, 7vw, 90px); border-radius: 20px; }
        h1 { font-size: clamp(2rem, 5vw, 3.6rem); line-height: 1.15; margin: 16px 0; }
        .btn { display: inline-block; background: #fff; color: #155e75; padding: 12px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; }
        .modules { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr)); gap: 20px; margin: 32px 0; }
        article { padding: 24px; background: #fff; border: 1px solid #dce6eb; border-radius: 12px; }
        h2 { font-size: 1.3rem; }
        footer { font-size: .9rem; }
        a:focus-visible { outline: 3px solid #e5ab42; outline-offset: 4px; }
    </style>
</head>
<body>
<header><strong>Medicina</strong><nav aria-label="Acceso">@auth<a href="{{ route('home') }}">Mi cuenta</a>@else<a href="{{ route('login') }}">Iniciar sesión</a> · <a href="{{ route('register') }}">Registrarse</a>@endauth</nav></header>
<main>
    <section class="hero">
        <p>Gestión clínica</p>
        <h1>Pacientes y médicos, en un solo lugar.</h1>
        <p>Registro de pacientes, asignación de médicos y generación de fichas para el personal autorizado.</p>
        <a class="btn" href="{{ route('home') }}">Acceder al sistema</a>
    </section>
    <section class="modules" aria-label="Módulos">
        <article><h2>Pacientes</h2><p>Datos de registro, alergias, enfermedades y fotografía opcional.</p></article>
        <article><h2>Médicos</h2><p>Directorio de profesionales y sus especialidades, con múltiples asignaciones por paciente.</p></article>
        <article><h2>Documentos</h2><p>Fichas PDF y códigos QR cuyos enlaces requieren acceso autorizado.</p></article>
    </section>
</main>
<footer>Proyecto de demostración para portafolio. Use exclusivamente datos ficticios.</footer>
</body>
</html>
