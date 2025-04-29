<?php
/**
 * Funcionalidad del Tour Guiado (WordPress Pointers) para POS Base.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Define los pasos (pointers) para el tour del POS.
 *
 * @return array Array de pointers.
 */
function pos_base_define_tour_pointers() {
    $pointers = array(
        'pos_step_1' => array(
            'target'       => '#pos-product-search', // Selector CSS del input de búsqueda de productos
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Buscar Productos', 'pos-base' ), // Título
                    esc_html__( 'Empieza escribiendo aquí el nombre o SKU del producto que quieres añadir al carrito.', 'pos-base' ) // Contenido
                ),
                'position' => array(
                    'edge'  => 'top',   // Borde del target donde se ancla el pointer
                    'align' => 'left', // Alineación del pointer respecto al borde
                ),
            ),
        ),
        'pos_step_2' => array(
            'target'       => '#pos-product-list', // Selector CSS del contenedor de la lista de productos
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Lista de Productos', 'pos-base' ),
                    esc_html__( 'Aquí aparecerán los productos destacados o los resultados de tu búsqueda. Haz clic en "Añadir" o selecciona una opción si es variable.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'right',
                    'align' => 'left',
                ),
            ),
        ),
        'pos_step_3' => array(
            'target'       => '#pos-cart-items', // Selector CSS del contenedor de items del carrito
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Carrito de Compras', 'pos-base' ),
                    esc_html__( 'Los productos añadidos se mostrarán aquí. Puedes ajustar la cantidad, el precio unitario o eliminar items.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
        ),
        'pos_step_4' => array(
            // 'target'       => '#pos-customer-section', // <-- INCORRECTO
            'target'       => '#pos-customer-area',    // <-- CORREGIDO
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Gestión de Clientes', 'pos-base' ),
                    esc_html__( 'Busca un cliente existente o crea uno nuevo. Es necesario seleccionar un cliente (o usar "Invitado") para completar la venta.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
        ),
         'pos_step_5' => array(
            // 'target'       => '#pos-checkout-section', // <-- INCORRECTO
            'target'       => '#pos-checkout-area',    // <-- CORREGIDO
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Finalizar Venta', 'pos-base' ),
                    esc_html__( 'Selecciona el tipo de venta (Directa, Crédito, Suscripción), método de pago, aplica cupones y haz clic en "Completar Venta".', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'left', // Quizás 'top' sea mejor aquí también
                    'align' => 'right',
                ),
            ),
        ),
        // --- NUEVOS PASOS --- (Añadidos previamente como ejemplo)

        'pos_step_6' => array(
            'target'       => '#pos-sale-type', // Selector del <select> de tipo de venta
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Tipo de Venta', 'pos-base' ),
                    esc_html__( 'Elige si es una venta directa, a crédito o una suscripción. Si eliges suscripción, aparecerán campos adicionales.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
        ),

        'pos_step_7' => array(
            'target'       => '#pos-payment-method', // Selector del <select> de método de pago
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Método de Pago', 'pos-base' ),
                    esc_html__( 'Selecciona cómo pagó el cliente (Efectivo, Tarjeta, etc.).', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
        ),

        'pos_step_8' => array(
            'target'       => '#pos-complete-sale-button', // Selector del botón final
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( '¡Completar!', 'pos-base' ),
                    esc_html__( 'Una vez que todo esté listo (productos, cliente, pago), haz clic aquí para finalizar y crear el pedido.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top', // Podría ser 'bottom' también
                    'align' => 'right',
                ),
            ),
        ),
        // Puedes añadir más pasos aquí para las pestañas, cupones, etc.
        /*
        'pos_step_9' => array(
            'target'       => '.nav-tab-wrapper a[data-tab="calendar"]', // Pestaña Calendario
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__( 'Pestaña Calendario', 'pos-base' ),
                    esc_html__( 'Haz clic aquí para ver los próximos vencimientos de suscripciones.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'bottom',
                    'align' => 'center',
                ),
            ),
        ),
        */

    ); // Fin del array $pointers

    // --- El código que añade los botones automáticamente NO necesita cambios ---
    foreach ( $pointers as $key => &$pointer ) {
        $pointer['options']['buttons'] = array(
             array(
                'name'  => 'close',
                'label' => esc_html__( 'Cerrar', 'pos-base' ),
                'class' => 'button-secondary pos-tour-close-btn',
            ),
            array(
                'name'  => 'next',
                'label' => esc_html__( 'Siguiente', 'pos-base' ),
                'class' => 'button-primary pos-tour-next-btn',
            ),
        );
        $pointer['options']['content'] = wp_kses_post($pointer['options']['content']);
    }
    unset($pointer);

    return $pointers;
}


/**
 * Enqueue scripts y estilos para el tour del POS.
 *
 * @param string $hook_suffix El sufijo del hook de la página actual.
 */
function pos_base_enqueue_tour_scripts( $hook_suffix ) {
    // Intentar obtener el hook suffix guardado al crear la página
    // Asumiendo que se guarda en pos-page.php con add_menu_page o add_submenu_page
    // y se almacena en una variable global o una opción.
    // Si usas una variable global, asegúrate de que esté definida ANTES de que este hook se ejecute.
    // Ejemplo con variable global (asegúrate que se define en pos-page.php):
    global $pos_base_pos_page_hook_suffix;
    error_log("Tour Debug: ".$hook_suffix);
    // Alternativa: Si no usas global, define el hook esperado directamente
    // $expected_hook_suffix = 'toplevel_page_pos-base'; // O el que sea correcto

    // --- DEBUG INICIO ---
    error_log("Tour Debug: Hook actual: $hook_suffix");
    // Usar la variable global si existe, sino el valor hardcodeado como fallback
    $expected_hook = isset($pos_base_pos_page_hook_suffix) ? $pos_base_pos_page_hook_suffix : 'toplevel_page_pos-base';
    error_log("Tour Debug: Hook esperado: " . $expected_hook);
    // --- DEBUG FIN ---

    // 1. Verificar si estamos en la página correcta del POS
    // Usar la variable global si existe, sino el valor hardcodeado
    if ( $hook_suffix !== $expected_hook ) {
        error_log("Tour Debug: SALIENDO - Hook suffix no coincide."); // <-- DEBUG
        return;
    }

    // 2. Verificar si el usuario ya ha descartado el tour
    $user_id = get_current_user_id();
    $tour_dismissed = get_user_meta( $user_id, 'pos_base_tour_dismissed', true );

    // --- DEBUG INICIO ---
    error_log("Tour Debug: User ID: $user_id, Tour Dismissed Meta: " . var_export($tour_dismissed, true));
    // --- DEBUG FIN ---

    if ( $tour_dismissed ) {
        error_log("Tour Debug: SALIENDO - Tour ya descartado."); // <-- DEBUG
        return; // No cargar el tour si ya fue descartado
    }

    // 3. Obtener los pasos del tour
    $pointers = pos_base_define_tour_pointers();
    if ( empty( $pointers ) ) {
        error_log("Tour Debug: SALIENDO - No hay pointers definidos."); // <-- DEBUG
        return; // No hacer nada si no hay pasos
    }

    // 4. Enqueue scripts y estilos necesarios
    wp_enqueue_style( 'wp-pointer' );
    wp_enqueue_script( 'wp-pointer' );

    // Enqueue nuestro script personalizado del tour
    $tour_js_path = POS_BASE_PLUGIN_DIR . 'assets/tour.js';
    $tour_js_url = POS_BASE_PLUGIN_URL . 'assets/tour.js';
    if (file_exists($tour_js_path)) {
        wp_enqueue_script(
            'pos-base-tour-script', // Handle único
            $tour_js_url, // Ruta a tu tour.js
            array( 'jquery', 'wp-pointer' ), // Dependencias
            filemtime($tour_js_path), // Versionado basado en modificación del archivo
            true // Cargar en el footer
        );
    } else {
         error_log("Tour Debug: ERROR - El archivo tour.js no se encontró en: " . $tour_js_path);
         return; // Salir si el script no existe
    }


    // 5. Pasar datos a JavaScript
    $data_for_js = array(
        'pointers'  => $pointers,
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'pos_tour_nonce' ), // Nonce de seguridad para AJAX
        'i18n'      => array( // Textos para JS
            'finish' => esc_html__( 'Finalizar', 'pos-base' ),
            // Puedes añadir más textos si los necesitas en tour.js
        ),
    );

    wp_localize_script( 'pos-base-tour-script', 'posBaseTourData', $data_for_js );
    error_log("Tour Debug: PASÓ TODAS LAS COMPROBACIONES - Scripts encolados y datos localizados para $hook_suffix."); // <-- DEBUG

}
// Añadir prioridad alta (ej: 99) por si otro plugin interfiere con la variable global o el hook
add_action( 'admin_enqueue_scripts', 'pos_base_enqueue_tour_scripts', 99 );

/**
 * Manejador AJAX para descartar el tour.
 */
function pos_base_ajax_dismiss_tour() {
    error_log("Tour Debug: Entrando en pos_base_ajax_dismiss_tour."); // <-- DEBUG

    // Verificar nonce
    if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ), 'pos_tour_nonce' ) ) {
        error_log("Tour Debug: AJAX Error - Nonce inválido o faltante."); // <-- DEBUG
        wp_send_json_error( array( 'message' => esc_html__( 'Error de seguridad (Nonce).', 'pos-base' ) ), 403 ); // 403 Forbidden
    }

    // Verificar permisos (asegúrate de que el usuario puede usar el POS)
    if ( ! current_user_can( 'manage_woocommerce' ) ) { // O la capacidad que uses
        error_log("Tour Debug: AJAX Error - Permiso denegado (manage_woocommerce)."); // <-- DEBUG
        wp_send_json_error( array( 'message' => esc_html__( 'Permiso denegado.', 'pos-base' ) ), 403 );
    }

    // Marcar el tour como descartado para este usuario
    $user_id = get_current_user_id();
    if ( update_user_meta( $user_id, 'pos_base_tour_dismissed', true ) ) {
        error_log("Tour Debug: AJAX Success - Tour descartado para usuario $user_id."); // <-- DEBUG
        wp_send_json_success( array( 'message' => esc_html__( 'Tour descartado.', 'pos-base' ) ) );
    } else {
        error_log("Tour Debug: AJAX Error - No se pudo guardar user_meta para usuario $user_id."); // <-- DEBUG
        wp_send_json_error( array( 'message' => esc_html__( 'No se pudo guardar la preferencia.', 'pos-base' ) ), 500 ); // Internal Server Error
    }
}
add_action( 'wp_ajax_pos_base_dismiss_tour', 'pos_base_ajax_dismiss_tour' );

?>
