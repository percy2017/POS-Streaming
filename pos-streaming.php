<?php
/**
 * Plugin Name:       POS BASE
 * Plugin URI:        https://percyalvarez.com/plugins-wordpress
 * Description:       plugin (point of sales) full responsive, para gestionar ventas, calendario y usuarios de wodpress y woocommerce, con modules extras.
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
define( 'POS_STREAMING_VERSION', '1.1.0' ); // Asegúrate que la versión sea la correcta
define( 'POS_STREAMING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POS_STREAMING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'POS_STREAMING_PLUGIN_FILE', __FILE__ );
define( 'POS_STREAMING_ASSETS_URL', POS_STREAMING_PLUGIN_URL . 'assets/' );

// Declarar compatibilidad con HPOS
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', POS_STREAMING_PLUGIN_FILE, true );
    }
} );

/**
 * Función principal de inicialización del plugin.
 * Se ejecuta después de que todos los plugins estén cargados.
 */
function pos_streaming_init() {

    // --- Comprobación de WooCommerce ---
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'pos_streaming_woocommerce_inactive_notice' );
        return;
    }

    // --- Inclusión de Archivos Principales (Sólo si WC está activo) ---
    require_once POS_STREAMING_PLUGIN_DIR . 'pos-page.php';
    require_once POS_STREAMING_PLUGIN_DIR . 'pos-api.php';
    require_once POS_STREAMING_PLUGIN_DIR . 'pos-metabox.php';

    // require_once POS_STREAMING_PLUGIN_DIR . 'pos-table.php'; 
    // require_once POS_STREAMING_PLUGIN_DIR . 'pos-cpts.php'; 
    // require_once POS_STREAMING_PLUGIN_DIR . 'pos-metaboxes-cpt.php';
    // require_once POS_STREAMING_PLUGIN_DIR . 'pos-suppliers.php';



    // --- Carga del Text Domain para Traducciones ---
    load_plugin_textdomain(
        'pos-base',
        false,
        dirname( plugin_basename( POS_STREAMING_PLUGIN_FILE ) ) . '/languages/'
    );

    // --- Encolar Scripts y Estilos ---
    add_action( 'admin_enqueue_scripts', 'pos_streaming_enqueue_assets' );

    // --- Filtro para Avatar Personalizado ---
    add_filter( 'get_avatar', 'pos_streaming_get_custom_avatar', 10, 5 );

    // --- Hook para Metabox de Pedido ---
    add_action( 'add_meta_boxes', 'pos_streaming_add_order_metabox', 10, 2 );

}
add_action( 'plugins_loaded', 'pos_streaming_init' );


/**
 * Muestra un aviso en el admin si WooCommerce no está activo.
 */
if ( ! function_exists( 'pos_streaming_woocommerce_inactive_notice' ) ) {
    function pos_streaming_woocommerce_inactive_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'El plugin "POS Streaming" requiere que WooCommerce esté activo y funcionando.', 'pos-streaming' ); ?></p>
        </div>
        <?php
    }
}


// --- Hooks de Activación / Desactivación ---
function pos_streaming_activate() {
    // Tu código de activación...
}
register_activation_hook( POS_STREAMING_PLUGIN_FILE, 'pos_streaming_activate' );

function pos_streaming_deactivate() {
    // Tu código de desactivación...
}
register_deactivation_hook( POS_STREAMING_PLUGIN_FILE, 'pos_streaming_deactivate' );


/**
 * Enqueue scripts and styles for the admin area.
 * (Usando la versión corregida de la respuesta anterior)
 */
function pos_streaming_enqueue_assets( $hook_suffix ) {

    $pos_page_hook = 'toplevel_page_pos-streaming';

    if ( $hook_suffix !== $pos_page_hook ) {
        return;
    }

    // Dependencias Nativas
    add_thickbox();
    wp_enqueue_media();

    // Estilos
    wp_enqueue_style( 'pos-streaming-datatables', POS_STREAMING_ASSETS_URL . 'vendor/datatables/datatables.min.css', array(), '1.13.8' );
    wp_enqueue_style( 'pos-streaming-sweetalert2', POS_STREAMING_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.min.css', array(), '11.10.6' );
    wp_enqueue_style( 'pos-streaming-intl-tel-input', POS_STREAMING_ASSETS_URL . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '18.2.1' );

    wp_enqueue_style(
        'pos-streaming-style',
        POS_STREAMING_ASSETS_URL . 'style.css',
        array('pos-streaming-datatables', 'pos-streaming-sweetalert2', 'pos-streaming-intl-tel-input', 'thickbox'),
        filemtime( POS_STREAMING_PLUGIN_DIR . 'assets/style.css' )
    );

    // Scripts
    wp_enqueue_script( 'pos-streaming-datatables', POS_STREAMING_ASSETS_URL . 'vendor/datatables/datatables.min.js', array('jquery'), '1.13.8', true );
    wp_enqueue_script( 'pos-streaming-sweetalert2', POS_STREAMING_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.all.min.js', array(), '11.10.6', true );
    wp_enqueue_script( 'pos-streaming-fullcalendar', POS_STREAMING_ASSETS_URL . 'vendor/fullcalendar/index.global.min.js', array(), '6.1.11', true );
    wp_enqueue_script( 'pos-streaming-intl-tel-input', POS_STREAMING_ASSETS_URL . 'vendor/intl-tel-input/js/intlTelInputWithUtils.min.js', array('jquery'), '18.2.1', true );

    wp_enqueue_script(
        'pos-streaming-app',
        POS_STREAMING_ASSETS_URL . 'app.js',
        array('jquery', 'wp-util', 'thickbox', 'pos-streaming-datatables', 'pos-streaming-sweetalert2', 'pos-streaming-fullcalendar', 'pos-streaming-intl-tel-input'),
        filemtime( POS_STREAMING_PLUGIN_DIR . 'assets/app.js' ),
        true
    );

    // Localize (Usando la versión corregida de la respuesta anterior)
    wp_localize_script(
        'pos-streaming-app',
        'posStreamingParams',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'rest_url' => esc_url_raw( rest_url( 'pos_streaming/v1/' ) ),
            'admin_url' => admin_url(),
            'intlTelInputUtilsScript' => esc_url( POS_STREAMING_ASSETS_URL . 'vendor/intl-tel-input/js/utils.js' ),
            'default_avatar_url' => get_avatar_url( 0, ['size' => 96, 'default' => 'mystery'] ),
            'i18n' => array(
                'loading' => __( 'Cargando...', 'pos-streaming' ),
                'saving' => __( 'Guardando...', 'pos-streaming' ),
                'processing' => __( 'Procesando...', 'pos-streaming' ),
                'error_general' => __( 'Ocurrió un error inesperado.', 'pos-streaming' ),
                'search_placeholder' => __( 'Buscar producto por nombre o SKU...', 'pos-streaming' ),
                'search_customer_placeholder' => __( 'Buscar cliente por nombre, email o teléfono...', 'pos-streaming' ),
                'no_products_found' => __( 'No se encontraron productos.', 'pos-streaming' ),
                'no_featured_products_found' => __( 'No hay productos destacados.', 'pos-streaming' ),
                'add_to_cart' => __( 'Añadir', 'pos-streaming' ),
                'select_variation' => __( 'Selecciona opción:', 'pos-streaming' ),
                'instock' => __( 'En stock', 'pos-streaming' ),
                'outofstock' => __( 'Agotado', 'pos-streaming' ),
                'cart_empty' => __( 'El carrito está vacío.', 'pos-streaming' ),
                'add_customer' => __( 'Añadir Nuevo Cliente', 'pos-streaming' ),
                'edit_customer' => __( 'Editar Cliente', 'pos-streaming' ),
                'customer_required_fields' => __( 'Por favor, completa los campos requeridos.', 'pos-streaming' ),
                'customer_saved_success' => __( 'Cliente guardado correctamente.', 'pos-streaming' ),
                'customer_saved_error' => __( 'Error al guardar el cliente.', 'pos-streaming' ),
                'loading_customer_data' => __( 'Cargando datos del cliente...', 'pos-streaming' ),
                'searching' => __( 'Buscando...', 'pos-streaming' ),
                'search_error' => __( 'Error al buscar.', 'pos-streaming' ),
                'no_customers_found' => __( 'No se encontraron clientes.', 'pos-streaming' ),
                'anonymous' => __( 'Invitado', 'pos-streaming' ),
                'select_avatar_title' => __( 'Seleccionar o Subir Avatar', 'pos-streaming' ),
                'use_this_avatar' => __( 'Usar esta imagen', 'pos-streaming' ),
                'loading_payment_methods' => __( 'Cargando métodos de pago...', 'pos-streaming' ),
                'select_payment_method' => __( '-- Selecciona Método de Pago --', 'pos-streaming' ),
                'no_payment_methods' => __( 'No hay métodos de pago activos.', 'pos-streaming' ),
                'error_loading_payment_methods' => __( 'Error al cargar métodos.', 'pos-streaming' ),
                'complete_sale' => __( 'Completar Venta', 'pos-streaming' ),
                'creating_order' => __( 'Creando pedido...', 'pos-streaming' ),
                'order_created_success' => __( '¡Pedido Creado!', 'pos-streaming' ),
                'order_created_error' => __( 'Error al crear el pedido.', 'pos-streaming' ),
                'coupon_code_required' => __( 'Por favor, ingresa un código de cupón.', 'pos-streaming' ),
                'apply' => __( 'Aplicar', 'pos-streaming' ),
                'validating' => __( 'Validando...', 'pos-streaming' ),
                'coupon_applied_success' => __( 'Cupón "%s" aplicado correctamente.', 'pos-streaming' ),
                'coupon_invalid' => __( 'El código de cupón no es válido.', 'pos-streaming' ),
                'remove_coupon' => __( 'Quitar cupón', 'pos-streaming' ),
                
                'dt_processing' => __( 'Procesando...', 'pos-streaming' ),
                'dt_search' => __( 'Buscar:', 'pos-streaming' ),
                'dt_lengthMenu' => __( 'Mostrar _MENU_ registros', 'pos-streaming' ),
                'dt_info' => __( 'Mostrando _START_ a _END_ de _TOTAL_ registros', 'pos-streaming' ),
                'dt_infoEmpty' => __( 'Mostrando 0 a 0 de 0 registros', 'pos-streaming' ),
                'dt_infoFiltered' => __( '(filtrado de _MAX_ registros totales)', 'pos-streaming' ),
                'dt_loadingRecords' => __( 'Cargando...', 'pos-streaming' ),
                'dt_zeroRecords' => __( 'No se encontraron registros coincidentes', 'pos-streaming' ),
                'dt_emptyTable' => __( 'No hay datos disponibles en la tabla', 'pos-streaming' ),
                'dt_paginate_first' => __( 'Primero', 'pos-streaming' ),
                'dt_paginate_previous' => __( 'Anterior', 'pos-streaming' ),
                'dt_paginate_next' => __( 'Siguiente', 'pos-streaming' ),
                'dt_paginate_last' => __( 'Último', 'pos-streaming' ),
                'dt_aria_sortAscending' => __( ': activar para ordenar la columna ascendente', 'pos-streaming' ),
                'dt_aria_sortDescending' => __( ': activar para ordenar la columna descendente', 'pos-streaming' )
            )
        )
    );
}

/**
 * Filtra la salida de get_avatar para usar la imagen personalizada si existe.
 * (Usando la versión corregida de la respuesta anterior)
 */
function pos_streaming_get_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
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
 *
 * @param array  $actions     Array existente de enlaces de acción.
 * @param string $plugin_file Path al archivo principal del plugin.
 * @return array Array modificado de enlaces de acción.
 */
function pos_streaming_add_settings_link( $actions, $plugin_file ) {
    // Asegurarse de que solo se aplica a nuestro plugin
    if ( plugin_basename( POS_STREAMING_PLUGIN_FILE ) === $plugin_file ) {
        // Construir la URL de la página de configuración (usaremos un slug 'pos-streaming-settings')
        $settings_url = admin_url( 'admin.php?page=pos-streaming-settings' );

        // Crear el enlace HTML
        $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configuración', 'pos-base' ) . '</a>';

        // Añadir el nuevo enlace al principio del array
        array_unshift( $actions, $settings_link );
    }
    return $actions;
}
// Enganchar la función al filtro apropiado
add_filter( 'plugin_action_links_' . plugin_basename( POS_STREAMING_PLUGIN_FILE ), 'pos_streaming_add_settings_link', 10, 2 );


?>
