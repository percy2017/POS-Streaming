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
function pos_base_add_order_metabox( $post_type, $post ) {
    $screen = get_current_screen(); // Obtener el objeto de la pantalla actual

    // Log para ver qué pantalla detecta WordPress
    error_log("POS Streaming DEBUG: pos_base_add_order_metabox() - Hook 'add_meta_boxes' disparado. Screen ID: " . ($screen ? $screen->id : 'N/A') . ", Post Type: " . $post_type);

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

        error_log("POS Streaming DEBUG: pos_base_add_order_metabox() - Es pantalla de edición de pedido. Ejecutando add_meta_box() para pantalla ID: " . $target_screen_id);

        add_meta_box(
            'pos_base_order_details',                // ID único
            __( 'Detalles POS', 'pos-base' ), // Título
            'pos_base_order_metabox_callback',       // Callback
            $target_screen_id,                            // <-- USAR EL ID DE PANTALLA DETECTADO
            'side',                                       // Contexto
            'low'                                         // Prioridad
        );
        error_log('POS Streaming DEBUG: pos_base_add_order_metabox() - add_meta_box() ejecutado.');
    } else {
         error_log("POS Streaming DEBUG: pos_base_add_order_metabox() - No es pantalla de edición de pedido o falta ID de pantalla. No se añade metabox.");
    }
}


/**
 * Muestra el contenido del metabox de POS Base en la pantalla de edición de pedidos.
 * Muestra el tipo de venta, detalles de suscripción base y, si aplica Y el módulo está activo,
 * el perfil y la cuenta de streaming asignados.
 *
 * @param WP_Post|WC_Order|object $post_or_order_object Objeto del post o del pedido.
 */
function pos_base_order_metabox_callback( $post_or_order_object ) {
    // --- LOG DE DEBUG ---
    // error_log('POS Streaming DEBUG: pos_base_order_metabox_callback() - Función llamada.');
    // --------------------

    // --- Obtener el ID del pedido correctamente ---
    $order_id = 0;
    $order = null;
    if ( $post_or_order_object instanceof WP_Post ) {
        $order_id = $post_or_order_object->ID;
        $order = wc_get_order($order_id);
    } elseif ( class_exists('WC_Order') && $post_or_order_object instanceof WC_Order ) {
        $order_id = $post_or_order_object->get_id();
        $order = $post_or_order_object;
    } elseif (is_object($post_or_order_object) && isset($post_or_order_object->id)) {
         $order_id = $post_or_order_object->id;
         $order = wc_get_order($order_id);
    }

    if ( ! $order_id || !$order ) {
        error_log('POS Base ERROR: pos_base_order_metabox_callback() - No se pudo obtener order_id u objeto WC_Order.');
        echo '<p>' . esc_html__( 'Error: No se pudo obtener la información del pedido.', 'pos-base' ) . '</p>';
        return;
    }
    // --------------------------------------------------------------------

    // Añadir un nonce field (aunque este metabox es solo de visualización por ahora)
    wp_nonce_field( 'pos_base_save_order_meta', 'pos_base_order_nonce' );

    // --- Obtener Metadatos Base del POS ---
    $sale_type = $order->get_meta( '_pos_sale_type', true );
    $sub_title = $order->get_meta( '_pos_subscription_title', true );
    $sub_expiry = $order->get_meta( '_pos_subscription_expiry_date', true );
    $sub_color = $order->get_meta( '_pos_subscription_color', true );
    // --- Obtener Metadatos del Módulo Streaming ---
    $assigned_profile_id = $order->get_meta( '_pos_assigned_profile_id', true );

    // --- Mostrar Contenido del Metabox ---
    echo '<div class="pos-metabox-content">';

    // 1. Mostrar Tipo de Venta
    if ( ! empty( $sale_type ) ) {
        $sale_type_label = '';
        switch ($sale_type) {
            case 'direct': $sale_type_label = __('Directo', 'pos-base'); break;
            case 'subscription': $sale_type_label = __('Suscripción', 'pos-base'); break;
            case 'credit': $sale_type_label = __('Crédito', 'pos-base'); break;
            default: $sale_type_label = esc_html($sale_type);
        }
        echo '<p><strong>' . esc_html__( 'Tipo Venta (POS):', 'pos-base' ) . '</strong> ' . esc_html( $sale_type_label ) . '</p>';
    } else {
         echo '<p>' . esc_html__( 'Tipo de venta POS no especificado.', 'pos-base' ) . '</p>';
    }

    // 2. Mostrar Detalles de Suscripción (si es tipo suscripción)
    if ( $sale_type === 'subscription' ) {
        echo '<hr style="margin: 10px 0;">';
        echo '<h4>' . esc_html__( 'Detalles Suscripción Base:', 'pos-base' ) . '</h4>';

        // Mostrar Título, Vencimiento, Color (base)
        if ( ! empty( $sub_title ) ) {
            echo '<p><strong>' . esc_html__( 'Título (Calendario):', 'pos-base' ) . '</strong> ' . esc_html( $sub_title ) . '</p>';
        }
        if ( ! empty( $sub_expiry ) ) {
            $formatted_date = $sub_expiry; try { $date_obj = new DateTime($sub_expiry); $formatted_date = $date_obj->format(get_option('date_format')); } catch (Exception $e) {}
            echo '<p><strong>' . esc_html__( 'Vencimiento:', 'pos-base' ) . '</strong> ' . $formatted_date . '</p>';
        }
        if ( ! empty( $sub_color ) ) {
            echo '<p><strong>' . esc_html__( 'Color:', 'pos-base' ) . '</strong> ';
            echo '<span style="display:inline-block; width: 15px; height: 15px; background-color:' . esc_attr( $sub_color ) . '; border: 1px solid #ccc; vertical-align: middle; margin-right: 5px;"></span>';
            echo '<code>' . esc_html( $sub_color ) . '</code></p>';
        }
        if ( empty($sub_title) && empty($sub_expiry) && empty($sub_color) ) {
             echo '<p><em>' . esc_html__( 'No se encontraron detalles de suscripción base.', 'pos-base' ) . '</em></p>';
        }

        // --- INICIO: VERIFICACIÓN DEL MÓDULO STREAMING ---
        $active_modules = get_option( 'pos_base_active_modules', [] );
        $is_streaming_active = ( is_array( $active_modules ) && in_array( 'streaming', $active_modules, true ) );

        // SOLO mostrar la sección de streaming asignado SI el módulo está activo
        if ( $is_streaming_active ) {
        // --- FIN: VERIFICACIÓN DEL MÓDULO STREAMING ---

            // --- INICIO: Mostrar Detalles del Perfil y Cuenta Asignados ---
            echo '<hr style="margin: 10px 0;">';
            echo '<h4>' . esc_html__( 'Detalles Streaming Asignado:', 'pos-streaming' ) . '</h4>'; // Usar text domain del módulo

            // Añadir logs de depuración (si aún los tienes, está bien)
            error_log('[Metabox DEBUG] Order ID: ' . $order_id . ' - Intentando leer _pos_assigned_profile_id.');
            error_log('[Metabox DEBUG] Valor de $assigned_profile_id recuperado: ' . print_r($assigned_profile_id, true));
            if ($assigned_profile_id && $assigned_profile_id > 0) {
                $post_type_check = get_post_type($assigned_profile_id);
                error_log('[Metabox DEBUG] Resultado de get_post_type(' . $assigned_profile_id . '): ' . print_r($post_type_check, true));
            } else {
                error_log('[Metabox DEBUG] $assigned_profile_id está vacío o no es > 0.');
            }

            // La condición IF original para mostrar detalles o el mensaje "No se asignó..."
            if ( $assigned_profile_id && $assigned_profile_id > 0 && get_post_type($assigned_profile_id) === 'pos_profile' ) {
                // Obtener detalles del perfil
                $profile_title = get_the_title( $assigned_profile_id );
                $profile_edit_link = get_edit_post_link( $assigned_profile_id );
                $parent_account_id = get_post_meta( $assigned_profile_id, '_pos_profile_parent_account_id', true );

                // Mostrar Perfil
                echo '<p><strong>' . esc_html__( 'Perfil Asignado:', 'pos-streaming' ) . '</strong> ';
                if ( $profile_edit_link ) {
                    echo '<a href="' . esc_url( $profile_edit_link ) . '">' . esc_html( $profile_title ?: sprintf( __( 'Perfil ID %d', 'pos-streaming' ), $assigned_profile_id ) ) . '</a>';
                } else {
                    echo esc_html( $profile_title ?: sprintf( __( 'Perfil ID %d', 'pos-streaming' ), $assigned_profile_id ) );
                }
                echo '</p>';

                // Mostrar Cuenta Padre (si existe)
                if ( $parent_account_id && $parent_account_id > 0 && get_post_type($parent_account_id) === 'pos_account' ) {
                    $account_title = get_the_title( $parent_account_id );
                    $account_edit_link = get_edit_post_link( $parent_account_id );
                    $provider_key = get_post_meta( $parent_account_id, '_pos_account_provider', true );
                    $provider_label = '';

                    // Obtener etiqueta legible del proveedor (repetimos la lista o usamos helper)
                    $supported_providers = array(
                        'netflix'     => 'Netflix', 'disney_plus' => 'Disney+', 'hbo_max' => 'HBO Max (Max)',
                        'prime_video' => 'Amazon Prime Video', 'spotify' => 'Spotify', 'youtube_premium' => 'YouTube Premium',
                        'other'       => __( 'Otro', 'pos-streaming' ),
                    );
                    if ( isset($supported_providers[$provider_key]) ) {
                        $provider_label = $supported_providers[$provider_key];
                    } elseif ($provider_key) {
                        $provider_label = ucfirst(str_replace('_', ' ', $provider_key)); // Fallback
                    }

                    echo '<p><strong>' . esc_html__( 'Cuenta Padre:', 'pos-streaming' ) . '</strong> ';
                    if ( $account_edit_link ) {
                        echo '<a href="' . esc_url( $account_edit_link ) . '">' . esc_html( $account_title ?: sprintf( __( 'Cuenta ID %d', 'pos-streaming' ), $parent_account_id ) ) . '</a>';
                    } else {
                        echo esc_html( $account_title ?: sprintf( __( 'Cuenta ID %d', 'pos-streaming' ), $parent_account_id ) );
                    }
                    if ( $provider_label ) {
                         echo ' <span style="color: #777;">(' . esc_html($provider_label) . ')</span>';
                    }
                    echo '</p>';

                } else {
                    echo '<p><em>' . esc_html__( 'Información de la cuenta padre no encontrada en el perfil.', 'pos-streaming' ) . '</em></p>';
                }

            } else {
                // Si no hay _pos_assigned_profile_id o no es válido
                echo '<p><em>' . esc_html__( 'No se asignó un perfil de streaming específico a esta venta.', 'pos-streaming' ) . '</em></p>';
            }
            // --- FIN: Mostrar Detalles del Perfil y Cuenta Asignados ---

        } // <-- Llave de cierre para el if ( $is_streaming_active )

    } // Fin if ($sale_type === 'subscription')

    echo '</div>'; // Fin .pos-metabox-content
}


