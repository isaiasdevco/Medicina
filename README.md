# Medicina2025 · Sistema de gestión clínica

Medicina2025 es un proyecto académico desarrollado con Laravel para organizar la información de pacientes y médicos en una aplicación web. Su objetivo es facilitar el registro de datos, la asignación de profesionales y la elaboración de documentos de consulta.

El proyecto aplica la arquitectura modelo-vista-controlador, relaciones de bases de datos, validación de formularios y control de acceso. Para las pruebas y demostraciones se utilizan datos ficticios.

## Funcionalidades

- **Pacientes:** registro, listado, edición y eliminación, con fecha de nacimiento, alergias y enfermedades.
- **Médicos:** administración de profesionales y especialidades; un paciente puede tener varios médicos asignados.
- **Fotografías:** carga de archivos o captura con cámara, con almacenamiento privado.
- **Documentos:** generación de fichas PDF con fotografía y códigos QR que enlazan al documento protegido.
- **Acceso:** registro e inicio de sesión, con autorización del personal antes de consultar o modificar información clínica.

El sistema impide eliminar médicos con pacientes asignados y valida los datos antes de guardarlos.

## Tecnologías

PHP 8.2+, Laravel 12, Eloquent, Blade, AdminLTE, Bootstrap, JavaScript, Vite, SQLite, DomPDF y Simple QrCode. Las pruebas utilizan PHPUnit y Playwright.

## Instalación y visualización

En Windows puede seguir el [paso a paso con XAMPP](docs/XAMPP.md).

Se requieren PHP con PDO SQLite, GD, mbstring y XML, Composer 2 y Node.js 22.12 o 24+.

```bash
git clone https://github.com/isaiasdevco/Medicina.git
cd Medicina
composer install --prefer-dist --no-interaction
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan adminlte:install --only=assets --force
npm run build
php artisan serve
```

Abra en su navegador la dirección indicada por el último comando. Estos pasos se ejecutan en la computadora donde se visualizará el proyecto. En Windows, cree el archivo SQLite vacío con `New-Item database/database.sqlite -ItemType File` si no existe.

Registre una cuenta desde la interfaz y habilite su acceso desde la terminal:

```bash
php artisan app:authorize-staff tu-correo@example.com
```

No se incluyen cuentas predeterminadas ni datos de pacientes. No sobrescriba un `.env` existente.

## Comprobaciones

```bash
php artisan test
npm run build
composer audit --locked
npm audit
```

La [guía de instalación](docs/INSTALACION.md) incluye la configuración, las pruebas de navegador y los pasos para actualizar una instalación existente.
