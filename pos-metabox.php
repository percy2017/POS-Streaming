<?php
/**
 * Funciones para añadir, mostrar y guardar el Metabox de POS Base en los pedidos.
 * Compatible con HPOS.
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );

/**
 * Añade el metabox a la pantalla de edición de pedidos de WooCommerce.
 * (Función sin cambios)
 */
function pos_base_add_order_metabox( $post_type, $post ) {
    $screen = get_current_screen();

    // Log para ver qué pantalla detecta WordPress
    // error_log("POS Streaming DEBUG: pos_base_add_order_metabox() - Hook 'add_meta_boxes' disparado. Screen ID: " . ($screen ? $screen->id : 'N/A') . ", Post Type: " . $post_type);

    $is_order_edit_screen = false;
    $target_screen_id = null;

    if ( $screen ) {
        if ( $screen->id === 'shop_order' ) {
            $is_order_edit_screen = true;
            $target_screen_id = 'shop_order';
            // error_log("POS Streaming DEBUG: Detectada pantalla tradicional 'shop_order'.");
        } elseif ( $screen->id === 'woocommerce_page_wc-orders' && isset($_GET['action']) && $_GET['action'] === 'edit' ) {
            $is_order_edit_screen = true;
            $target_screen_id = $screen->id;
            // error_log("POS Streaming DEBUG: Detectada pantalla HPOS 'woocommerce_page_wc-orders'.");
        }
        // Compatibilidad adicional para HPOS en algunas configuraciones
        elseif ( $screen->id === 'shop_order' && $post instanceof \Automattic\WooCommerce\Admin\Overrides\Order ) {
             $is_order_edit_screen = true;
             $target_screen_id = $screen->id; // O podría ser $post->get_screen_id(); si existe
             // error_log("POS Streaming DEBUG: Detectada pantalla HPOS vía objeto Order.");
        }
    }

    if ( $is_order_edit_screen && $target_screen_id ) {
        // error_log("POS Streaming DEBUG: pos_base_add_order_metabox() - Es pantalla de edición de pedido. Ejecutando add_meta_box() para pantalla ID: " . $target_screen_id);
        add_meta_box(
            'pos_base_order_details',
            __( 'Detalles POS', 'pos-base' ),
            'pos_base_order_metabox_callback', // <-- MODIFICADA para mostrar campos
            $target_screen_id,
            'side',
            'low'
        );
        // error_log('POS Streaming DEBUG: pos_base_add_order_metabox() - add_meta_box() ejecutado.');
    } else {
         // error_log("POS Streaming DEBUG: pos_base_add_order_metabox() - No es pantalla de edición de pedido o falta ID de pantalla. No se añade metabox.");
    }
}
add_action( 'add_meta_boxes', 'pos_base_add_order_metabox', 10, 2 );


/**
 * Muestra el contenido EDITABLE del metabox de POS Base.
 *
 * @param WP_Post|WC_Order|object $post_or_order_object Objeto del post o del pedido.
 */
function pos_base_order_metabox_callback( $post_or_order_object ) {
    $order = null;
    // Intenta obtener el objeto WC_Order de forma robusta
    if ( $post_or_order_object instanceof WP_Post ) {
        $order = wc_get_order($post_or_order_object->ID);
    } elseif ( class_exists('WC_Order') && $post_or_order_object instanceof WC_Order ) {
        $order = $post_or_order_object;
    } elseif (is_object($post_or_order_object) && isset($post_or_order_object->id)) {
         // Compatibilidad HPOS donde el objeto puede no ser WC_Order directamente
         $order = wc_get_order($post_or_order_object->id);
    }

    // Si no se pudo obtener el pedido, mostrar error y salir
    if ( ! $order ) {
        echo '<p>' . esc_html__( 'Error: No se pudo obtener la información del pedido.', 'pos-base' ) . '</p>';
        return;
    }
    $order_id = $order->get_id();

    // Nonce para seguridad al guardar los datos del metabox
    wp_nonce_field( 'pos_base_save_order_meta_action', 'pos_base_order_nonce' );

    // --- Obtener Metadatos Actuales del Pedido ---
    $sale_type = $order->get_meta( '_pos_sale_type', true );
    $sub_title = $order->get_meta( '_pos_subscription_title', true );
    $sub_expiry = $order->get_meta( '_pos_subscription_expiry_date', true );
    $sub_color = $order->get_meta( '_pos_subscription_color', true );
    $assigned_profile_id = $order->get_meta( '_pos_assigned_profile_id', true );

    // --- Mostrar Campos Editables del Metabox ---
    echo '<div class="pos-metabox-content form-wrap">'; // Clase 'form-wrap' para estilos de WP

    // 1. Campo Tipo de Venta (Select)
    ?>
    <p class="form-field form-field-wide">
        <label for="pos_sale_type"><?php esc_html_e( 'Tipo Venta (POS):', 'pos-base' ); ?></label>
        <select id="pos_sale_type" name="pos_sale_type" class="wc-enhanced-select" style="width:100%;">
            <option value="direct" <?php selected( $sale_type, 'direct' ); ?>><?php esc_html_e( 'Directo', 'pos-base' ); ?></option>
            <option value="subscription" <?php selected( $sale_type, 'subscription' ); ?>><?php esc_html_e( 'Suscripción', 'pos-base' ); ?></option>
            <option value="credit" <?php selected( $sale_type, 'credit' ); ?>><?php esc_html_e( 'Crédito', 'pos-base' ); ?></option>
            <option value="" <?php selected( $sale_type, '' ); ?>><?php esc_html_e( '(Ninguno)', 'pos-base' ); ?></option>
        </select>
    </p>
    <?php

    // 2. Campos de Suscripción Base (siempre visibles por simplicidad)
    echo '<hr style="margin: 10px 0;">';
    echo '<h4>' . esc_html__( 'Detalles Suscripción Base:', 'pos-base' ) . '</h4>';
    ?>
    <p class="form-field form-field-wide">
        <label for="pos_subscription_title"><?php esc_html_e( 'Título (Calendario):', 'pos-base' ); ?></label>
        <input type="text" id="pos_subscription_title" name="pos_subscription_title" value="<?php echo esc_attr( $sub_title ); ?>" style="width:100%;">
    </p>
    <p class="form-field form-field-wide">
        <label for="pos_subscription_expiry_date"><?php esc_html_e( 'Vencimiento:', 'pos-base' ); ?></label>
        <input type="date" id="pos_subscription_expiry_date" name="pos_subscription_expiry_date" value="<?php echo esc_attr( $sub_expiry ); ?>" style="width:100%;">
    </p>
    <p class="form-field form-field-wide">
        <label for="pos_subscription_color"><?php esc_html_e( 'Color:', 'pos-base' ); ?></label>
        <input type="color" id="pos_subscription_color" name="pos_subscription_color" value="<?php echo esc_attr( $sub_color ?: '#3a87ad' ); ?>" style="width: 50px; height: 30px; padding: 2px;">
        <input type="text" value="<?php echo esc_attr( $sub_color ?: '#3a87ad' ); ?>" readonly style="width: calc(100% - 60px); vertical-align: top; margin-left: 5px;" onclick="this.select();">
    </p>
    <?php

    // --- INICIO: Mostrar Estado de Recordatorio Enviado (Informativo) ---
    $active_modules = get_option( 'pos_base_active_modules', [] );
    $is_evolution_active = ( is_array( $active_modules ) && in_array( 'evolution-api', $active_modules, true ) );

    // Mostrar solo si el módulo Evo está activo, es suscripción y hay fecha de vencimiento válida
    if ( $is_evolution_active && $sale_type === 'subscription' && ! empty( $sub_expiry ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $sub_expiry ) ) {
        $reminder_sent_meta_key = '_pos_evo_reminder_sent_' . $sub_expiry;
        $reminder_sent = $order->get_meta( $reminder_sent_meta_key, true ); // Obtiene '1' o true si existe, '' si no

        $status_text = $reminder_sent ? __('Sí', 'pos-base') : __('No', 'pos-base');
        $formatted_expiry = ''; // Formatear fecha para mostrar
        try {
            $date_obj = new DateTime($sub_expiry);
            $formatted_expiry = $date_obj->format(get_option('date_format')); // Usa formato de fecha de WP
        } catch (Exception $e) {
            $formatted_expiry = $sub_expiry; // Fallback a la fecha original si hay error
        }
        ?>
        <p class="form-field form-field-wide pos-reminder-status">
            <label><?php esc_html_e( 'Recordatorio Enviado:', 'pos-base' ); ?></label>
            <span style="font-weight: bold;"><?php echo esc_html( $status_text ); ?></span>
            <?php if ($reminder_sent): ?>
                <span class="description"><?php printf( esc_html__( '(en fecha %s)', 'pos-base' ), esc_html( $formatted_expiry ) ); ?></span>
            <?php endif; ?>
            <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Indica si el recordatorio de vencimiento por WhatsApp fue enviado en la fecha de vencimiento indicada.', 'pos-base' ); ?>"></span>
        </p>
        <?php
    }

    // 3. Campo Perfil Streaming Asignado (SOLO si el módulo 'streaming' está activo)
    $active_modules = get_option( 'pos_base_active_modules', [] );
    $is_streaming_active = ( is_array( $active_modules ) && in_array( 'streaming', $active_modules, true ) );

    if ( $is_streaming_active ) {
        echo '<hr style="margin: 10px 0;">';
        echo '<h4>' . esc_html__( 'Detalles Streaming Asignado:', 'pos-streaming' ) . '</h4>';

        // --- Lógica para obtener perfiles disponibles y el asignado ---
        // Argumentos base para buscar perfiles 'available'
        $available_profiles_query_args = array(
            'post_type'      => 'pos_profile',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_pos_profile_status',
                    'value'   => 'available',
                    'compare' => '=',
                ),
            ),
        );

        // Obtener los perfiles disponibles
        $available_profiles = get_posts( $available_profiles_query_args );

        // Obtener el post del perfil actualmente asignado (si existe y es válido)
        $assigned_profile_post = null;
        $assigned_profile_id_int = absint($assigned_profile_id); // Asegurar que sea entero
        if ( $assigned_profile_id_int > 0 && get_post_type( $assigned_profile_id_int ) === 'pos_profile' ) {
            $assigned_profile_post = get_post( $assigned_profile_id_int );
        }

        // Añadir el perfil asignado a la lista si no está ya (porque no estaba 'available')
        if ( $assigned_profile_post ) {
            $found = false;
            foreach ( $available_profiles as $profile ) {
                if ( $profile->ID == $assigned_profile_id_int ) {
                    $found = true;
                    break;
                }
            }
            if ( ! $found ) {
                $available_profiles[] = $assigned_profile_post;
                // Re-ordenar por título si se añadió
                usort( $available_profiles, function( $a, $b ) {
                    return strcmp( $a->post_title, $b->post_title );
                });
            }
        }
        // --- Fin lógica obtener perfiles ---

        // --- Mostrar el Selector de Perfiles ---
        ?>
        <p class="form-field form-field-wide">
            <label for="pos_assigned_profile_id"><?php esc_html_e( 'Perfil Asignado:', 'pos-streaming' ); ?></label>
            <select id="pos_assigned_profile_id" name="pos_assigned_profile_id" class="wc-enhanced-select select2" style="width:100%;">
                <option value="0" <?php selected( $assigned_profile_id, 0 ); ?>><?php esc_html_e( '-- No Asignado --', 'pos-streaming' ); ?></option>
                <?php if ( ! empty( $available_profiles ) ) : ?>
                    <?php foreach ( $available_profiles as $profile ) : ?>
                        <?php
                        // Obtener estado y cuenta padre para mostrar en la opción
                        $profile_status = get_post_meta( $profile->ID, '_pos_profile_status', true );
                        $parent_account_id = get_post_meta( $profile->ID, '_pos_profile_parent_account_id', true );
                        $account_title = ($parent_account_id) ? get_the_title($parent_account_id) : '';

                        // Crear etiquetas descriptivas
                        $status_label = '';
                        if ($profile_status === 'available') {
                            $status_label = ' (' . __('Disponible', 'pos-streaming') . ')';
                        } elseif ($profile_status === 'assigned' && $profile->ID != $assigned_profile_id_int) {
                            // Solo mostrar "Asignado a otro" si NO es el perfil actualmente asignado a ESTE pedido
                            $status_label = ' (' . __('Asignado a otro', 'pos-streaming') . ')';
                        }
                        // Si es el perfil asignado a este pedido, no mostramos estado (ya está seleccionado)

                        $account_label = ($account_title) ? ' (' . esc_html($account_title) . ')' : '';

                        ?>
                        <option value="<?php echo esc_attr( $profile->ID ); ?>" <?php selected( $assigned_profile_id, $profile->ID ); ?>>
                            <?php echo esc_html( $profile->post_title ); ?> <?php echo $account_label; // <-- Mostrar cuenta padre ?> <?php echo esc_html($status_label); ?> (ID: <?php echo esc_html($profile->ID); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </p>
        <?php
        // --- Fin Mostrar Selector ---
    } // end if ($is_streaming_active)

    echo '</div>'; // Fin .pos-metabox-content

    // --- JavaScript para el selector de color y Select2 ---
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            // Código del input color (sin cambios)
            $('#pos_subscription_color').on('input change', function(){
                $(this).next('input[type="text"]').val($(this).val());
            });
            $('#pos_assigned_profile_id').select2({
                width: '100%' // Asegurar que ocupe el ancho
            })
        });
    </script>   
    <?php
}



/**
 * Guarda los metadatos personalizados del POS desde el metabox.
 * Se engancha a los hooks de guardado de WooCommerce.
 *
 * @param int|WC_Order $order_id_or_order_object ID del pedido (CPT) o el objeto WC_Order (HPOS).
 */
function pos_base_save_order_meta_data( $order_id_or_order_object ) {

    // Obtener el objeto WC_Order y el ID de forma consistente
    $order = null;
    $order_id = 0;
    if ( is_numeric( $order_id_or_order_object ) ) {
        $order_id = absint( $order_id_or_order_object );
        $order = wc_get_order( $order_id );
    } elseif ( $order_id_or_order_object instanceof WC_Order ) {
        $order = $order_id_or_order_object;
        $order_id = $order->get_id();
    }

    // Salir si no tenemos un objeto de pedido válido
    if ( ! $order || ! $order_id ) {
        error_log('POS Base ERROR: pos_base_save_order_meta_data - No se pudo obtener el objeto WC_Order.');
        return;
    }

    // 1. Verificar Nonce
    if ( ! isset( $_POST['pos_base_order_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pos_base_order_nonce'] ) ), 'pos_base_save_order_meta_action' ) ) {
         error_log('POS Base ERROR: pos_base_save_order_meta_data - Falló la verificación del Nonce para el pedido ID: ' . $order_id);
        return;
    }

    // 2. Verificar Permisos
    if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
         error_log('POS Base ERROR: pos_base_save_order_meta_data - Permiso denegado para editar el pedido ID: ' . $order_id);
        return;
    }

    // 3. Evitar Autosave (aunque los hooks de WC suelen manejar esto)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // --- Procesar y Guardar Datos ---
    $update_needed = false; // Flag para saber si necesitamos guardar

    // Tipo de Venta
    if ( isset( $_POST['pos_sale_type'] ) ) {
        $new_sale_type = sanitize_key( $_POST['pos_sale_type'] );
        $order->update_meta_data( '_pos_sale_type', $new_sale_type );
        $update_needed = true;
        // error_log("POS Base DEBUG: Guardando _pos_sale_type: " . $new_sale_type . " para pedido " . $order_id);
    }

    // Título Suscripción
    if ( isset( $_POST['pos_subscription_title'] ) ) {
        $new_sub_title = sanitize_text_field( $_POST['pos_subscription_title'] );
        $order->update_meta_data( '_pos_subscription_title', $new_sub_title );
        $update_needed = true;
        // error_log("POS Base DEBUG: Guardando _pos_subscription_title: " . $new_sub_title . " para pedido " . $order_id);
    }

    // Vencimiento Suscripción
    if ( isset( $_POST['pos_subscription_expiry_date'] ) ) {
        $new_sub_expiry = sanitize_text_field( $_POST['pos_subscription_expiry_date'] );
        // Validar formato YYYY-MM-DD (simple)
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $new_sub_expiry ) || empty($new_sub_expiry) ) {
            $order->update_meta_data( '_pos_subscription_expiry_date', $new_sub_expiry );
            $update_needed = true;
            // error_log("POS Base DEBUG: Guardando _pos_subscription_expiry_date: " . $new_sub_expiry . " para pedido " . $order_id);
        } else {
             error_log("POS Base WARNING: Formato de fecha de vencimiento inválido: " . $new_sub_expiry . " para pedido " . $order_id);
        }
    }

    // Color Suscripción
    if ( isset( $_POST['pos_subscription_color'] ) ) {
        $new_sub_color = sanitize_hex_color( $_POST['pos_subscription_color'] );
        $order->update_meta_data( '_pos_subscription_color', $new_sub_color );
        $update_needed = true;
        // error_log("POS Base DEBUG: Guardando _pos_subscription_color: " . $new_sub_color . " para pedido " . $order_id);
    }

    // --- Perfil Asignado (Solo si el módulo streaming está activo) ---
    $active_modules = get_option( 'pos_base_active_modules', [] );
    $is_streaming_active = ( is_array( $active_modules ) && in_array( 'streaming', $active_modules, true ) );

    if ( $is_streaming_active && isset( $_POST['pos_assigned_profile_id'] ) ) {
        $old_profile_id = absint( $order->get_meta( '_pos_assigned_profile_id', true ) );
        $new_profile_id = absint( $_POST['pos_assigned_profile_id'] );

        // Solo procesar si el perfil ha cambiado
        if ( $new_profile_id !== $old_profile_id ) {
            error_log("[Streaming Metabox Save] Cambio detectado en perfil asignado para pedido $order_id. Viejo: $old_profile_id, Nuevo: $new_profile_id");

            // 1. Liberar el perfil antiguo si existía
            if ( $old_profile_id > 0 && get_post_type( $old_profile_id ) === 'pos_profile' ) {
                $update_old = update_post_meta( $old_profile_id, '_pos_profile_status', 'available' );
                error_log("[Streaming Metabox Save] Intentando liberar perfil antiguo ID $old_profile_id. Resultado: " . ($update_old ? 'Éxito' : 'Fallo'));
            }

            // 2. Asignar el perfil nuevo si se seleccionó uno válido
            if ( $new_profile_id > 0 ) {
                // Doble check que el nuevo ID es realmente un perfil
                if ( get_post_type( $new_profile_id ) === 'pos_profile' ) {
                    $update_new = update_post_meta( $new_profile_id, '_pos_profile_status', 'assigned' );
                    $order->update_meta_data( '_pos_assigned_profile_id', $new_profile_id );
                    $update_needed = true;
                    error_log("[Streaming Metabox Save] Intentando asignar perfil nuevo ID $new_profile_id. Resultado: " . ($update_new ? 'Éxito' : 'Fallo'));
                    // Añadir nota al pedido
                    $profile_title = get_the_title($new_profile_id);
                    $order->add_order_note( sprintf( __( 'Perfil Streaming cambiado a: %s (ID: %d)', 'pos-streaming' ), $profile_title, $new_profile_id ), false, false ); // Nota privada
                } else {
                    // Se envió un ID inválido, no asignar y registrar error
                    $order->delete_meta_data( '_pos_assigned_profile_id' ); // Eliminar si había uno antes
                    $update_needed = true;
                    error_log("[Streaming Metabox Save] ERROR: Se intentó asignar un ID ($new_profile_id) que no es un 'pos_profile' para el pedido $order_id.");
                    $order->add_order_note( sprintf( __( 'ERROR: Se intentó asignar un ID de perfil inválido (%d).', 'pos-streaming' ), $new_profile_id ), false, false );
                }
            } else {
                // Se seleccionó "No Asignado"
                $order->delete_meta_data( '_pos_assigned_profile_id' );
                $update_needed = true;
                error_log("[Streaming Metabox Save] Perfil desasignado para pedido $order_id.");
                 if ($old_profile_id > 0) {
                     $order->add_order_note( __( 'Perfil Streaming desasignado.', 'pos-streaming' ), false, false );
                 }
            }
        }
    } // Fin if ($is_streaming_active)

    // Guardar el pedido si hubo cambios en los metadatos
    if ( $update_needed ) {
        $order->save();
        // error_log("POS Base DEBUG: Metadatos del pedido $order_id guardados.");
    }
}
// Enganchar a ambos hooks para compatibilidad CPT y HPOS
add_action( 'woocommerce_process_shop_order_meta', 'pos_base_save_order_meta_data', 10, 1 ); // Para CPT (recibe ID)
add_action( 'woocommerce_admin_process_shop_order_object', 'pos_base_save_order_meta_data', 10, 1 ); // Para HPOS (recibe WC_Order)

?>
