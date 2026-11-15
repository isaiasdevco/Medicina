# Revisión técnica · Medicina2025

Revisión del código del segundo archivo proporcionado, realizada el 15 de noviembre de 2026. Se mantuvieron Laravel y los módulos de pacientes, médicos, fotografías, PDF y QR. La publicación contiene código y recursos de demostración, sin importar datos clínicos del archivo.

## Hallazgos comprobados y correcciones

| Área | Estado encontrado | Corrección |
| --- | --- | --- |
| Acceso | Rutas de pacientes, médicos, PDF y QR sin autenticación | Autenticación y permiso explícito de personal en todas las rutas clínicas, incluidas fotos; registro sin privilegios y autorización/revocación por consola |
| Configuración sensible | El segundo ZIP no incluía `.env` ni bases de datos; `.env.example` habilitaba debug | Ejemplo sin secretos, debug desactivado, sesiones cifradas y base SQLite para la demostración |
| Médicos de un paciente | El formulario permitía varios, pero los controladores guardaban solo el primero | Relación muchos a muchos, tabla intermedia con clave única, validación de existencia y sincronización transaccional |
| Migración de datos | Una sola columna `medico_id` con cascada de eliminación | Migración adicional que conserva las asignaciones anteriores y restringe el borrado de médicos; reversión rechazada si puede perder pacientes/asignaciones |
| Validación | Alergias opcionales frente a columna obligatoria; reglas distintas al crear/editar; fechas y referencias insuficientes | FormRequest compartida, alergias nulas permitidas, límites de longitud, fechas no futuras y médicos válidos sin duplicados |
| Edición | Búsquedas por query string que podían devolver `null` | Enlaces con parámetros de ruta y model binding con respuestas 404 |
| Fotografías | Archivos públicos, eliminación inconsistente y falta de limpieza al borrar pacientes | Disco privado, nombres UUID, límites de tipo/peso/dimensiones y recodificación JPEG; limpieza tras fallos y al reemplazar/eliminar |
| Fotos anteriores | Fotos referenciadas podían estar en discos público o privado antiguos | Comando repetible de migración; preserva referencias compartidas, reporta ausencias y fotos públicas huérfanas sin borrarlas a ciegas |
| Cámara | Atributo de vídeo incorrecto y errores visibles solo en consola | Vista previa, captura como archivo, cierre del stream y mensajes para permisos/compatibilidad; captura probada con dispositivo sintético |
| PDF | Fuentes remotas con un import mal escrito; foto dependiente de un enlace público de almacenamiento | Fuente DejaVu Sans incluida, recursos remotos y PHP desactivados, fotografía embebida desde el disco privado |
| QR | Enlace al PDF sin comprobar que existiera el paciente ni exigir autenticación | Model binding, autorización y enlace protegido sin información clínica ni credenciales en el QR |
| Frontend | URLs locales, nombres/IDs de médicos fijos y HTML de formularios mal anidado | Rutas generadas, opciones desde la base, formularios válidos, selección y errores conservados, tablas/formularios adaptables |
| Dependencias | Laravel 11.37 fuera de soporte y paquetes frontend antiguos | Laravel 12.72, DomPDF 3.1.6 y dependencias actualizadas; ambos lockfiles conservados; dependencias frontend sin uso retiradas |
| Archivos | Fotos, log, vistas/cache compiladas y `public/hot` de otra máquina | No se importaron esos archivos al Git; recursos de terceros regenerables y traducciones idénticas al paquete excluidos |
| Documentación/pruebas | README genérico y pruebas de ejemplo mínimas | Instalación y actualización documentadas, pruebas funcionales PHP y de navegador, configuración de CI |

## Verificación observada

- PHP 8.4.27 y Composer 2.9.2, instalados sin permisos de root a partir de paquetes Debian con índices firmados y del archivo oficial de Composer con SHA-256 verificado.
- `composer install`: completado desde el lockfile. Se utilizan URL oficiales GitHub/codeload para evitar el bloqueo del endpoint de API del proxy.
- `composer validate --strict` y `composer check-platform-reqs`: correctos.
- Migraciones, instalación de recursos AdminLTE y compilación de vistas: correctas.
- `php artisan test --compact`: **63 pruebas aprobadas, 240 aserciones**; SQLite en memoria, sin pruebas omitidas.
- `npm run test:browser`: **11 comprobaciones aprobadas** en Chromium, con servidor real y datos/base/fotos temporales. Incluyen registro, autorización, login/logout, CSRF, médicos, pacientes, archivos, cámara sintética, PDF con imagen, QR decodificado, borrado y ancho de 375 px.
- `npm ci`, `npm run build` y Laravel Pint: correctos.
- `npm audit`: **0 avisos**, incluidas las dependencias de desarrollo, en el momento de la revisión.
- Gitleaks 8.24.3: sin detecciones en el código preparado y en el historial local revisado antes de la publicación. Los archivos versionados se comprobaron también para excluir fotos, datos y archivos de entorno privados.
- Script de instalación del entorno repetido sobre la instalación existente: correcto, sin borrar datos ni regenerar una clave existente.

## Límites y pendientes

- `composer audit --locked` no pudo completar la consulta de avisos porque el proxy deniega `packagist.org`. La resolución de dependencias terminó sin avisos en los metadatos disponibles, pero **eso no sustituye una auditoría completa**. Se guardó la adición de `packagist.org` y `api.github.com` en el borrador de red del entorno; hay que aplicar esos ajustes y repetir la auditoría.
- MySQL, SMTP externo, HTTPS en un servidor desplegado y una cámara física no se probaron. La cámara y sus errores se verificaron con un dispositivo sintético de Chromium.
- El workflow de GitHub Actions se añade como configuración; estos resultados corresponden a la ejecución local y no acreditan una ejecución remota del workflow.
- No se evaluaron datos ni fotografías clínicas del ZIP. Se excluyeron las 72 imágenes/archivos de las carpetas de fotos, el log y las cachés. No se publican ni se necesita decidir cuáles eran reales.
- No se identificaron credenciales en un `.env`: ese archivo no estaba en el segundo ZIP. No puede afirmarse nada sobre el contenido del primer ZIP, cuya descarga no fue posible.
- El permiso de personal da acceso al directorio compartido. No hay roles por médico/centro ni auditoría clínica. Las eliminaciones de pacientes son definitivas y requieren copias de seguridad fuera de esta demostración.
