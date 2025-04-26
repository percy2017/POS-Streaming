<?php
/**
 * Funciones para añadir y mostrar el Metabox de POS Streaming en los pedidos.
 * Compatible con HPOS. Usa hook add_meta_boxes.
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );

/**
 * Añade el metabox a la pantalla de edición de pedidos de WooCommerce.
 * Llamada desde el hook 'add_meta_boxes'.
 * Usa get_current_screen() y el ID de pantalla detectado.
 *
 * @param string $post_type El tipo de post actual (puede variar con HPOS).
 * @param WP_Post|object $post El objeto del post actual (o pantalla).
 */
function pos_streaming_add_order_metabox( $post_type, $post ) {
    $screen = get_current_screen(); // Obtener el objeto de la pantalla actual

    // Log para ver qué pantalla detecta WordPress
    error_log("POS Streaming DEBUG: pos_streaming_add_order_metabox() - Hook 'add_meta_boxes' disparado. Screen ID: " . ($screen ? $screen->id : 'N/A') . ", Post Type: " . $post_type);

    // Comprobar si estamos en la pantalla de edición de pedidos de WooCommerce
    $is_order_edit_screen = false;
    $target_screen_id = null; // Variable para guardar el ID de pantalla correcto

    if ( $screen ) {
        // Verificar si el ID de la pantalla es 'shop_order' (tradicional)
        // O si es 'woocommerce_page_wc-orders' Y la acción es 'edit' (HPOS)
        if ( $screen->id === 'shop_order' ) {
            $is_order_edit_screen = true;
            $target_screen_id = 'shop_order'; // Usar 'shop_order' para tradicional
             error_log("POS Streaming DEBUG: Detectada pantalla tradicional 'shop_order'.");
        } elseif ( $screen->id === 'woocommerce_page_wc-orders' && isset($_GET['action']) && $_GET['action'] === 'edit' ) {
            $is_order_edit_screen = true;
            $target_screen_id = $screen->id; // Usar el ID real de la pantalla HPOS
             error_log("POS Streaming DEBUG: Detectada pantalla HPOS 'woocommerce_page_wc-orders'.");
        }
    }

    // --- Añadir el metabox solo si estamos en la pantalla correcta ---
    if ( $is_order_edit_screen && $target_screen_id ) {

        error_log("POS Streaming DEBUG: pos_streaming_add_order_metabox() - Es pantalla de edición de pedido. Ejecutando add_meta_box() para pantalla ID: " . $target_screen_id);

        add_meta_box(
            'pos_streaming_order_details',                // ID único
            __( 'Detalles POS Streaming', 'pos-streaming' ), // Título
            'pos_streaming_order_metabox_callback',       // Callback
            $target_screen_id,                            // <-- USAR EL ID DE PANTALLA DETECTADO
            'side',                                       // Contexto
            'low'                                         // Prioridad
        );
        error_log('POS Streaming DEBUG: pos_streaming_add_order_metabox() - add_meta_box() ejecutado.');
    } else {
         error_log("POS Streaming DEBUG: pos_streaming_add_order_metabox() - No es pantalla de edición de pedido o falta ID de pantalla. No se añade metabox.");
    }
}


/**
 * Muestra el contenido del metabox de POS Streaming.
 * (Sin cambios en esta función, ya es compatible con WP_Post y WC_Order)
 *
 * @param WP_Post|WC_Order $post_or_order_object Objeto del post o del pedido.
 */
function pos_streaming_order_metabox_callback( $post_or_order_object ) {
     // --- LOG DE DEBUG ---
     error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Función llamada.');
     // --------------------

    // --- Obtener el ID del pedido correctamente ---
    $order_id = 0;
    $order = null;
    if ( $post_or_order_object instanceof WP_Post ) {
        $order_id = $post_or_order_object->ID;
        $order = wc_get_order($order_id);
        error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Recibido WP_Post, Order ID: ' . $order_id);
    } elseif ( class_exists('WC_Order') && $post_or_order_object instanceof WC_Order ) {
        $order_id = $post_or_order_object->get_id();
        $order = $post_or_order_object;
        error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Recibido WC_Order, Order ID: ' . $order_id);
    } else {
         // HPOS puede pasar otros objetos, intentar obtener ID si es posible
         if (is_object($post_or_order_object) && isset($post_or_order_object->id)) {
              $order_id = $post_or_order_object->id;
              $order = wc_get_order($order_id);
              error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Recibido objeto desconocido, Order ID (supuesto): ' . $order_id);
         } else {
             error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Error: Objeto recibido no reconocido.');
         }
    }


    if ( ! $order_id || !$order ) {
        error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Error: No se pudo obtener order_id u objeto WC_Order.');
        echo '<p>' . esc_html__( 'Error: No se pudo obtener la información del pedido.', 'pos-streaming' ) . '</p>';
        return;
    }
    // --------------------------------------------------------------------

    // ... (resto del código del callback sin cambios: nonce, get_meta, echo HTML) ...
     // Añadir un nonce field por seguridad
    wp_nonce_field( 'pos_streaming_save_order_meta', 'pos_streaming_order_nonce' );

    // Obtener los metadatos guardados usando el $order_id
    $sale_type = $order->get_meta( '_pos_sale_type', true );
    $sub_title = $order->get_meta( '_pos_subscription_title', true );
    $sub_expiry = $order->get_meta( '_pos_subscription_expiry_date', true );
    $sub_color = $order->get_meta( '_pos_subscription_color', true );
    error_log('POS Streaming DEBUG: pos_streaming_order_metabox_callback() - Metadatos leídos: ' . print_r(compact('sale_type', 'sub_title', 'sub_expiry', 'sub_color'), true));


    echo '<div class="pos-metabox-content">';

    // Mostrar Tipo de Venta
    if ( ! empty( $sale_type ) ) {
        $sale_type_label = '';
        switch ($sale_type) {
            case 'direct': $sale_type_label = __('Directo', 'pos-streaming'); break;
            case 'subscription': $sale_type_label = __('Suscripción', 'pos-streaming'); break;
            case 'credit': $sale_type_label = __('Crédito', 'pos-streaming'); break;
            default: $sale_type_label = esc_html($sale_type);
        }
        echo '<p><strong>' . esc_html__( 'Tipo Venta (POS):', 'pos-streaming' ) . '</strong> ' . esc_html( $sale_type_label ) . '</p>';
    } else {
         echo '<p>' . esc_html__( 'Tipo de venta POS no especificado.', 'pos-streaming' ) . '</p>';
    }

    // Mostrar Detalles de Suscripción (si es tipo suscripción)
    if ( $sale_type === 'subscription' ) {
        echo '<hr style="margin: 10px 0;">';
        echo '<h4>' . esc_html__( 'Detalles Suscripción:', 'pos-streaming' ) . '</h4>';

        if ( ! empty( $sub_title ) ) {
            echo '<p><strong>' . esc_html__( 'Título:', 'pos-streaming' ) . '</strong> ' . esc_html( $sub_title ) . '</p>';
        }
        if ( ! empty( $sub_expiry ) ) {
            try {
                 $date_obj = new DateTime($sub_expiry);
                 $formatted_date = $date_obj->format(get_option('date_format'));
            } catch (Exception $e) {
                 $formatted_date = esc_html($sub_expiry);
            }
            echo '<p><strong>' . esc_html__( 'Vencimiento:', 'pos-streaming' ) . '</strong> ' . $formatted_date . '</p>';
        }
        if ( ! empty( $sub_color ) ) {
            echo '<p><strong>' . esc_html__( 'Color:', 'pos-streaming' ) . '</strong> ';
            echo '<span style="display:inline-block; width: 15px; height: 15px; background-color:' . esc_attr( $sub_color ) . '; border: 1px solid #ccc; vertical-align: middle; margin-right: 5px;"></span>';
            echo '<code>' . esc_html( $sub_color ) . '</code></p>';
        }

        if ( empty($sub_title) && empty($sub_expiry) && empty($sub_color) ) {
             echo '<p><em>' . esc_html__( 'No se encontraron detalles de suscripción.', 'pos-streaming' ) . '</em></p>';
        }
    }

    echo '</div>'; // Fin .pos-metabox-content
}

