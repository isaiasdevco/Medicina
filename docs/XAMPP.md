# Visualizar Medicina2025 en Windows con XAMPP

XAMPP proporciona PHP y Apache. Para esta aplicación puede usar el servidor de desarrollo de Laravel o Apache. La configuración incluida usa SQLite, por lo que no necesita encender MySQL ni crear una base en phpMyAdmin.

## 1. Prepare las herramientas

Instale XAMPP con PHP 8.2 o superior, Composer 2 y Node.js 24 LTS. Durante la instalación de Composer, seleccione `C:\xampp\php\php.exe` como ejecutable PHP. Si XAMPP está instalado en otra ubicación, ajuste las rutas de esta guía.

En el panel de XAMPP, abra **Config → PHP (php.ini)** junto a Apache. Compruebe que estas extensiones estén habilitadas, sin un punto y coma al inicio de la línea; no duplique las que ya existen:

```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

DOM/XML, OpenSSL e iconv también son necesarios y normalmente vienen incluidos. Para admitir las fotos de hasta 4 MiB, compruebe estos límites:

```ini
upload_max_filesize=4M
post_max_size=6M
```

Guarde el archivo. Si Apache estaba encendido, reinícielo. Los comandos PHP de la terminal leen esa configuración al ejecutarse.

## 2. Descargue el proyecto corregido

Entre en [el repositorio](https://github.com/isaiasdevco/Medicina), elija **Code → Download ZIP**, descomprima y renombre la carpeta como `Medicina`. Colóquela en:

```text
C:\proyectos\Medicina
```

Dentro de esa carpeta deben estar directamente `artisan`, `composer.json` y `package.json`.

## 3. Abra PowerShell en esa carpeta

```powershell
cd C:\proyectos\Medicina
$env:Path = "C:\xampp\php;$env:Path"
php -v
composer --version
node -v
npm -v
```

El primer comando de versión debe mostrar PHP 8.2 o superior. El ajuste de `Path` solo afecta esta ventana de PowerShell. Si acaba de instalar Composer o Node y no se reconocen, cierre y abra la terminal.

## 4. Instale y configure la aplicación

Ejecute los siguientes comandos en orden, esperando a que cada uno termine correctamente:

```powershell
composer install --prefer-dist --no-interaction
npm ci
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (!(Test-Path database\database.sqlite)) { New-Item database\database.sqlite -ItemType File }
php artisan migrate
php artisan adminlte:install --only=assets --force
npm run build
```

Estos pasos corresponden a una instalación nueva. No vuelva a generar la clave de una instalación que ya utiliza datos propios. El ejemplo `.env` ya usa `DB_CONNECTION=sqlite`; no requiere configurar usuario y contraseña de MySQL.

## 5. Abra la aplicación: servidor de Laravel

Esta es la forma más rápida. Puede dejar Apache y MySQL detenidos en XAMPP, porque el comando siguiente inicia el servidor de desarrollo con el PHP de XAMPP:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Abra en el navegador de esa misma computadora:

```text
http://127.0.0.1:8000
```

Mantenga esa terminal abierta mientras usa la aplicación. Para detenerla, pulse **Ctrl+C**. No necesita ejecutar `npm run dev` después de compilar con `npm run build`.

## 6. Registre y autorice su cuenta

En la página, pulse **Registrarse** y cree su cuenta. Al entrar verá que está pendiente de autorización.

Abra otra ventana de PowerShell y ejecute, sustituyendo el correo por el que registró:

```powershell
cd C:\proyectos\Medicina
$env:Path = "C:\xampp\php;$env:Path"
php artisan app:authorize-staff tu-correo@example.com
```

Recargue la página. Registre primero un médico y luego un paciente ficticio. Ya puede editar registros, probar fotos y descargar PDF o QR. La cámara funciona en localhost si concede permiso al navegador.

## 7. Las siguientes veces

Si ya instaló las dependencias y configuró la aplicación, solo necesita:

```powershell
cd C:\proyectos\Medicina
$env:Path = "C:\xampp\php;$env:Path"
php artisan serve --host=127.0.0.1 --port=8000
```

Abra la misma dirección e inicie sesión. Los datos de SQLite y las fotos se conservan en su computadora. No repita la generación de clave ni use `migrate:fresh`, porque este último borra las tablas.

## Alternativa: usar Apache desde el panel de XAMPP

Si prefiere encender Apache, prepare primero los pasos 1 a 4. En esta alternativa no ejecute `php artisan serve`.

Abra `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, conserve su contenido y agregue este bloque. Se usa otro puerto para mantener la configuración existente de Apache:

```apache
Listen 8081
<VirtualHost *:8081>
    ServerName localhost
    DocumentRoot "C:/proyectos/Medicina/public"
    <Directory "C:/proyectos/Medicina/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

No duplique `Listen 8081` si ya existe. En `C:\xampp\apache\conf\httpd.conf`, compruebe que estén habilitadas estas líneas (sin `#` al principio):

```apache
LoadModule rewrite_module modules/mod_rewrite.so
Include conf/extra/httpd-vhosts.conf
```

En el `.env` del proyecto cambie únicamente la URL:

```dotenv
APP_URL=http://localhost:8081
```

Desde la carpeta del proyecto ejecute `php artisan config:clear`. En el panel de XAMPP pulse **Start** junto a **Apache**, o reinícielo si ya estaba encendido. Abra `http://localhost:8081` en su computadora y siga el registro y autorización del paso 6. MySQL puede seguir apagado.

El directorio web se limita a `public/`: la base de datos, el código y las fotos privadas quedan fuera de ese directorio. Si Apache informa que el puerto 8081 está ocupado, elija otro puerto libre y cámbielo tanto en el bloque como en `APP_URL` y en la dirección del navegador.
