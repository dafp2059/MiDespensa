# INGESPRO Inmobiliaria: web en PHP + MySQL

Web de la inmobiliaria hecha desde cero, sin WordPress. Usa PHP 8, MySQL, HTML, CSS y un poco de JavaScript, y funciona en cualquier hosting con cPanel.

## Qué incluye

**Web pública**
- **Inicio:** buscador, inmuebles destacados, zonas, sección para propietarios, proyectos y contacto.
- **Listado de inmuebles** con filtros por operación, tipo, zona, precio, habitaciones y palabra clave, orden y paginación.
- **Ficha de cada inmueble:** galería, características, video de YouTube, mapa, botón de WhatsApp con mensaje prellenado y formulario para agendar visita.
- **Proyectos**, **Vende o arrienda** (captación de propietarios), **Nosotros**, **Contacto** y **Política de tratamiento de datos** (Ley 1581).
- **Pagos en línea con Wompi** (canon, administración, separación…), con página de resultado y webhook.
- **SEO:** URLs amigables (`/inmueble/casa-campestre-el-retiro`), títulos y descripciones por página, datos estructurados, `sitemap.xml` y redirecciones desde las direcciones viejas de WordPress.
- Botón flotante de WhatsApp y diseño adaptado a celulares.

**Panel de administración** (`/admin/`). Todo se cambia desde aquí, sin tocar código.

*Contenido*
- **Inmuebles:** crear, editar, ocultar, destacar y eliminar, con fotos (se reducen solas a 1800 px), portada, estado, precio y coordenadas.
- **Proyectos**, **Zonas** (con foto), **Mensajes** de los formularios (con botón para responder por WhatsApp) y **Pagos** de Wompi (exportables a Excel).

*Clientes y contratos*
- **Clientes:** arrendatarios y compradores. Con un botón se les envía por WhatsApp o correo el enlace para crear su contraseña de **Mi cuenta**.
- **Contratos:** arrendamiento o promesa de compraventa. Se elige el inmueble, el cliente y la plantilla, y el contrato sale con todos los datos llenos (incluido el valor en letras), listo para imprimir o guardar como PDF. En arriendo se crean solas las cuotas de cada mes; en venta se arma el plan de pagos.
- **Cuotas:** ver pagadas, pendientes y vencidas; registrar pagos en efectivo o transferencia; enviar recordatorios por WhatsApp; recibos.
- **Plantillas de contrato** editables (modelos de referencia: hay que revisarlos con un abogado).

*Operación diaria*
- **Actas de entrega y recepción:** se llenan desde el celular en el inmueble, por espacios, con estado, observaciones, fotos y contadores. La de recepción compara con la de entrega y resalta lo que empeoró. Se imprimen para firmar y el cliente las ve en Mi cuenta.
- **Reajuste por IPC:** cada contrato de arriendo avisa cuándo cumple año, calcula el canon máximo (IPC del año anterior, art. 20 Ley 820 de 2003), aplica el reajuste a las cuotas pendientes, impide más de un reajuste cada 12 meses y genera la carta de notificación. También permite prorrogar el contrato. La tabla de IPC se mantiene en Contratos.
- **Agenda de visitas:** por asesor, desde cero o desde un mensaje de contacto; confirmación por WhatsApp al cliente y al asesor, archivo para el calendario del celular, alerta de cruce de horarios y visitas del día en el resumen.
- **Mapa:** en la web (lista o mapa con precios) y en el inicio; en el panel se ubica el inmueble tocando el mapa o con la ubicación del celular. Usa OpenStreetMap, sin costo ni llaves.

*Portal de clientes* (`/mi-cuenta/`): el cliente ve sus cuotas, paga con un clic por Wompi (la cuota se marca pagada sola cuando Wompi aprueba), descarga recibos y ve su contrato.

*Diseño*
- **Banner:** varias diapositivas que cambian solas, cada una con foto, video subido (MP4/WEBM) o video de YouTube, título, texto y dos botones. Se eligen los segundos por diapositiva, el alto y si se muestra el buscador.
- **Inicio:** mostrar, ocultar y reordenar las secciones, y editar todos sus textos, la franja "Por qué elegirnos", los pasos para propietarios, un bloque de texto libre y el título y la descripción para Google.
- **Menús:** menú principal, barra superior y las dos columnas del pie de página (texto, enlace, orden y si abre en pestaña nueva), además de los textos del pie.
- **Páginas:** Nosotros, Contacto, Vende o arrienda, Política de datos y todas las páginas nuevas que quieras, con editor de texto, foto de encabezado y formulario de contacto opcional.
- **Ajustes:** logo, versión para fondo oscuro, ícono de la pestaña, colores, tipografía, botones de WhatsApp y los datos de la empresa (teléfono, dirección, horario, redes, NIT, razón social).
- **Usuarios** del equipo, con roles:
  - **Administrador:** todo, incluido el diseño de la web, los usuarios, las plantillas y eliminar información.
  - **Administrativo:** publica y edita inmuebles, proyectos y zonas; maneja clientes y contratos.
  - **Contable:** pagos, cuotas y recibos; consulta clientes y contratos.
  - **Asesor:** responde mensajes y atiende clientes; ve los inmuebles pero no los publica ni los edita.

Los **códigos de inmueble son solo números** (se asignan solos desde el 100 o se escriben a mano). En la web se busca por código, y la dirección corta `tudominio.com/105` abre directamente el inmueble 105, ideal para los avisos de "Se vende / Se arrienda". El asesor de cada inmueble se elige de la lista de usuarios y en la ficha se muestra su nombre y su celular.

Al subir una versión nueva del código, la base de datos se actualiza sola la primera vez que alguien abre la web.

## Estructura

```
app/            Código interno. Va FUERA de public_html.
  config.php    Tus datos y contraseñas (se crea a partir de config.sample.php).
  views/        Plantillas: cabecera, pie, tarjetas, formularios, panel.
public_html/    Lo que se ve en internet.
  admin/        Panel de administración.
  assets/       Estilos, JavaScript, logo.
  uploads/      Fotos que sube el equipo.
database/schema.sql   Tablas de la base de datos.
```

## Instalación en cPanel

1. **Copia de seguridad del WordPress actual.** En cPanel > Copias de seguridad, descarga una copia completa antes de tocar nada.
2. **Base de datos.** En cPanel > Bases de datos MySQL, crea una base de datos y un usuario, y dale todos los privilegios. Las tablas se crean solas la primera vez que se abre la web (no hace falta importar nada en phpMyAdmin).
3. **Archivos.** Con el Administrador de archivos o FTP:
   - Sube la carpeta `app/` a tu carpeta de inicio (al lado de `public_html`, **no dentro**).
   - Sube el contenido de `public_html/` dentro de `public_html` (después de mover el WordPress a otra carpeta o eliminarlo).
4. **Configuración.** Copia `app/config.sample.php` como `app/config.php` y completa los datos de la base de datos, de la empresa (NIT y razón social para la política de datos) y las llaves de Wompi.
5. **Versión de PHP.** En cPanel > Seleccionar versión de PHP, elige 8.1 o superior y activa las extensiones `pdo_mysql`, `gd`, `curl` y `mbstring`.
6. **Primer usuario.** Entra a `https://ingesproinmobiliaria.com/admin/`. La primera vez te pide crear el administrador.
7. **Permisos.** La carpeta `public_html/uploads` debe poder escribirse (755).

### Wompi

1. En [comercios.wompi.co](https://comercios.wompi.co) > Desarrolladores, copia la **llave pública**, el **secreto de integridad** y el **secreto de eventos** en `app/config.php`.
2. En la misma sección, cambia la **URL de eventos** a `https://ingesproinmobiliaria.com/wompi-webhook.php`. Así los pagos se actualizan solos aunque el cliente cierre el navegador.
3. Prueba primero con las llaves de prueba (`pub_test_…`). La página de pagos muestra un aviso mientras estés en modo de pruebas.

## Verla sin computador (GitHub Codespaces)

Desde el navegador del celular abre:

**https://codespaces.new/dafp2059/MiDespensa?ref=claude/ingespro-web**

Toca **Create codespace** y espera unos 5 minutos la primera vez. Cuando termine, ve a la pestaña **Puertos** (Ports) y abre la dirección del puerto **8000**: ahí está la web funcionando. El panel está en esa misma dirección agregando `/admin`. La primera vez te pide crear el usuario administrador.

Es una copia de pruebas: lo que cargues ahí no pasa al hosting. Cuando termines, detén el codespace en github.com/codespaces para no gastar las horas gratis del mes.

## Probarlo en tu computador

Con PHP 8 y MySQL instalados:

```bash
mysql -u root -e "CREATE DATABASE ingespro CHARACTER SET utf8mb4"
mysql -u root ingespro < database/schema.sql
cp app/config.sample.php app/config.php   # y edita base_url = http://localhost:8000 y los datos de MySQL
php -S localhost:8000 -t public_html tools/servidor-local.php
```

Abre http://localhost:8000. El archivo `tools/servidor-local.php` imita las reglas de `.htaccess`, que el servidor de pruebas de PHP no lee.

## Textos para revisar antes de publicar

- La franja "Por qué elegirnos" (en **Diseño > Inicio**) y la página **Nosotros** (en **Diseño > Páginas**): confirma que los servicios descritos son los que ofrecen.
- **Horario de atención**, **NIT** y **razón social**: se cambian en el panel, en **Ajustes**.
- El **logo**: súbelo en el panel, en **Ajustes**. Mientras tanto se muestra uno provisional.
