<?php
/**
 * Plugin Name:       POS BASE
 * Plugin URI:        https://percyalvarez.com/plugins-wordpress
 * Description:       Plugin base para Punto de Venta (POS) en WordPress/WooCommerce, con soporte para módulos extensibles.
 * Version:           1.1.0
 * Author:            Ing. Percy Alvarez
 * Author URI:        https://percyalvarez.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pos-base
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   8.x
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );


// --- Constantes del Plugin ---
define( 'POS_BASE_VERSION', '1.1.0' ); // Versión del plugin base
define( 'POS_BASE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POS_BASE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'POS_BASE_PLUGIN_FILE', __FILE__ );
define( 'POS_BASE_ASSETS_URL', POS_BASE_PLUGIN_URL . 'assets/' );

// Declarar compatibilidad con HPOS
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', POS_BASE_PLUGIN_FILE, true );
    }
} );

/**
 * Función principal de inicialización del plugin base.
 * Se ejecuta después de que todos los plugins estén cargados.
 */
function pos_base_init() {

    // --- Comprobación de WooCommerce ---
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'pos_base_woocommerce_inactive_notice' );
        return;
    }

    // --- Inclusión de Archivos Principales (Sólo si WC está activo) ---
    // Estos archivos contendrán la lógica *común* del POS
    require_once POS_BASE_PLUGIN_DIR . 'pos-page.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-api.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-metabox.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-setting.php'; // <--- AÑADE ESTA LÍNEA

    // --- Carga Dinámica de Módulos Activos ---
    $active_modules = get_option( 'pos_base_active_modules', [] );
    if ( ! empty( $active_modules ) && is_array( $active_modules ) ) {
        foreach ( $active_modules as $module_slug ) {
            $module_slug = sanitize_key( $module_slug );
            $module_file = POS_BASE_PLUGIN_DIR . 'modules/' . $module_slug . '/pos-' . $module_slug . '-module.php';
            if ( file_exists( $module_file ) && is_readable( $module_file ) ) {
                require_once $module_file;
            } else {
                 error_log( "POS Base WARNING: Módulo activo '" . $module_slug . "' no encontrado o no legible en: " . $module_file );
            }
        }
    }


    // --- Carga del Text Domain para Traducciones del Plugin Base ---
    load_plugin_textdomain(
        'pos-base', // Nuevo text domain base
        false,
        dirname( plugin_basename( POS_BASE_PLUGIN_FILE ) ) . '/languages/'
    );

    // --- Encolar Scripts y Estilos Base ---
    add_action( 'admin_enqueue_scripts', 'pos_base_enqueue_assets' );

    // --- Filtro para Avatar Personalizado (Se mantiene por ahora, podría ir a un módulo de "clientes avanzados") ---
    add_filter( 'get_avatar', 'pos_base_get_custom_avatar', 10, 5 );

    // --- Hook para Metabox de Pedido (Funcionalidad base) ---
    add_action( 'add_meta_boxes', 'pos_base_add_order_metabox', 10, 2 );

    // --- Hook para que los módulos registren sus rutas API ---
    // Se llama dentro de pos_streaming_register_rest_routes en pos-api.php
    // do_action( 'pos_base_register_module_rest_routes', 'pos_streaming/v1' ); // Ejemplo, la llamada real está en pos-api.php

    // --- Hook para que los módulos añadan sus propios CPTs, taxonomías, etc. ---
    // Los módulos pueden usar el hook 'init' directamente.

}
add_action( 'plugins_loaded', 'pos_base_init' );


/**
 * Muestra un aviso en el admin si WooCommerce no está activo.
 */
if ( ! function_exists( 'pos_base_woocommerce_inactive_notice' ) ) {
    function pos_base_woocommerce_inactive_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'El plugin "POS Base" requiere que WooCommerce esté activo y funcionando.', 'pos_base' ); // Mantenemos text-domain viejo por compatibilidad de traducción ?></p>
        </div>
        <?php
    }
}


// --- Hooks de Activación / Desactivación ---
function pos_base_activate() {
    // Código de activación del plugin base (ej: flush rewrite rules si se añaden CPTs base)
    // Los módulos deben manejar su propia activación/desactivación si es necesario.
}
register_activation_hook( POS_BASE_PLUGIN_FILE, 'pos_base_activate' );

function pos_base_deactivate() {
    // Código de desactivación del plugin base
}
register_deactivation_hook( POS_BASE_PLUGIN_FILE, 'pos_base_deactivate' );


/**
 * Enqueue scripts and styles for the admin area (POS Page).
 */
function pos_base_enqueue_assets( $hook_suffix ) {

    // Solo cargar en la página principal del POS
    $pos_page_hook = 'toplevel_page_pos-base'; // <-- ¡OJO! El slug del menú aún es 'pos_base'. Deberías cambiarlo también en pos-page.php si quieres consistencia total.
                                                    // Si lo cambias a 'pos-base', actualiza esta variable.

    if ( $hook_suffix !== $pos_page_hook ) {
        return;
    }

    // Dependencias Nativas
    add_thickbox();
    wp_enqueue_media();

    // Estilos Vendor (comunes)
    wp_enqueue_style( 'pos_base-datatables', POS_BASE_ASSETS_URL . 'vendor/datatables/datatables.min.css', array(), '1.13.8' );
    wp_enqueue_style( 'pos_base-sweetalert2', POS_BASE_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.min.css', array(), '11.10.6' );
    wp_enqueue_style( 'pos_base-intl-tel-input', POS_BASE_ASSETS_URL . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '18.2.1' );
    // FullCalendar se podría cargar condicionalmente si solo lo usa un módulo

    // Estilo Base del POS
    wp_enqueue_style(
        'pos-base-style', // Nuevo handle
        POS_BASE_ASSETS_URL . 'style.css',
        array('pos_base-datatables', 'pos_base-sweetalert2', 'pos_base-intl-tel-input', 'thickbox'), // Dependencias vendor
        filemtime( POS_BASE_PLUGIN_DIR . 'assets/style.css' )
    );

    // Scripts Vendor (comunes)
    wp_enqueue_script( 'pos_base-datatables', POS_BASE_ASSETS_URL . 'vendor/datatables/datatables.min.js', array('jquery'), '1.13.8', true );
    wp_enqueue_script( 'pos_base-sweetalert2', POS_BASE_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.all.min.js', array(), '11.10.6', true );
    wp_enqueue_script( 'pos_base-fullcalendar', POS_BASE_ASSETS_URL . 'vendor/fullcalendar/index.global.min.js', array(), '6.1.11', true ); // Cargar siempre por ahora
    wp_enqueue_script( 'pos_base-intl-tel-input', POS_BASE_ASSETS_URL . 'vendor/intl-tel-input/js/intlTelInputWithUtils.min.js', array('jquery'), '18.2.1', true );

    // Script Base de la Aplicación POS
    wp_enqueue_script(
        'pos-base-app', // Nuevo handle
        POS_BASE_ASSETS_URL . 'app.js',
        array('jquery', 'wp-util', 'thickbox', 'pos_base-datatables', 'pos_base-sweetalert2', 'pos_base-fullcalendar', 'pos_base-intl-tel-input'), // Dependencias vendor y WP
        filemtime( POS_BASE_PLUGIN_DIR . 'assets/app.js' ),
        true
    );

    // Localize Script Base
    wp_localize_script(
        'pos-base-app', // Usar el nuevo handle del script base
        'posBaseParams', // Nuevo nombre para el objeto JS
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'rest_url' => esc_url_raw( rest_url( 'pos-base/v1/' ) ), // El namespace de la API sigue siendo el mismo por ahora
            'admin_url' => admin_url(),
            'intlTelInputUtilsScript' => esc_url( POS_BASE_ASSETS_URL . 'vendor/intl-tel-input/js/utils.js' ),
            'default_avatar_url' => get_avatar_url( 0, ['size' => 96, 'default' => 'mystery'] ),
            'i18n' => array(
                // Mantenemos 'pos_base' aquí por compatibilidad con archivos .po/.mo existentes
                'loading' => __( 'Cargando...', 'pos_base' ),
                'saving' => __( 'Guardando...', 'pos_base' ),
                'processing' => __( 'Procesando...', 'pos_base' ),
                'error_general' => __( 'Ocurrió un error inesperado.', 'pos_base' ),
                'search_placeholder' => __( 'Buscar producto por nombre o SKU...', 'pos_base' ),
                'search_customer_placeholder' => __( 'Buscar cliente por nombre, email o teléfono...', 'pos_base' ),
                'no_products_found' => __( 'No se encontraron productos.', 'pos_base' ),
                'no_featured_products_found' => __( 'No hay productos destacados.', 'pos_base' ),
                'add_to_cart' => __( 'Añadir', 'pos_base' ),
                'select_variation' => __( 'Selecciona opción:', 'pos_base' ),
                'instock' => __( 'En stock', 'pos_base' ),
                'outofstock' => __( 'Agotado', 'pos_base' ),
                'cart_empty' => __( 'El carrito está vacío.', 'pos_base' ),
                'add_customer' => __( 'Añadir Nuevo Cliente', 'pos_base' ),
                'edit_customer' => __( 'Editar Cliente', 'pos_base' ),
                'customer_required_fields' => __( 'Por favor, completa los campos requeridos.', 'pos_base' ),
                'customer_saved_success' => __( 'Cliente guardado correctamente.', 'pos_base' ),
                'customer_saved_error' => __( 'Error al guardar el cliente.', 'pos_base' ),
                'loading_customer_data' => __( 'Cargando datos del cliente...', 'pos_base' ),
                'searching' => __( 'Buscando...', 'pos_base' ),
                'search_error' => __( 'Error al buscar.', 'pos_base' ),
                'no_customers_found' => __( 'No se encontraron clientes.', 'pos_base' ),
                'anonymous' => __( 'Invitado', 'pos_base' ),
                'select_avatar_title' => __( 'Seleccionar o Subir Avatar', 'pos_base' ),
                'use_this_avatar' => __( 'Usar esta imagen', 'pos_base' ),
                'loading_payment_methods' => __( 'Cargando métodos de pago...', 'pos_base' ),
                'select_payment_method' => __( '-- Selecciona Método de Pago --', 'pos_base' ),
                'no_payment_methods' => __( 'No hay métodos de pago activos.', 'pos_base' ),
                'error_loading_payment_methods' => __( 'Error al cargar métodos.', 'pos_base' ),
                'complete_sale' => __( 'Completar Venta', 'pos_base' ),
                'creating_order' => __( 'Creando pedido...', 'pos_base' ),
                'order_created_success' => __( '¡Pedido Creado!', 'pos_base' ),
                'order_created_error' => __( 'Error al crear el pedido.', 'pos_base' ),
                'coupon_code_required' => __( 'Por favor, ingresa un código de cupón.', 'pos_base' ),
                'apply' => __( 'Aplicar', 'pos_base' ),
                'validating' => __( 'Validando...', 'pos_base' ),
                'coupon_applied_success' => __( 'Cupón "%s" aplicado correctamente.', 'pos_base' ),
                'coupon_invalid' => __( 'El código de cupón no es válido.', 'pos_base' ),
                'remove_coupon' => __( 'Quitar cupón', 'pos_base' ),

                // DataTables (mantenemos 'pos_base')
                'dt_processing' => __( 'Procesando...', 'pos_base' ),
                'dt_search' => __( 'Buscar:', 'pos_base' ),
                'dt_lengthMenu' => __( 'Mostrar _MENU_ registros', 'pos_base' ),
                'dt_info' => __( 'Mostrando _START_ a _END_ de _TOTAL_ registros', 'pos_base' ),
                'dt_infoEmpty' => __( 'Mostrando 0 a 0 de 0 registros', 'pos_base' ),
                'dt_infoFiltered' => __( '(filtrado de _MAX_ registros totales)', 'pos_base' ),
                'dt_loadingRecords' => __( 'Cargando...', 'pos_base' ),
                'dt_zeroRecords' => __( 'No se encontraron registros coincidentes', 'pos_base' ),
                'dt_emptyTable' => __( 'No hay datos disponibles en la tabla', 'pos_base' ),
                'dt_paginate_first' => __( 'Primero', 'pos_base' ),
                'dt_paginate_previous' => __( 'Anterior', 'pos_base' ),
                'dt_paginate_next' => __( 'Siguiente', 'pos_base' ),
                'dt_paginate_last' => __( 'Último', 'pos_base' ),
                'dt_aria_sortAscending' => __( ': activar para ordenar la columna ascendente', 'pos_base' ),
                'dt_aria_sortDescending' => __( ': activar para ordenar la columna descendente', 'pos_base' )
            )
        )
    );

    // --- Hook para que los módulos encolen sus propios assets ---
    // Los módulos se engancharán a esta acción para añadir sus JS/CSS específicos
    do_action( 'pos_base_enqueue_module_scripts', $hook_suffix );
}

/**
 * Filtra la salida de get_avatar para usar la imagen personalizada si existe.
 * (Funcionalidad BASE del POS)
 */
function pos_base_get_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) { // <-- NOMBRE ACTUALIZADO
    $user_id = false;

    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if ( $user ) {
            $user_id = $user->ID;
        }
    } elseif ( $id_or_email instanceof WP_User ) {
         $user_id = $id_or_email->ID;
    } elseif ( $id_or_email instanceof WP_Comment ) {
         if ( ! empty( $id_or_email->user_id ) ) {
             $user_id = (int) $id_or_email->user_id;
         } elseif ( ! empty( $id_or_email->comment_author_email ) ) {
             $user = get_user_by( 'email', $id_or_email->comment_author_email );
             if ( $user ) {
                 $user_id = $user->ID;
             }
         }
    }


    if ( ! $user_id ) {
        return $avatar;
    }

    // El meta key 'pos_customer_avatar_id' es usado por el modal de cliente base
    $custom_avatar_id = get_user_meta( $user_id, 'pos_customer_avatar_id', true );

    if ( $custom_avatar_id ) {
        $image_url = wp_get_attachment_image_url( $custom_avatar_id, array( $size, $size ), false );

        if ( $image_url ) {
            $avatar = sprintf(
                '<img alt="%s" src="%s" class="avatar avatar-%d photo pos-custom-avatar" height="%d" width="%d" loading="lazy" decoding="async">',
                esc_attr( $alt ),
                esc_url( $image_url ),
                (int) $size,
                (int) $size,
                (int) $size
            );
        }
    }

    return $avatar;
}

/**
 * Añade un enlace de "Configuración" a la fila del plugin en la página de plugins.
 */
function pos_base_add_settings_link( $actions, $plugin_file ) {
    // Asegurarse de que solo se aplica a nuestro plugin base
    if ( plugin_basename( POS_BASE_PLUGIN_FILE ) === $plugin_file ) {
        // Construir la URL de la página de configuración
        // El slug 'pos_base-settings' se define en pos-page.php. Considera renombrarlo a 'pos-base-settings'.
        $settings_url = admin_url( 'admin.php?page=pos-base-settings' );

        // Crear el enlace HTML, usando el nuevo text domain 'pos-base'
        $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configuración', 'pos-base' ) . '</a>';

        // Añadir el nuevo enlace al principio del array
        array_unshift( $actions, $settings_link );
    }
    return $actions;
}
// Enganchar la función al filtro apropiado usando la constante renombrada
add_filter( 'plugin_action_links_' . plugin_basename( POS_BASE_PLUGIN_FILE ), 'pos_base_add_settings_link', 10, 2 );


?>
