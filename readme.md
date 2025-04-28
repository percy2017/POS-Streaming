# POS Base: Punto de Venta Modular para WordPress y WooCommerce

[![Licencia: GPL v2 o posterior](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Requiere al menos](https://img.shields.io/badge/WordPress-5.5+-blue.svg)](https://wordpress.org/download/)
[![WooCommerce Requiere al menos](https://img.shields.io/badge/WooCommerce-4.0+-purple.svg)](https://wordpress.org/plugins/woocommerce/)
[![PHP Requiere al menos](https://img.shields.io/badge/PHP-7.4+-blueviolet.svg)](https://www.php.net/)

**POS Base** proporciona una solución de Punto de Venta (POS) robusta y flexible, integrada directamente en tu panel de administración de WordPress. Construido con un **enfoque central en la modularidad**, ofrece funcionalidades esenciales de POS permitiendo, al mismo tiempo, una extensión fluida a través de módulos dedicados para satisfacer diversas necesidades comerciales.

Este plugin transforma tu configuración de WordPress/WooCommerce en un eficiente centro de ventas, permitiendo la gestión directa de ventas, interacción con clientes y procesamiento de pedidos desde una interfaz unificada. Su arquitectura extensible lo hace ideal para negocios que requieren características de POS personalizadas más allá de las ofertas estándar, como gestión de suscripciones, integraciones de servicios (como WhatsApp) o flujos de trabajo específicos de la industria.

---

## ✨ Características Clave (Plugin Núcleo)

*   **Interfaz de POS Intuitiva:** Una interfaz limpia basada en pestañas (POS, Calendario, Ventas) accesible desde el menú de administración de WordPress.
*   **Gestión de Productos:** Búsqueda inteligente (nombre/SKU), visualización de productos destacados, soporte para productos simples y variables.
*   **Gestión de Clientes:** Búsqueda de clientes existentes de WooCommerce, creación/edición de clientes vía modal (con soporte para avatar), opción de venta como invitado.
*   **Carrito Dinámico:** Añadir/eliminar productos, modificar cantidad, **edición de precios en tiempo real** directamente en el carrito.
*   **Checkout Flexible:**
    *   Selección de cliente.
    *   Integración de métodos de pago (pasarelas de WooCommerce + Manual/POS).
    *   Soporte para cupones de WooCommerce.
    *   Selección de **"Tipo de Venta"** (Directo, Suscripción, Crédito).
    *   Campos para **"Suscripción Base"** (Título, Fecha de Vencimiento, Color) para seguimiento básico.
    *   Notas del pedido.
*   **Creación de Pedidos:** Genera pedidos estándar de WooCommerce.
*   **Vista de Calendario:** Visualiza las fechas de vencimiento de la "Suscripción Base" usando FullCalendar.
*   **Historial de Ventas:** Registro de ventas potente, con búsqueda y ordenamiento, usando DataTables con procesamiento del lado del servidor para un rendimiento óptimo.
*   **Metabox en Pedido de WooCommerce:** Permite editar datos específicos del POS (Tipo de Venta, Suscripción Base, datos de módulos) directamente en la pantalla de edición de pedidos de WooCommerce.
*   **API REST Segura:** Comunicación asíncrona (`/pos-base/v1/`) para una experiencia frontend rápida.
*   **Arquitectura Modular:** Extiende fácilmente la funcionalidad a través de módulos separados (ver abajo).
*   **Página de Configuración:** Activación/desactivación simple de los módulos detectados.

---

## ⚙️ Requisitos

*   **WordPress:** Versión 5.5 o superior.
*   **WooCommerce:** Versión 4.0 o superior.
*   **PHP:** Versión 7.4 o superior.

---

## 🚀 Instalación

1.  **Descarga:**
    *   Clona el repositorio: `git clone https://github.com/tu-usuario/pos-base.git` en tu directorio `wp-content/plugins/`.
    *   O descarga el archivo `.zip` desde las releases del repositorio.
2.  **Subida (si usas ZIP):**
    *   Navega a tu Admin de WordPress -> Plugins -> Añadir Nuevo -> Subir Plugin.
    *   Elige el archivo `.zip` descargado y haz clic en "Instalar Ahora".
3.  **Activación:**
    *   Navega a WordPress Admin -> Plugins.
    *   Localiza "POS Base" y haz clic en "Activar".

---

## 🛠️ Uso y Primeros Pasos

1.  **Acceso:** Encuentra el elemento de menú "**POS Base**" en la barra lateral de administración de WordPress.
2.  **Interfaz:** Explora las pestañas principales:
    *   **POS:** Realiza operaciones de venta (buscar productos/clientes, gestionar carrito, checkout).
    *   **Calendario:** Visualiza los próximos vencimientos de "Suscripción Base".
    *   **Ventas:** Revisa el historial de transacciones del POS.
3.  **Configuración:**
    *   Navega a "POS Base" -> "Configuración".
    *   Activa los módulos deseados (ej., Streaming, WhatsApp) marcando las casillas y guardando los cambios. Las características específicas del módulo estarán disponibles en la interfaz del POS u otras áreas relevantes.
4.  **Instrucciones Detalladas:** Para una guía paso a paso sobre el uso de la interfaz del POS, consulta el archivo `manual.md`.

---

## 🏗️ Arquitectura y Modularidad

POS Base está diseñado para la extensibilidad:

*   **Plugin Núcleo (`pos-base`):** Proporciona la interfaz de POS fundamental, APIs y características esenciales.
*   **Módulos (directorio `modules/`):** Contiene subdirectorios, cada uno representando un conjunto distinto de características (ej., `streaming`, `whatsapp`).
*   **Detección y Activación:** El plugin núcleo detecta automáticamente los módulos ubicados en el directorio `modules/`. Pueden ser habilitados/deshabilitados a través de la página "Configuración" (ajustes guardados en la opción `pos_base_active_modules`).
*   **Carga Dinámica:** El plugin núcleo carga el archivo principal (ej., `pos-{slug}-module.php`) de cada módulo *activo* durante la inicialización.
*   **Integración Basada en Hooks:** Los módulos interactúan con el núcleo y WordPress **exclusivamente a través de actions y filters**. Esto asegura la mantenibilidad y previene conflictos. Hooks clave proporcionados por POS Base incluyen (ejemplos):
    *   `pos_base_enqueue_module_scripts`: Para cargar JS/CSS específicos del módulo en la página del POS.
    *   `pos_base_register_module_rest_routes`: Para añadir endpoints de API REST específicos del módulo.
    *   `pos_base_checkout_fields`: Para añadir campos personalizados a la sección de checkout del POS.
    *   `pos_base_prepare_order_meta`: Para guardar datos del módulo cuando se crea un pedido.
    *   `pos_base_sales_datatable_meta_column`: Para mostrar datos del módulo en la tabla del historial de Ventas.
    *   `pos_base_metabox_fields`: Para añadir campos al metabox de edición de pedidos de WooCommerce.
    *   `pos_base_save_metabox_data`: Para guardar datos del módulo desde el metabox.

---

## 🧩 Módulos Incluidos

### 3.1. Módulo Streaming (`modules/streaming/`)

*   **Propósito:** Gestiona entidades de "Cuentas Streaming" y "Perfiles Streaming" (ej., para servicios como Netflix, Spotify) y permite asignar perfiles disponibles a ventas de tipo "Suscripción" realizadas a través del POS.
*   **Características:**
    *   **CPTs:** Registra `pos_account` (Cuentas) y `pos_profile` (Perfiles) con campos personalizados (estado, cuenta padre, etc.) y pantallas de administración accesibles bajo "POS Base".
    *   **Integración Checkout POS:** Añade un desplegable **Select2** al checkout del POS (solo para ventas tipo "Suscripción") listando perfiles disponibles (`_pos_profile_status` = 'available'), mostrando nombre del perfil y de la cuenta padre.
    *   **Meta del Pedido:** Guarda el ID del perfil seleccionado en el campo meta `_pos_assigned_profile_id` del pedido.
    *   **Actualización de Estado:** Establece automáticamente el estado del perfil seleccionado a 'assigned'.
    *   **Integración Metabox Edición Pedido:** Añade un desplegable HTML estándar (sin Select2) al metabox del POS en la pantalla de edición de pedidos de WC, permitiendo ver/cambiar el perfil asignado post-venta y actualizando correctamente los estados de los perfiles.
    *   **Integración Tabla de Ventas:** Muestra el nombre del perfil asignado en la columna "Meta" del historial de Ventas.
    *   **API REST:** Proporciona el endpoint `GET /pos-base/v1/streaming/available-profiles` para alimentar el desplegable Select2 en el frontend del POS.

### 3.2. Módulo WhatsApp (`modules/whatsapp/`)

*   **Propósito:** Integra puntos de contacto de WhatsApp en el frontend del sitio web.
*   **Características:**
    *   **Configuración:** Añade ajustes bajo "POS Base" -> "Configuración" para definir número de destino, mensaje por defecto, tooltip del botón y título del popup (guardado en `pos_whatsapp_settings`).
    *   **Botón Flotante:** Muestra un botón flotante en todo el sitio que abre un popup/modal, dirigiendo a los usuarios a WhatsApp con información pre-rellenada según los ajustes.
    *   **Botón Página de Producto:** Añade un botón "Consultar por WhatsApp" en las páginas de producto de WooCommerce, pre-rellenando el mensaje con detalles del producto.
    *   **Botón Página de Checkout:** Añade un botón "Completar Pedido por WhatsApp" en la página de checkout de WooCommerce.
    *   **Assets Frontend:** Encola CSS (`floating-button.css`) y JS (`floating-button.js`) específicos para la funcionalidad del botón/popup.

---

## 🧑‍💻 Desarrollo de Módulos Personalizados

1.  **Crear Directorio:** Añade una nueva carpeta dentro de `wp-content/plugins/pos-base/modules/tu-modulo-slug/`.
2.  **Crear Archivo Principal:** Dentro del directorio de tu módulo, crea `pos-tu-modulo-slug-module.php`. Este archivo será cargado por el núcleo si el módulo está activo.
3.  **Estructura:** Organiza el código de tu módulo (ej., `includes/`, `assets/`, `templates/`).
4.  **Enganchar (Hook In):** Desde tu archivo principal del módulo, usa `require_once` para incluir otros archivos necesarios y usa `add_action()` y `add_filter()` para adjuntar las funciones de tu módulo a los hooks de WordPress y de POS Base.
    ```php
    <?php
    // Ejemplo: pos-tu-modulo-slug-module.php
    defined( 'ABSPATH' ) or die();

    define( 'TU_MODULO_DIR', plugin_dir_path( __FILE__ ) );
    define( 'TU_MODULO_URL', plugin_dir_url( __FILE__ ) );

    // require_once TU_MODULO_DIR . 'includes/tus-funciones.php';
    // require_once TU_MODULO_DIR . 'includes/tus-hooks.php';

    // add_action( 'pos_base_checkout_fields', 'tu_modulo_add_campo_checkout' );
    ?>
    ```
5.  **Implementar:** Construye tus características específicas (CPTs, APIs, elementos UI, lógica).
6.  **Activar:** Ve a "POS Base" -> "Configuración" y habilita tu nuevo módulo.

---

## 🙌 Contribuciones

¡Las contribuciones son bienvenidas! Por favor, sigue las prácticas estándar de GitHub:

1.  Haz un Fork del repositorio.
2.  Crea una rama para tu característica (`git checkout -b feature/CaracteristicaIncreible`).
3.  Confirma tus cambios (`git commit -m 'Añade alguna CaracteristicaIncreible'`).
4.  Empuja a la rama (`git push origin feature/CaracteristicaIncreible`).
5.  Abre un Pull Request.

Por favor, adhiérete a los Estándares de Codificación de WordPress.

---

## 📜 Licencia

POS Base y sus módulos incluidos están licenciados bajo la **GPL v2 o posterior**.
Consulta la GNU General Public License v2.0 para más detalles.

---

## 🙏 Créditos y Agradecimientos

*   **Desarrollador Principal:** Ing. Percy Alvarez
*   **Librerías Externas:**
    *   Select2 (Licencia MIT)
    *   DataTables (Licencia MIT)
    *   SweetAlert2 (Licencia MIT)
    *   FullCalendar (Licencia MIT)
    *   Intl-Tel-Input (Licencia MIT)

---
