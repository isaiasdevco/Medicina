# Publicar una demostración en Render

La aplicación necesita PHP y una base de datos. El repositorio incluye un contenedor con Apache, PHP 8.4, las dependencias de Laravel y los recursos de interfaz compilados. `render.yaml` solicita un único servicio web con el plan `free`, sin bases de datos ni discos de pago.

## Configuración

1. Abra [Configurar Medicina2025 en Render](https://render.com/deploy?repo=https://github.com/isaiasdevco/Medicina).
2. Inicie sesión en su propia cuenta de Render y conecte el repositorio `isaiasdevco/Medicina`, rama `main`. Si usa el panel manualmente, elija **New → Blueprint** y seleccione ese repositorio.
3. Compruebe que el servicio utiliza **Free**. Si su cuenta no ofrece ese plan, no seleccione un plan de pago para seguir estas instrucciones.
4. Complete las variables privadas que solicita Render:
   - `APP_KEY`: genere una clave de Laravel ejecutando `php artisan key:generate --show` en la carpeta del proyecto, en su computadora, y copie el resultado completo (incluido `base64:`).
   - `DEMO_EMAIL`: correo que utilizará para iniciar sesión en la demostración.
   - `DEMO_PASSWORD`: contraseña exclusiva para esta demo, de al menos 12 caracteres. No reutilice la de GitHub ni la de su correo.
5. Guarde esos valores solo en las variables privadas de Render. No copie el `.env` de su computadora al repositorio.
6. Cree el servicio y espere a que termine el despliegue. Abra la dirección HTTPS que Render muestre en el panel y elija **Iniciar sesión** con los datos del paso 4.

La cuenta inicial tiene permiso clínico. Las cuentas que se registren desde la interfaz siguen necesitando autorización: el registro público no concede acceso a pacientes. Los valores de acceso se introducen en Render, nunca en el código o en un mensaje de chat.

El primer arranque ejecuta las migraciones y crea la cuenta inicial. Los arranques siguientes no duplican la cuenta ni cambian su contraseña. Si ya existe una cuenta sin autorización con ese correo, el proceso se detiene para no concederle permisos automáticamente.

## Qué se puede mostrar

Después de entrar, registre médicos ficticios y luego pacientes de prueba. Puede editar los datos, asignar varios médicos, subir una fotografía sintética, descargar el PDF y abrir su QR. La cámara requiere HTTPS y permiso del navegador.

## Límites de la demo gratuita

SQLite y las fotos se guardan en el sistema de archivos temporal del servicio. **Pueden perderse cuando Render recrea el contenedor, por ejemplo durante un nuevo despliegue.** El plan gratuito también puede suspender el servicio por inactividad, por lo que la primera visita puede tardar más en responder. No use la demo como almacenamiento permanente ni cargue datos de personas reales.

Mantener datos de forma permanente requeriría una base de datos externa y almacenamiento de archivos persistente, con sus propias condiciones y límites. Esta configuración no contrata esos servicios.

## Prueba del contenedor en su computadora

Con Docker instalado:

```bash
docker build -t medicina-demo .
```

Cree fuera del repositorio un archivo privado de variables con `APP_KEY`, `DEMO_MODE=true`, `DEMO_EMAIL`, `DEMO_PASSWORD`, `APP_LOCALE=es` y `SESSION_SECURE_COOKIE=false` para la prueba local por HTTP. Puede obtener una clave propia ejecutando `php artisan key:generate --show` en su computadora. No suba ese archivo ni comparta sus valores.

```bash
docker run --rm --env-file /ruta/privada/demo.env -p 8080:10000 medicina-demo
```

Abra `http://localhost:8080` en esa misma computadora. Para HTTPS en Render se conserva `SESSION_SECURE_COOKIE=true`; no cambie ese valor en el servicio publicado. El contenedor adapta su puerto a la variable `PORT` de Render y respeta el HTTPS de su proxy.

El despliegue público se completa en su cuenta de Render. Tener los archivos en GitHub o probar el contenedor localmente no crea por sí solo una dirección pública.
