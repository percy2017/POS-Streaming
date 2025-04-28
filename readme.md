# POS Base: Punto de Venta Modular para WordPress y WooCommerce

[![Licencia: GPL v2 o posterior](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Requiere al menos](https://img.shields.io/badge/WordPress-5.5+-blue.svg)](https://wordpress.org/download/)
[![WooCommerce Requiere al menos](https://img.shields.io/badge/WooCommerce-4.0+-purple.svg)](https://wordpress.org/plugins/woocommerce/)
[![PHP Requiere al menos](https://img.shields.io/badge/PHP-7.4+-blueviolet.svg)](https://www.php.net/)

**POS Base** proporciona una solución de Punto de Venta (POS) robusta y flexible, integrada directamente en tu panel de administración de WordPress. Construido con un **enfoque central en la modularidad**, ofrece funcionalidades esenciales de POS permitiendo, al mismo tiempo, una extensión fluida a través de módulos dedicados para satisfacer diversas necesidades comerciales.

Este plugin transforma tu configuración de WordPress/WooCommerce en un eficiente centro de ventas, permitiendo la gestión directa de ventas, interacción con clientes y procesamiento de pedidos desde una interfaz unificada. Su arquitectura extensible lo hace ideal para negocios que requieren características de POS personalizadas más allá de las ofertas estándar, como gestión de suscripciones, integraciones de servicios (como WhatsApp vía Evolution API) o flujos de trabajo específicos de la industria.

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
*   **Historial de Ventas (Mejorado):** Registro de ventas potente, con búsqueda y ordenamiento, usando DataTables con procesamiento del lado del servidor.
    *   **Columnas Agrupadas:** Presenta la información en 5 columnas optimizadas:
        1.  `Pedido / Fecha / Tipo`: Incluye ID del pedido (enlace), fecha, tipo de venta (badge) y acciones (Ver Pedido, Enviar Mensaje).
        2.  `Cliente / Contacto`: Muestra avatar, nombre del cliente (enlace si es usuario registrado) y número de teléfono (enlace `tel:`).
        3.  `Producto(s)`: Lista los productos del pedido (nombre y cantidad).
        4.  `Vencimiento / Historial`: Muestra la fecha de vencimiento de la suscripción (si aplica) con formato legible y tiempo relativo (ej. "en 3 días", "hace 2 semanas"). Incluye estadísticas clave del cliente (Total Pedidos, Gasto Total, Valor Medio).
        5.  `Notas / Detalles`: Muestra la última "Nota para el Cliente" del pedido y detalles relevantes del POS como el Título de la Suscripción y el Perfil de Streaming asignado (si el módulo está activo). Columna con ancho ajustable vía CSS.
    *   **Búsqueda Mejorada:** La búsqueda del lado del servidor incluye ID, nombre/email cliente, nombre producto, número de teléfono y título de suscripción.
    *   **Ordenación Mejorada:** Permite ordenar por Fecha, Nombre de Cliente (aproximado), y Fecha de Vencimiento.
*   **Metabox en Pedido de WooCommerce:** Permite editar datos específicos del POS (Tipo de Venta, Suscripción Base, datos de módulos) directamente en la pantalla de edición de pedidos de WooCommerce. Muestra estado de envío de recordatorio (si módulo Evolution API está activo).
*   **API REST Segura:** Comunicación asíncrona (`/pos-base/v1/`) para una experiencia frontend rápida.
*   **Arquitectura Modular:** Extiende fácilmente la funcionalidad a través de módulos separados (ver abajo).
*   **Página de Configuración:** Activación/desactivación simple de los módulos detectados.

---

## ⚙️ Requisitos

*   **WordPress:** Versión 5.5 o superior.
*   **WooCommerce:** Versión 4.0 o superior.
*   **PHP:** Versión 7.4 o superior.
*   **WordPress Cron:** Debe estar habilitado y funcionando correctamente para las tareas programadas (ej. recordatorios).
*   **Thickbox:** Debe estar cargado en el admin para los modales (generalmente incluido por defecto, pero `add_thickbox()` se usa para asegurar).

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
    *   **POS:** Realiza operaciones de venta.
    *   **Calendario:** Visualiza los próximos vencimientos de "Suscripción Base".
    *   **Ventas:** Revisa el historial de transacciones del POS con la nueva estructura de tabla.
3.  **Configuración:**
    *   Navega a "POS Base" -> "Configuración".
    *   Activa los módulos deseados (ej., Streaming, Evolution API) marcando las casillas y guardando los cambios.
    *   Configura los ajustes específicos de cada módulo activo (ej., URL y Token para Evolution API).
4.  **Instrucciones Detalladas:** Para una guía paso a paso sobre el uso de la interfaz del POS, consulta el archivo `manual.md`.

---

## 🏗️ Arquitectura y Modularidad

POS Base está diseñado para la extensibilidad:

*   **Plugin Núcleo (`pos-base`):** Proporciona la interfaz de POS fundamental, APIs y características esenciales.
*   **Módulos (directorio `modules/`):** Contiene subdirectorios, cada uno representando un conjunto distinto de características (ej., `streaming`, `whatsapp`, `evolution-api`).
*   **Detección y Activación:** El plugin núcleo detecta automáticamente los módulos ubicados en el directorio `modules/`. Pueden ser habilitados/deshabilitados a través de la página "Configuración" (ajustes guardados en la opción `pos_base_active_modules`).
*   **Carga Dinámica:** El plugin núcleo carga el archivo principal (ej., `pos-{slug}-module.php`) de cada módulo *activo* durante la inicialización.
*   **Integración Basada en Hooks:** Los módulos interactúan con el núcleo y WordPress **exclusivamente a través de actions y filters**. Esto asegura la mantenibilidad y previene conflictos. Hooks clave proporcionados por POS Base incluyen (ejemplos):
    *   `pos_base_enqueue_module_scripts`: Para cargar JS/CSS específicos del módulo en la página del POS.
    *   `pos_base_register_module_rest_routes`: Para añadir endpoints de API REST específicos del módulo.
    *   `pos_base_checkout_fields`: Para añadir campos personalizados a la sección de checkout del POS.
    *   `pos_base_prepare_order_meta`: Para guardar datos del módulo cuando se crea un pedido.
    *   `pos_base_sales_datatable_meta_column`: **(Obsoleto/Reemplazado)** La tabla ahora se construye directamente en la API.
    *   `pos_base_metabox_fields`: Para añadir campos al metabox de edición de pedidos de WooCommerce.
    *   `pos_base_save_metabox_data`: Para guardar datos del módulo desde el metabox.
*   **Tareas Programadas (Cron):** Los módulos pueden registrar sus propias tareas WP-Cron (ej., el módulo Evolution API para recordatorios).

---

## 🧩 Módulos Incluidos

### 3.1. Módulo Streaming (`modules/streaming/`)

*   **Propósito:** Gestiona entidades de "Cuentas Streaming" y "Perfiles Streaming" (ej., para servicios como Netflix, Spotify) y permite asignar perfiles disponibles a ventas de tipo "Suscripción" realizadas a través del POS.
*   **Características:**
    *   **CPTs:** Registra `pos_account` (Cuentas) y `pos_profile` (Perfiles) con campos personalizados (estado, cuenta padre, etc.) y pantallas de administración accesibles bajo "POS Base".
    *   **Integración Checkout POS:** Añade un desplegable **Select2** al checkout del POS (solo para ventas tipo "Suscripción") listando perfiles disponibles (`_pos_profile_status` = 'available'), mostrando nombre del perfil y de la cuenta padre.
    *   **Meta del Pedido:** Guarda el ID del perfil seleccionado en el campo meta `_pos_assigned_profile_id` del pedido.
    *   **Actualización de Estado:** Establece automáticamente el estado del perfil seleccionado a 'assigned'.
    *   **Integración Metabox Edición Pedido:** Añade un desplegable HTML estándar al metabox del POS en la pantalla de edición de pedidos de WC, permitiendo ver/cambiar el perfil asignado post-venta y actualizando correctamente los estados de los perfiles.
    *   **Integración Tabla de Ventas:** Muestra el nombre del perfil asignado en la columna "Notas / Detalles" del historial de Ventas.
    *   **API REST:** Proporciona el endpoint `GET /pos-base/v1/streaming/available-profiles` para alimentar el desplegable Select2 en el frontend del POS.

### 3.2. Módulo WhatsApp (`modules/whatsapp/`)

*   **Propósito:** Integra puntos de contacto de WhatsApp en el frontend del sitio web.
*   **Características:**
    *   **Configuración:** Añade ajustes bajo "POS Base" -> "Configuración" para definir número de destino, mensaje por defecto, tooltip del botón y título del popup (guardado en `pos_whatsapp_settings`).
    *   **Botón Flotante:** Muestra un botón flotante en todo el sitio que abre un popup/modal, dirigiendo a los usuarios a WhatsApp con información pre-rellenada según los ajustes.
    *   **Botón Página de Producto:** Añade un botón "Consultar por WhatsApp" en las páginas de producto de WooCommerce.
    *   **Botón Página de Checkout:** Añade un botón "Completar Pedido por WhatsApp" en la página de checkout de WooCommerce.
    *   **Assets Frontend:** Encola CSS (`floating-button.css`) y JS (`floating-button.js`) específicos para la funcionalidad del botón/popup.

### 3.3. Módulo Evolution API (`modules/evolution-api/`) **(Nuevo)**

*   **Propósito:** Conecta el POS Base con una instancia de Evolution API para enviar mensajes de WhatsApp. Gestiona una única instancia de la API y automatiza el envío de recordatorios de vencimiento.
*   **Características:**
    *   **Página de Gestión:** Añade un submenú "POS Base" -> "Evolution API" para gestionar la conexión con la instancia:
        *   Crear nueva instancia.
        *   Conectar/Reconectar (Mostrar QR).
        *   Ver estado actual (conectado/desconectado, nombre perfil, etc.).
        *   Desconectar instancia.
        *   Eliminar instancia.
        *   Log de actividad.
    *   **Configuración:** Integra los campos para la URL de la API y el Token en la página principal de "Configuración" de POS Base.
    *   **Recordatorios Automáticos de Vencimiento:**
        *   Registra una tarea WP-Cron diaria (`pos_evolution_check_subscription_expiry`).
        *   Busca pedidos de tipo "Suscripción" cuya fecha de vencimiento (`_pos_subscription_expiry_date`) sea *hoy*.
        *   Si la API está configurada y conectada, envía automáticamente un mensaje de WhatsApp al cliente usando la **"Nota para el Cliente"** del pedido como contenido del mensaje.
        *   Utiliza un metadato en el pedido (`_pos_evo_reminder_sent_YYYY-MM-DD`) para evitar envíos duplicados el mismo día.
    *   **Integración Tabla de Ventas:**
        *   Añade la acción "Enviar Mensaje" en la primera columna de la tabla de Ventas.
        *   Al hacer clic, abre un modal (Thickbox) que permite escribir un mensaje personalizado y enviarlo al número de teléfono del cliente a través de la API de Evolution.
    *   **Integración Metabox Edición Pedido:** Muestra si el recordatorio de vencimiento fue enviado para ese pedido en su fecha de vencimiento.
    *   **API Client:** Incluye la clase `Evolution_API_Client` para encapsular las llamadas a la API externa.

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
    *   **DataTables Responsive Extension** (Licencia MIT - *Asegúrate de incluirla si la usas*)

---
