# POS Base - Punto de Venta Modular para WordPress/WooCommerce

![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)
<!-- Añadir más badges si es relevante (versión, build etc.) -->

**POS Base** es un plugin fundacional para WordPress y WooCommerce que proporciona una interfaz de Punto de Venta (POS) básica y, lo más importante, una **arquitectura modular** diseñada para ser extendida fácilmente. Permite añadir funcionalidades específicas para diferentes tipos de negocio (streaming, hoteles, restaurantes, etc.) a través de módulos independientes sin modificar el núcleo base.

## Características Principales (Núcleo Base)

El plugin `pos-base` incluye la siguiente funcionalidad esencial:

*   **Interfaz de Pestañas:** Navegación clara entre POS, Calendario y Ventas.
*   **Gestión de Productos:**
    *   Búsqueda de productos por nombre/SKU.
    *   Visualización inicial de productos destacados.
*   **Gestión de Clientes:**
    *   Búsqueda de clientes existentes (nombre, email, teléfono).
    *   Creación y edición de clientes a través de un modal.
    *   Soporte para avatar personalizado por cliente.
*   **Carrito de Compras:**
    *   Añadir productos simples y variables.
    *   Modificar cantidad.
    *   **Modificar precio unitario directamente en el carrito.**
    *   Eliminar productos.
    *   Visualización de subtotal y total.
*   **Checkout:**
    *   Selección de cliente (o venta como invitado).
    *   Selección de método de pago (obtenidos de WC + opción manual POS).
    *   Aplicación de cupones de WooCommerce.
    *   Selección de **Tipo de Venta** (Directo, Suscripción, Crédito).
    *   Campos para **Suscripción Base** (Título para calendario, Fecha de Vencimiento, Color).
    *   Campo para añadir notas privadas al pedido.
    *   Creación del pedido en WooCommerce (marcado como completado por defecto, excepto crédito).
*   **Calendario:**
    *   Visualización (usando FullCalendar) de los vencimientos de las **Suscripciones Base** creadas desde el POS.
*   **Tabla de Ventas:**
    *   Historial de pedidos creados desde el POS (usando DataTables).
    *   Columnas para Pedido #, Fecha, Cliente, Total, Tipo (POS), Notas, **Meta** (muestra detalles de Suscripción Base).
    *   Funcionalidad Server-Side para eficiencia.
*   **Configuración:**
    *   Página para **activar/desactivar módulos** detectados automáticamente.
*   **API REST Base (`/pos-base/v1/`):**
    *   Endpoints para todas las operaciones del frontend (productos, clientes, pagos, cupones, pedidos, calendario, ventas).
    *   Validación y sanitización de argumentos.
    *   Autenticación basada en nonce y capacidad `manage_woocommerce`.

## Arquitectura Modular

La clave de POS Base es su extensibilidad:

1.  **Núcleo (`pos-base`):** Contiene toda la funcionalidad común descrita arriba y la interfaz principal. Reside en el directorio raíz del plugin.
2.  **Directorio `modules/`:** Dentro de `pos-base`, esta carpeta contiene subdirectorios, donde cada subdirectorio representa un módulo (ej: `modules/streaming/`, `modules/hotel/`).
3.  **Detección y Activación:**
    *   El plugin base escanea `modules/` para encontrar módulos potenciales.
    *   La página "POS Base" -> "Configuración" muestra estos módulos con checkboxes.
    *   Al guardar, los slugs de los módulos activos se guardan en la opción `pos_base_active_modules` de WordPress.
4.  **Carga Dinámica:**
    *   En el hook `plugins_loaded`, `pos-base.php` lee la opción `pos_base_active_modules`.
    *   Para cada módulo activo, incluye (`require_once`) su archivo principal (se asume `modules/{slug}/pos-{slug}-module.php` por defecto).
5.  **Integración vía Hooks:**
    *   El núcleo `pos-base` ofrece numerosos **actions** y **filters** en puntos clave.
    *   Los módulos **deben** usar estos hooks para añadir su funcionalidad sin modificar el código base. Ejemplos:
        *   `add_action('init', 'mi_modulo_registrar_cpts');`
        *   `add_action('pos_base_enqueue_module_scripts', 'mi_modulo_cargar_assets');`
        *   `add_action('pos_base_register_module_rest_routes', 'mi_modulo_registrar_api');`
        *   `add_action('pos_base_checkout_fields', 'mi_modulo_mostrar_campos_extra');` (Hook a crear si no existe)
        *   `add_filter('pos_base_prepare_order_meta', 'mi_modulo_guardar_meta_extra', 10, 2);` (Hook a crear si no existe)
        *   `add_filter('pos_base_sales_datatable_meta_column', 'mi_modulo_mostrar_meta_extra', 10, 3);` (Hook a crear si no existe)

## Instalación

1.  **Dependencia:** Requiere **WooCommerce** activo.
2.  **Método 1 (Archivo ZIP):**
    *   Descarga la última versión desde GitHub Releases.
    *   Ve a tu panel de WordPress -> Plugins -> Añadir Nuevo -> Subir Plugin.
    *   Selecciona el archivo ZIP descargado e instálalo.
3.  **Método 2 (Git):**
    *   Clona este repositorio en tu directorio `wp-content/plugins/`:
        ```bash
        git clone https://github.com/tu-usuario/pos-base.git pos-base
        ```
    *   (Opcional) Si necesitas compilar assets (JS/CSS), sigue las instrucciones de desarrollo (si aplica).
4.  **Activación:** Ve a Plugins en tu panel de WordPress y activa "POS Base".

## Uso Básico

*   Accede a la interfaz principal del POS a través del menú "POS Base" en el panel de administración de WordPress.
*   Navega entre las pestañas POS, Calendario y Ventas.
*   Ve a "POS Base" -> "Configuración" para activar los módulos específicos que necesites (ej: Streaming, Hotel).

*(Para instrucciones detalladas de uso para el usuario final, consulta el archivo `manual.md`)*

## Desarrollo de Módulos

Para crear un nuevo módulo (ej: "MiModulo"):

1.  **Crea el Directorio:** `wp-content/plugins/pos-base/modules/mi-modulo/`
2.  **Crea el Archivo Principal:** `modules/mi-modulo/pos-mi-modulo-module.php`. Este archivo será cargado por `pos-base` si el módulo está activo.
3.  **Estructura Interna:** Organiza tu código dentro del directorio del módulo (ej: `includes/`, `assets/`, `templates/`, `mi-modulo-cpts.php`, `mi-modulo-api.php`).
4.  **Incluye y Engancha:** Desde `pos-mi-modulo-module.php`:
    *   Incluye (`require_once`) los demás archivos PHP de tu módulo.
    *   Usa `add_action` y `add_filter` para enganchar las funciones de tu módulo a los hooks de WordPress y a los hooks específicos de `pos-base`.
    *   **Ejemplo `pos-mi-modulo-module.php`:**
        ```php
        <?php
        // Evitar acceso directo
        defined( 'ABSPATH' ) or die();

        define( 'MI_MODULO_DIR', plugin_dir_path( __FILE__ ) );
        define( 'MI_MODULO_URL', plugin_dir_url( __FILE__ ) );

        // Incluir archivos del módulo
        require_once MI_MODULO_DIR . 'includes/mi-modulo-cpts.php';
        require_once MI_MODULO_DIR . 'includes/mi-modulo-api.php';
        require_once MI_MODULO_DIR . 'includes/mi-modulo-hooks.php'; // Archivo que contiene los add_action/add_filter

        // Enganchar funciones principales (ejemplos)
        add_action( 'init', 'mi_modulo_registrar_cpts_tax' ); // Definida en mi-modulo-cpts.php
        add_action( 'pos_base_register_module_rest_routes', 'mi_modulo_registrar_rutas_api' ); // Definida en mi-modulo-api.php
        add_action( 'pos_base_enqueue_module_scripts', 'mi_modulo_enqueue_assets' ); // Definida en mi-modulo-hooks.php
        ?>
        ```
5.  **Funcionalidad:** Implementa tus CPTs, taxonomías, lógica de negocio, endpoints API, JS/CSS específicos dentro de tu módulo.
6.  **Activación:** Ve a la configuración de POS Base y activa tu nuevo módulo.

## Contribuciones

Las contribuciones son bienvenidas. Por favor, sigue estos pasos:

1.  Haz un Fork del repositorio.
2.  Crea una nueva rama para tu funcionalidad (`git checkout -b feature/nueva-funcionalidad`).
3.  Realiza tus cambios y haz commit (`git commit -am 'Añade nueva funcionalidad X'`).
4.  Empuja tu rama (`git push origin feature/nueva-funcionalidad`).
5.  Abre un Pull Request.

*(Por favor, sigue los estándares de codificación de WordPress).*

## Licencia

Este plugin está licenciado bajo la GPL v2 or later. Ver el archivo `LICENSE` para más detalles (si existe).
