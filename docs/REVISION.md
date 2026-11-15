# Revisión del proyecto

Revisión de `main` para el portafolio académico de AppRo8, realizada el 15 de noviembre de 2026. Se mantuvieron Laravel y los módulos existentes. Todas las pruebas utilizaron información ficticia.

## Problemas corregidos

- **Inicio de PHPUnit:** `phpunit.xml` apuntaba a `tests/Unit`, una carpeta vacía que Git no guarda. Al quitarla del entorno local se reprodujo el mensaje `Test directory .../tests/Unit not found`, con código de salida 2. Se retiró esa suite y se conservaron las pruebas reales de `tests/Feature`. También se configuró PHPUnit para fallar si no encuentra pruebas.
- **Formularios:** algunos valores con un tipo incorrecto provocaban un error 500 al volver a mostrar el formulario, aunque la validación ya los había rechazado. Se corrigió la presentación de esos valores en pacientes y médicos y se añadieron pruebas de regresión.
- **Permisos:** una cuenta sin autorización podía recibir 403 o 404 según existiera el registro solicitado. Ahora se comprueba el permiso clínico antes de buscar el paciente o médico, por lo que no se revela esa diferencia.
- **Workflow:** Playwright quedó habilitado para PHP 8.2 y 8.4. Un fallo en una versión ya no cancela automáticamente la otra.

## Resultados comprobados en esta revisión

Se preparó una copia limpia de los archivos del proyecto, sin `.env`, `tests/Unit`, cachés de Laravel ni recursos compilados. Se instalaron las dependencias según los lockfiles, se generó una configuración local de prueba y se ejecutaron las migraciones y la compilación. No se reutilizaron los resultados de la revisión anterior.

| Comprobación local | PHP 8.2.35 | PHP 8.4.27 |
| --- | --- | --- |
| Suite de PHPUnit | 74 pruebas aprobadas, 276 aserciones | 74 pruebas aprobadas, 276 aserciones |
| Playwright con Chromium | 12 comprobaciones aprobadas | 12 comprobaciones aprobadas |
| Laravel Pint | Aprobado | Aprobado |
| `composer validate --strict` | Aprobado | Aprobado |
| `composer check-platform-reqs` | Aprobado | Aprobado |

No hubo pruebas omitidas en esas ejecuciones. También finalizaron correctamente `composer install`, `npm ci`, las migraciones, la instalación de los recursos de AdminLTE y `npm run build`. `npm audit` devolvió cero avisos.

El navegador utilizado fue Chromium 156.0.8078.79, controlado por Playwright 1.65.1, con Node.js 24.22.1 sobre Linux. Las ejecuciones locales usaron el Chromium instalado; el workflow de GitHub instala el navegador de Playwright.

Las pruebas cubren:

- Registro de cuentas, inicio y cierre de sesión y autorización o revocación del personal.
- Alta, listado, edición y eliminación de pacientes y médicos, con múltiples médicos por paciente.
- Validación de datos, conservación de selecciones y presentación de errores sin fallos 500.
- Restricciones de borrado y migración de asignaciones anteriores sin perder pacientes.
- Fotografías privadas, reemplazo y eliminación de archivos y limpieza si falla una operación.
- Captura con cámara sintética, cierre del dispositivo y mensaje al denegar el permiso.
- PDF con fotografía embebida y QR decodificado hacia el documento correcto, que sigue requiriendo autenticación.
- Protección CSRF mediante solicitudes HTTP reales y formulario a 375 píxeles de ancho.

## GitHub Actions: verificación pendiente

La auditoría externa informó el fallo de la ejecución [43758679988](https://github.com/isaiasdevco/Medicina/actions/runs/43758679988). Se reprodujo y corrigió su causa en una copia local limpia, pero **no se pudo consultar el resultado remoto desde este entorno**.

El proxy rechaza la conexión HTTPS a `api.github.com` con `CONNECT 403`; `gh api` devuelve `Forbidden` al consultar esa ejecución y el repositorio. Esto impide leer sus trabajos, logs y estado final. El acceso Git por HTTPS sí permite descargar y subir los cambios. La matriz quedó configurada para probar ambas versiones, pero los resultados locales de la tabla no acreditan un CI aprobado.

`composer audit --locked` también permanece bloqueado: la consulta a `packagist.org/api/security-advisories/` es rechazada por el proxy y Composer termina con código 100. No se desactivó la auditoría ni se marcaron estos pasos como aprobados.

Para cerrar la verificación hay que aplicar en el entorno activo las reglas de red para `api.github.com` y `packagist.org`, volver a consultar la nueva ejecución de `main` y confirmar los dos trabajos, incluido Playwright. Si aparecen fallos remotos, deben resolverse antes de considerar terminado el CI.

## Privacidad y alcance

Gitleaks no detectó secretos en el historial revisado ni en los cambios preparados. Se comprobaron además los archivos versionados: no contienen `.env` privados, bases de datos, logs ni fotografías clínicas. `.env.example` mantiene valores de ejemplo sin credenciales. No se modificó la visibilidad del repositorio ni se creó un despliegue público.

No se probó una cámara física, MySQL, correo SMTP externo ni una instalación de XAMPP en Windows. La aplicación continúa siendo un proyecto académico con directorio clínico compartido para el personal autorizado y eliminación definitiva de pacientes; las pruebas no equivalen a una certificación para usar información médica real.
