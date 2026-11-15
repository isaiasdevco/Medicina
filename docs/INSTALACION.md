# Instalación y configuración

Para Windows, consulte también la [guía de XAMPP](XAMPP.md).

## Requisitos

- PHP 8.2 o superior con PDO SQLite, GD, mbstring, DOM/XML, fileinfo, iconv, OpenSSL, ctype y tokenizer.
- Composer 2. Las extensiones Curl y ZIP facilitan la descarga de dependencias.
- Node.js 22.12 o 24+ y npm.
- Permisos de escritura en `storage/` y `bootstrap/cache/`.

## Instalación local

Desde la carpeta del proyecto:

```bash
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

La configuración predeterminada utiliza SQLite. En Windows, cree el archivo vacío con `New-Item database/database.sqlite -ItemType File` si todavía no existe. No sobrescriba un `.env` existente ni vuelva a generar la clave de una instalación con datos.

Los recursos de AdminLTE se instalan desde Composer. Para editar los recursos de interfaz, ejecute `npm run dev` en otra terminal; use `npm run build` para generar la versión compilada.

## Cuentas y permisos

Registre una cuenta desde la interfaz y habilite su acceso clínico desde una consola de confianza:

```bash
php artisan app:authorize-staff tu-correo@example.com
```

Para retirar ese permiso:

```bash
php artisan app:authorize-staff tu-correo@example.com --revoke
```

Las cuentas nuevas no tienen acceso clínico. El personal autorizado comparte el directorio de pacientes y médicos. La recuperación de contraseña requiere configurar un servicio de correo; `MAIL_MAILER=log` se utiliza en desarrollo.

## Fotografías y datos

Las fotografías se guardan en `storage/app/private/pacientes/` y se consultan mediante rutas protegidas. No necesitan `storage:link`. Se admiten JPG, PNG y WebP de hasta 4 MiB y 3000 × 3000 píxeles. La cámara requiere HTTPS o localhost y permiso del navegador.

Eliminar un paciente borra sus asignaciones y su fotografía de forma definitiva. Un médico con pacientes asignados no puede eliminarse hasta reasignarlos. Utilice datos ficticios para las demostraciones.

## Actualizar una instalación

1. Active mantenimiento con `php artisan down` y realice una copia de seguridad de la base y de las fotografías.
2. Actualice el código y las dependencias conservando `.env` y `APP_KEY`.
3. Ejecute `php artisan migrate --force`.
4. Si la instalación guardaba fotografías en el disco público, ejecute `php artisan app:privatize-photos`. Resuelva los archivos ausentes o públicos sin referencia que indique el comando.
5. Compile con `npm run build`, compruebe la aplicación y ejecute `php artisan up`.

No use `migrate:fresh` sobre una instalación con datos. La migración de múltiples médicos conserva las asignaciones anteriores y no permite revertirlas si existen pacientes, para evitar perder información.

## Pruebas

```bash
php artisan test
vendor/bin/pint --test app bootstrap/app.php bootstrap/providers.php config database routes tests
npm run build
composer validate --strict
composer check-platform-reqs
composer audit --locked
npm audit
```

Las pruebas PHP utilizan SQLite en memoria. Las pruebas de navegador crean una base temporal, datos ficticios y una cámara sintética:

```bash
npx playwright install chromium
npm run test:browser
```

Antes de ejecutar las pruebas de navegador, compile los recursos y mantenga la configuración sin cachear (`php artisan config:clear`). El script inicia y detiene su servidor de prueba.

## Configuración privada

Mantenga `.env`, bases de datos, logs, copias de seguridad y fotografías fuera del repositorio. El directorio web debe apuntar exclusivamente a `public/`. Para un servidor con HTTPS, configure `APP_ENV=production`, `APP_DEBUG=false` y `SESSION_SECURE_COOKIE=true`.
