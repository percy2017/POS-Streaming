# Contexto del Proyecto: Plugin POS Base para WordPress (prompt.md)

**Instrucciones para el Usuario:** Copia y pega TODO el contenido de este archivo al inicio de cada nueva sesión de chat conmigo (Gemini/IA) para proporcionarme el contexto completo del plugin `pos-base`.

---

## 1. Descripción General y Propósito

*   **Nombre:** `pos-base`
*   **Autor:** Ing. Percy Alvarez (https://percyalvarez.com/)
*   **Licencia:** GPL v2 o posterior.
*   **Propósito Principal:** Plataforma de Punto de Venta (POS) para WordPress/WooCommerce con una **arquitectura modular** central. Ofrece una interfaz de venta directa en el admin de WP y permite la extensión mediante módulos para funcionalidades específicas (streaming, WhatsApp, etc.).
*   **Entorno:** WordPress 5.5+, WooCommerce 4.0+, PHP 7.4+.

## 2. Tecnologías y Librerías Clave

*   **Backend:** PHP, WordPress API (Hooks, Options, Metabox, CPT, REST API), WooCommerce API.
*   **Frontend (Interfaz POS):** JavaScript (jQuery, wp-util), HTML5, CSS3 (`assets/css/app.css`).
*   **Librerías Externas (Vendor - cargadas por `pos-base.php`):**
    *   **Select2:** (`assets/vendor/select2/`) - Mejora de selectores (usada en POS frontend y metabox de edición de pedido).
    *   **DataTables:** (`assets/vendor/datatables/`) - Tabla de historial de Ventas (server-side).
    *   **SweetAlert2:** (`assets/vendor/sweetalert2/`) - Notificaciones visuales.
    *   **FullCalendar:** (`assets/vendor/fullcalendar/`) - Vista de Calendario.
    *   **Intl-Tel-Input:** (`assets/vendor/intl-tel-input/`) - Campo de teléfono internacional.
*   **Componentes WP:** Thickbox (modales), wp-pointer (tour), wp-media (avatar).

## 3. Arquitectura y Archivos Clave

*   **Núcleo (`pos-base`):** Contiene la funcionalidad base y la interfaz principal.
*   **Módulos (`modules/`):** Subdirectorios para funcionalidades extendidas (`streaming`, `whatsapp`).
*   **Carga de Módulos:**
    *   La opción `pos_base_active_modules` (array de slugs) define los módulos activos.
    *   `pos-base.php` carga `modules/{slug}/pos-{slug}-module.php` para cada módulo activo en `plugins_loaded`.
*   **Integración:** **Exclusivamente mediante Hooks** (actions/filters) definidos en el núcleo (`pos_base_...`) y WordPress.
*   **Archivos Principales:**
    *   `pos-base.php`: Inicialización, carga de módulos, **`pos_base_enqueue_assets`** (carga JS/CSS base y vendor para la interfaz POS), **`pos_base_enqueue_select2_for_order_edit`** (carga Select2 para metabox), definición de hooks base, registro API base.
    *   `pos-metabox.php`: Lógica del metabox en la pantalla de edición de pedidos de WC (`pos_base_add_order_metabox`, `pos_base_order_metabox_callback`, `pos_base_save_order_meta_data`).
    *   `assets/js/app.js`: JavaScript principal para la interfaz del POS.
    *   `modules/{slug}/pos-{slug}-module.php`: Punto de entrada de cada módulo (incluye otros archivos del módulo y registra hooks).

## 4. Funcionalidades del Núcleo

*   **Interfaz POS (Pestaña POS):** Búsqueda productos/clientes, gestión carrito (precio editable), checkout (Tipo Venta, Suscripción Base), creación pedido WC.
*   **Calendario (Pestaña Calendario):** Vista FullCalendar de vencimientos de "Suscripción Base".
*   **Historial (Pestaña Ventas):** Tabla DataTables (server-side) de pedidos del POS, con columna "Meta" extensible por módulos.
*   **Metabox Edición Pedido WC:** Permite editar datos del POS (`_pos_sale_type`, `_pos_subscription_...`, y datos de módulos) post-creación, utilizando Select2 donde corresponde.

## 5. Módulos Activos y Funcionalidades

### 5.1. Módulo Streaming (`modules/streaming/`)

*   **Propósito:** Gestionar y asignar "Perfiles Streaming" a ventas de tipo "Suscripción".
*   **Componentes:**
    *   **CPTs:** `pos_account` (Cuentas), `pos_profile` (Perfiles con `_pos_profile_status`, `_pos_profile_parent_account_id`). Registrados por `streaming-cpts.php`.
    *   **Admin:** Submenús y columnas personalizadas para CPTs. Metaboxes para CPTs definidos en `streaming-metaboxes.php`.
    *   **API:** Endpoint `GET /pos-base/v1/streaming/available-profiles` (definido en `streaming-api.php`) devuelve perfiles disponibles (ID, título perfil, título cuenta) para el frontend.
    *   **JS Frontend POS:** `assets/js/streaming-app.js` (cargado por `streaming-assets.php` vía hook `pos_base_enqueue_module_scripts`) maneja el selector.
    *   **Integración Checkout POS:** Añade (vía hook `pos_base_checkout_fields`) un selector **Select2** para `_pos_assigned_profile_id` cuando `Tipo Venta = Suscripción`.
    *   **Guardado (Checkout):** Guarda `_pos_assigned_profile_id` y actualiza `_pos_profile_status` a 'assigned' (vía hook `pos_base_prepare_order_meta`).
    *   **Integración Metabox Edición Pedido:** Añade (vía hook `pos_base_metabox_fields`) un selector **Select2** para `_pos_assigned_profile_id`. Guarda y actualiza estados correctamente (vía hook `pos_base_save_metabox_data`).
    *   **Integración Tabla Ventas:** Muestra el nombre del perfil asignado en la columna "Meta" (vía hook `pos_base_sales_datatable_meta_column`).

### 5.2. Módulo WhatsApp (`modules/whatsapp/`)

*   **Propósito:** Añadir puntos de contacto de WhatsApp en el frontend del sitio.
*   **Componentes:**
    *   **Configuración:** Añade campos a la página de ajustes de POS Base (Número, Mensaje, Tooltip, Título) guardados en `pos_whatsapp_settings`.
    *   **Frontend:**
        *   Añade botón flotante + popup (vía `wp_footer`).
        *   Añade botón en página de producto WC (vía `woocommerce_single_product_summary`).
        *   Añade botón en página de checkout WC (vía `woocommerce_review_order_after_submit`).
    *   **Assets:** Carga `assets/css/floating-button.css` y `assets/js/floating-button.js` en el frontend (vía `wp_enqueue_scripts`).

## 6. API REST Personalizada

*   **Base:** `/pos-base/v1/` (Endpoints para operaciones del POS: productos, clientes, carrito, pedido, calendario, ventas).
*   **Streaming:** `GET /pos-base/v1/streaming/available-profiles`

## 7. Configuración Clave (Opciones WP)

*   `pos_base_active_modules`: Array con los slugs de los módulos activos.
*   `pos_whatsapp_settings`: Array con los ajustes del módulo WhatsApp.

## 8. Estado Operacional y Arquitectura

*   **Estado Funcional:** Todas las características y funcionalidades descritas para el núcleo de **POS Base** y los módulos **Streaming** y **WhatsApp** se encuentran implementadas, operativas y actualmente en producción.
*   **Arquitectura:** Se reitera que la interacción entre el núcleo y los módulos se basa **estrictamente en los hooks `pos_base_...`** definidos, asegurando la integridad y mantenibilidad del sistema.

---
