# Contexto del Proyecto: Plugin POS Base para WordPress (prompt.md)

**Instrucciones para el Usuario:** Copia y pega TODO el contenido de este archivo al inicio de cada nueva sesión de chat conmigo (Gemini/IA) para proporcionarme el contexto completo del plugin `pos-base`.

---

## 1. Descripción General y Propósito

*   **Nombre:** `pos-base`
*   **Autor:** Ing. Percy Alvarez (https://percyalvarez.com/)
*   **Licencia:** GPL v2 o posterior.
*   **Propósito Principal:** Plataforma de Punto de Venta (POS) para WordPress/WooCommerce con una **arquitectura modular** central. Ofrece una interfaz de venta directa en el admin de WP y permite la extensión mediante módulos para funcionalidades específicas (streaming, WhatsApp, Evolution API, etc.).
*   **Entorno:** WordPress 5.5+, WooCommerce 4.0+, PHP 7.4+.

## 2. Tecnologías y Librerías Clave

*   **Backend:** PHP, WordPress API (Hooks, Options, Metabox, CPT, REST API, WP-Cron), WooCommerce API.
*   **Frontend (Interfaz POS):** JavaScript (jQuery, wp-util), HTML5, CSS3 (`assets/css/app.css`, `assets/css/style.css`).
*   **Librerías Externas (Vendor - cargadas por `pos-base.php`):**
    *   **Select2:** (`assets/vendor/select2/`) - Mejora de selectores.
    *   **DataTables:** (`assets/vendor/datatables/`) - Tabla de historial de Ventas (server-side).
    *   **SweetAlert2:** (`assets/vendor/sweetalert2/`) - Notificaciones visuales.
    *   **FullCalendar:** (`assets/vendor/fullcalendar/`) - Vista de Calendario.
    *   **Intl-Tel-Input:** (`assets/vendor/intl-tel-input/`) - Campo de teléfono internacional.
    *   *(Nota: DataTables Responsive Extension puede no estar cargándose correctamente)*.
*   **Componentes WP:** Thickbox (modales), wp-pointer (tour), wp-media (avatar).

## 3. Arquitectura y Archivos Clave

*   **Núcleo (`pos-base`):** Contiene la funcionalidad base y la interfaz principal.
*   **Módulos (`modules/`):** Subdirectorios para funcionalidades extendidas (`streaming`, `whatsapp`, `evolution-api`).
*   **Carga de Módulos:**
    *   La opción `pos_base_active_modules` (array de slugs) define los módulos activos.
    *   `pos-base.php` carga `modules/{slug}/pos-{slug}-module.php` para cada módulo activo en `plugins_loaded`.
*   **Integración:** **Exclusivamente mediante Hooks** (actions/filters) definidos en el núcleo (`pos_base_...`) y WordPress.
*   **Archivos Principales:**
    *   `pos-base.php`: Inicialización, carga de módulos, **`pos_base_enqueue_assets`** (carga JS/CSS base y vendor para la interfaz POS), **`pos_base_enqueue_select2_for_order_edit`**, definición de hooks base, registro API base.
    *   `pos-metabox.php`: Lógica del metabox en la pantalla de edición de pedidos de WC (`pos_base_add_order_metabox`, `pos_base_order_metabox_callback`, `pos_base_save_order_meta_data`).
    *   `pos-api.php`: Callbacks para la API REST del núcleo (incluye `pos_base_api_get_sales_for_datatable`).
    *   `pos-setting.php`: Lógica para la página de configuración (activación de módulos, estilos checkboxes).
    *   `assets/js/app.js`: JavaScript principal para la interfaz del POS (incluye inicialización DataTables, listeners modales).
    *   `modules/{slug}/pos-{slug}-module.php`: Punto de entrada de cada módulo.

## 4. Funcionalidades del Núcleo

*   **Interfaz POS (Pestaña POS):** Búsqueda productos/clientes, gestión carrito (precio editable), checkout (Tipo Venta, Suscripción Base), creación pedido WC.
*   **Calendario (Pestaña Calendario):** Vista FullCalendar de vencimientos de "Suscripción Base".
*   **Historial (Pestaña Ventas) (Mejorado):** Tabla DataTables (server-side) optimizada para el POS.
    *   **Columnas Agrupadas (5):**
        1.  `Pedido / Fecha / Tipo`: ID (link), Fecha, Tipo (badge), Acciones (Ver Pedido, Enviar Mensaje vía Thickbox).
        2.  `Cliente / Contacto`: Avatar, Nombre (link), Teléfono (link `tel:`).
        3.  `Producto(s)`: Lista de nombres y cantidades de productos.
        4.  `Vencimiento / Historial`: Fecha Vencimiento (si aplica) + tiempo relativo, Estadísticas del cliente (Total Pedidos, Gasto, Valor Medio).
        5.  `Notas / Detalles`: Última "Nota para el Cliente", Título Suscripción (si aplica), Perfil Streaming (si módulo activo y aplica). Columna ancha.
    *   **Búsqueda Server-Side:** Incluye ID, nombre/email cliente, nombre producto, teléfono, título suscripción.
    *   **Ordenación Server-Side:** Por Fecha (defecto), Nombre Cliente (aprox.), Fecha Vencimiento.
*   **Metabox Edición Pedido WC:** Permite editar datos del POS (`_pos_sale_type`, `_pos_subscription_...`, y datos de módulos). **Muestra estado de envío de recordatorio** (si módulo Evolution API está activo).
*   **Página de Configuración:** Activación/desactivación de módulos con checkboxes estilizados y descripciones leídas de las cabeceras de los módulos.

## 5. Módulos Activos y Funcionalidades

### 5.1. Módulo Streaming (`modules/streaming/`)

*   **Propósito:** Gestiona Cuentas y Perfiles de Streaming, permitiendo asignarlos a ventas de suscripción desde el POS.
*   **Componentes:** CPTs (`pos_account`, `pos_profile`), Admin UI, API (`/available-profiles`), JS Frontend (`streaming-app.js`), Integración Checkout (Select2), Integración Metabox (Select2), Integración Tabla Ventas (Columna 5).

### 5.2. Módulo WhatsApp (`modules/whatsapp/`)

*   **Propósito:** Añade un widget de chat flotante, botón en productos y botón en checkout para contacto por WhatsApp en el frontend del sitio.
*   **Componentes:** Configuración (`pos_whatsapp_settings`), Botones Frontend, Assets Frontend (`floating-button.js`/`.css`).

### 5.3. Módulo Evolution API (`modules/evolution-api/`) **(Nuevo/Actualizado)**

*   **Propósito:** Conecta con Evolution API para enviar WhatsApp. Gestiona una instancia y automatiza recordatorios de vencimiento.
*   **Componentes:**
    *   `settings.php`: Integra campos URL/Token en Configuración POS Base.
    *   `api-client.php`: Clase `Evolution_API_Client`.
    *   `admin-page.php` & `instance-manager.js`: UI y JS para gestionar instancia (Crear, QR, Estado, Desconectar, Eliminar, Log).
    *   `hooks.php`: Manejadores AJAX para página de gestión y para el modal de envío de mensajes (`pos_base_ajax_send_pos_sms`).
    *   `cron.php`: Tarea WP-Cron diaria (`pos_evolution_check_subscription_expiry`).
*   **Características:**
    *   **Gestión de Instancia:** UI completa en submenú "Evolution API".
    *   **Recordatorios Automáticos:** Cron diario busca suscripciones que vencen *hoy*, usa la **"Nota para el Cliente"** del pedido como mensaje, envía vía API si está configurada/conectada, marca pedido (`_pos_evo_reminder_sent_YYYY-MM-DD`) para evitar duplicados.
    *   **Integración Tabla Ventas:** Acción "Enviar Mensaje" en Columna 1 abre modal **Thickbox** con textarea para enviar mensaje personalizado vía AJAX.
    *   **Integración Metabox:** Muestra estado de envío del recordatorio.

## 6. API REST Personalizada

*   **Base:** `/pos-base/v1/` (Endpoints para POS: productos, clientes, carrito, pedido, calendario, **ventas-datatable**).
*   **Streaming:** `GET /pos-base/v1/streaming/available-profiles`

## 7. Configuración Clave (Opciones WP)

*   `pos_base_active_modules`: Array con los slugs de los módulos activos.
*   `pos_whatsapp_settings`: Array con los ajustes del módulo WhatsApp.
*   `pos_evolution_api_settings`: Array con los ajustes del módulo Evolution API (`api_url`, `token`, `managed_instance_name`).

## 8. Estado Operacional y Arquitectura

*   **Estado Funcional:** Todas las características y funcionalidades descritas para el núcleo de **POS Base** y los módulos **Streaming**, **WhatsApp** y **Evolution API** se encuentran implementadas y operativas.
*   **Arquitectura:** La interacción entre el núcleo y los módulos se basa **estrictamente en los hooks `pos_base_...`** definidos y hooks estándar de WordPress/WooCommerce.

---
