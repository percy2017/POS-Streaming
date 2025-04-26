<?php
/**
 * Metaboxes para los Custom Post Types pos_account y pos_profile.
 * También incluye la personalización de las columnas de la tabla de administración para pos_account.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

// =========================================================================
// 0. REGISTRO DE METABOXES
// =========================================================================

/**
 * Registra los metaboxes para los CPTs.
 * Se engancha a 'add_meta_boxes'.
 */
function pos_streaming_cpt_add_meta_boxes() {

    // Metabox Principal para Cuentas (pos_account)
    add_meta_box(
        'pos_account_details_metabox',
        __( 'Detalles de la Cuenta POS', 'pos-streaming' ),
        'pos_streaming_render_account_metabox', // Muestra campos principales
        'pos_account',
        'normal',
        'high' // Prioridad alta
    );

    // Metabox para listar perfiles en la cuenta (pos_account)
    add_meta_box(
        'pos_account_profiles_list_metabox',
        __( 'Perfiles Asociados', 'pos-streaming' ),
        'pos_streaming_render_account_profiles_list', // Muestra lista de perfiles hijos
        'pos_account',
        'normal',
        'default' // Prioridad normal (después de 'high')
    );

    // Metabox para Perfiles (pos_profile)
    add_meta_box(
        'pos_profile_details_metabox',
        __( 'Detalles del Perfil POS', 'pos-streaming' ),
        'pos_streaming_render_profile_metabox', // Muestra campos del perfil
        'pos_profile',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'pos_streaming_cpt_add_meta_boxes' );


// =========================================================================
// 1. RENDERIZADO DE METABOXES
// =========================================================================

/**
 * Renderiza el HTML para el metabox PRINCIPAL de Cuentas (pos_account).
 * CORRECCIÓN FINAL: Obtiene el ID directamente, ignorando el $post pasado.
 *
 * @param WP_Post $post El objeto del post actual (¡QUE PUEDE SER INCORRECTO!).
 */
function pos_streaming_render_account_metabox( $post ) { // Aunque $post puede ser incorrecto, lo necesitamos para la firma de la función

    // --- OBTENER EL ID CORRECTO DIRECTAMENTE ---
    global $post; // Acceder a la variable global $post (la que usa WordPress para la página actual)
    $correct_post_id = null;
    if ( isset( $_GET['post'] ) ) {
        $correct_post_id = absint( $_GET['post'] ); // Preferir el ID de la URL
    } elseif ( isset( $post->ID ) ) {
        $correct_post_id = $post->ID; // Usar el ID global como respaldo
    }

    // Si no podemos obtener un ID válido, salir.
    if ( ! $correct_post_id || get_post_type($correct_post_id) !== 'pos_account' ) {
         error_log("DEBUG RENDER ACCOUNT METABOX: No se pudo obtener un ID de 'pos_account' válido. Saliendo.");
         echo '<p>' . esc_html__('Error: No se pudo determinar la cuenta a editar.', 'pos-streaming') . '</p>';
         return;
    }
    // --------------------------------------------

    error_log("DEBUG RENDER ACCOUNT METABOX: Iniciando para Post ID (obtenido directamente): " . $correct_post_id);

    wp_nonce_field( 'pos_save_account_meta', 'pos_account_nonce' );

    // --- Usar $correct_post_id para obtener metas ---
    $service_type = get_post_meta( $correct_post_id, '_pos_service_type', true );
    error_log("DEBUG RENDER ACCOUNT METABOX [{$correct_post_id}]: get_post_meta('_pos_service_type') devolvió: " . print_r($service_type, true));

    $total_profiles = get_post_meta( $correct_post_id, '_pos_total_profiles', true );
    error_log("DEBUG RENDER ACCOUNT METABOX [{$correct_post_id}]: get_post_meta('_pos_total_profiles') devolvió: " . print_r($total_profiles, true));

    $account_status = get_post_meta( $correct_post_id, '_pos_account_status', true );
    error_log("DEBUG RENDER ACCOUNT METABOX [{$correct_post_id}]: get_post_meta('_pos_account_status') devolvió: " . print_r($account_status, true));

    $account_expiry_date = get_post_meta( $correct_post_id, '_pos_account_expiry_date', true );
    error_log("DEBUG RENDER ACCOUNT METABOX [{$correct_post_id}]: get_post_meta('_pos_account_expiry_date') devolvió: " . print_r($account_expiry_date, true));

    $account_data = get_post_meta( $correct_post_id, '_pos_account_data', true );
    error_log("DEBUG RENDER ACCOUNT METABOX [{$correct_post_id}]: get_post_meta('_pos_account_data') devolvió: " . print_r($account_data, true));
    // --- Fin uso de $correct_post_id ---

    // --- HTML DEL METABOX (sin cambios) ---
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th><label for="pos_service_type"><?php esc_html_e( 'Tipo de Servicio:', 'pos-streaming' ); ?></label></th>
                <td>
                    <select name="pos_service_type" id="pos_service_type">
                        <option value="" <?php selected( $service_type, '' ); ?>>-- <?php esc_html_e('Seleccionar', 'pos-streaming'); ?> --</option>
                        <option value="netflix" <?php selected( $service_type, 'netflix' ); ?>>Netflix</option>
                        <option value="disney" <?php selected( $service_type, 'disney' ); ?>>Disney+</option>
                        <option value="hbo" <?php selected( $service_type, 'hbo' ); ?>>HBO Max</option>
                        <option value="prime" <?php selected( $service_type, 'prime' ); ?>>Prime Video</option>
                        <option value="spotify" <?php selected( $service_type, 'spotify' ); ?>>Spotify</option>
                        <option value="iptv" <?php selected( $service_type, 'iptv' ); ?>>IPTV</option>
                        <option value="other" <?php selected( $service_type, 'other' ); ?>><?php esc_html_e('Otro', 'pos-streaming'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Selecciona el servicio principal de esta cuenta.', 'pos-streaming'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pos_total_profiles"><?php esc_html_e( 'Nº Total de Perfiles:', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="number" id="pos_total_profiles" name="pos_total_profiles" value="<?php echo esc_attr( $total_profiles ); ?>" min="0" step="1" class="small-text" />
                    <p class="description"><?php esc_html_e('¿Cuántos perfiles/slots tiene esta cuenta en total? (Dejar en 0 si no aplica).', 'pos-streaming'); ?></p>
                </td>
            </tr>
             <tr>
                <th><label for="pos_account_status"><?php esc_html_e( 'Estado de la Cuenta:', 'pos-streaming' ); ?></label></th>
                <td>
                    <select name="pos_account_status" id="pos_account_status">
                        <option value="active" <?php selected( $account_status, 'active' ); selected( $account_status, '' ); ?>><?php esc_html_e('Activa', 'pos-streaming'); ?></option>
                        <option value="inactive" <?php selected( $account_status, 'inactive' ); ?>><?php esc_html_e('Inactiva', 'pos-streaming'); ?></option>
                        <option value="expired" <?php selected( $account_status, 'expired' ); ?>><?php esc_html_e('Expirada', 'pos-streaming'); ?></option>
                    </select>
                     <p class="description"><?php esc_html_e('Estado general de la cuenta proveedora.', 'pos-streaming'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pos_account_expiry_date"><?php esc_html_e( 'Fecha Vencimiento Cuenta:', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="date" id="pos_account_expiry_date" name="pos_account_expiry_date" value="<?php echo esc_attr( $account_expiry_date ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Fecha en que vence o necesita renovación esta cuenta proveedora.', 'pos-streaming'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pos_account_data"><?php esc_html_e( 'Datos de la Cuenta:', 'pos-streaming' ); ?></label></th>
                <td>
                    <textarea id="pos_account_data" name="pos_account_data" rows="6" class="large-text"><?php echo esc_textarea( $account_data ); ?></textarea>
                    <p class="description"><?php esc_html_e('Añade aquí credenciales, notas, fechas de pago u otros datos relevantes. ¡Ten cuidado con la información sensible!', 'pos-streaming'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
     error_log("DEBUG RENDER ACCOUNT METABOX [{$correct_post_id}]: Finalizando renderizado.");
}

/**
 * Renderiza el HTML para el metabox de Perfiles (pos_profile).
 *
 * @param WP_Post $post El objeto del post actual.
 */
function pos_streaming_render_profile_metabox( $post ) {
    // Nonce
    wp_nonce_field( 'pos_save_profile_meta', 'pos_profile_nonce' );

    // Obtener valores guardados
    $parent_account_id = get_post_meta( $post->ID, '_pos_parent_account_id', true );
    $profile_status = get_post_meta( $post->ID, '_pos_profile_status', true );
    $assigned_order_id = get_post_meta( $post->ID, '_pos_assigned_order_id', true );
    $profile_pin = get_post_meta( $post->ID, '_pos_profile_pin', true );

    // Preseleccionar cuenta padre si viene de la URL
    if ( ! $parent_account_id && isset( $_GET['parent_account_id'] ) ) {
        $potential_parent_id = absint( $_GET['parent_account_id'] );
        if ( $potential_parent_id > 0 && 'pos_account' === get_post_type( $potential_parent_id ) ) {
            $parent_account_id = $potential_parent_id;
        }
    }

    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th><label for="pos_parent_account_id"><?php esc_html_e( 'Cuenta Padre:', 'pos-streaming' ); ?></label></th>
                <td>
                    <?php
                    $accounts_query = new WP_Query( array(
                        'post_type' => 'pos_account', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
                    ) );
                    if ( $accounts_query->have_posts() ) : ?>
                        <select name="pos_parent_account_id" id="pos_parent_account_id" required>
                            <option value="" <?php selected( $parent_account_id, '' ); ?>>-- <?php esc_html_e('Seleccionar Cuenta', 'pos-streaming'); ?> --</option>
                            <?php while ( $accounts_query->have_posts() ) : $accounts_query->the_post(); ?>
                                <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected( $parent_account_id, get_the_ID() ); ?>>
                                    <?php echo esc_html( get_the_title() ); ?> (ID: <?php echo esc_html(get_the_ID()); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    <?php else : ?>
                        <p><?php esc_html_e('No hay cuentas creadas. Por favor, crea una cuenta primero.', 'pos-streaming'); ?></p>
                    <?php endif;
                    wp_reset_postdata(); // Importante después de la query
                    ?>
                    <p class="description"><?php esc_html_e('La cuenta principal a la que pertenece este perfil.', 'pos-streaming'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pos_profile_status"><?php esc_html_e( 'Estado del Perfil:', 'pos-streaming' ); ?></label></th>
                <td>
                    <select name="pos_profile_status" id="pos_profile_status">
                        <option value="available" <?php selected( $profile_status, 'available' ); selected( $profile_status, '' ); ?>><?php esc_html_e('Disponible', 'pos-streaming'); ?></option>
                        <option value="assigned" <?php selected( $profile_status, 'assigned' ); ?>><?php esc_html_e('Asignado', 'pos-streaming'); ?></option>
                        <option value="maintenance" <?php selected( $profile_status, 'maintenance' ); ?>><?php esc_html_e('Mantenimiento', 'pos-streaming'); ?></option>
                        <option value="disabled" <?php selected( $profile_status, 'disabled' ); ?>><?php esc_html_e('Deshabilitado', 'pos-streaming'); ?></option>
                    </select>
                     <p class="description"><?php esc_html_e('Estado actual de este perfil/slot.', 'pos-streaming'); ?></p>
                </td>
            </tr>
             <tr>
                <th><label for="pos_assigned_order_id"><?php esc_html_e( 'Pedido Asignado:', 'pos-streaming' ); ?></label></th>
                <td>
                    <?php if ( $assigned_order_id && $order = wc_get_order( $assigned_order_id ) ) : ?>
                        <a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" target="_blank">
                            #<?php echo esc_html( $assigned_order_id ); ?>
                        </a>
                        <button type="button" id="pos_unassign_order" class="button button-small" style="margin-left: 10px;"><?php esc_html_e('Desasignar', 'pos-streaming'); ?></button>
                    <?php elseif ($assigned_order_id) : ?>
                         <?php echo esc_html( $assigned_order_id ); ?> (<?php esc_html_e('Pedido no encontrado o borrado', 'pos-streaming'); ?>)
                    <?php else : ?>
                        <em><?php esc_html_e('No asignado', 'pos-streaming'); ?></em>
                    <?php endif; ?>
                    <input type="hidden" name="pos_assigned_order_id_current" id="pos_assigned_order_id_current" value="<?php echo esc_attr($assigned_order_id); ?>" />
                     <p class="description"><?php esc_html_e('El pedido de WooCommerce al que está asignado este perfil (si aplica).', 'pos-streaming'); ?></p>
                     <script type="text/javascript">
                        jQuery(document).ready(function($){
                            $('#pos_unassign_order').on('click', function(e){
                                e.preventDefault();
                                if (confirm('<?php echo esc_js(__('¿Seguro que quieres desasignar este perfil del pedido y marcarlo como Disponible?', 'pos-streaming')); ?>')) {
                                    $('#pos_profile_status').val('available');
                                    $('#pos_assigned_order_id_current').val('');
                                    $(this).closest('td').find('a, button').remove();
                                    $(this).closest('td').prepend('<em><?php echo esc_js(__('No asignado', 'pos-streaming')); ?></em>');
                                }
                            });
                        });
                     </script>
                </td>
            </tr>
             <tr>
                <th><label for="pos_profile_pin"><?php esc_html_e( 'PIN del Perfil (Opcional):', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="text" id="pos_profile_pin" name="pos_profile_pin" value="<?php echo esc_attr( $profile_pin ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Si el perfil tiene un PIN específico.', 'pos-streaming'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Renderiza el HTML para el metabox que lista los perfiles asociados.
 * CORREGIDO: La consulta busca 'pos_profile'.
 *
 * @param WP_Post $post El objeto del post actual (de tipo pos_account).
 */
function pos_streaming_render_account_profiles_list( $post ) {
    $account_id = $post->ID; // ID de la cuenta actual

    // Query para buscar perfiles hijos de ESTA cuenta
    $profiles_query = new WP_Query( array(
        'post_type' => 'pos_profile', // <-- CORREGIDO: Buscar perfiles
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_pos_parent_account_id', // Meta del perfil que guarda el ID de la cuenta padre
                'value' => $account_id,
                'compare' => '=',
            ),
        ),
        'orderby' => 'title',
        'order' => 'ASC',
    ) );

    // Enlace para añadir un nuevo perfil pre-asociado a esta cuenta
    $add_new_profile_link = admin_url( 'post-new.php?post_type=pos_profile&parent_account_id=' . $account_id );

    echo '<div class="pos-profiles-list-wrapper">';

    if ( $profiles_query->have_posts() ) :
        echo '<ul style="margin-top: 0;">';
        while ( $profiles_query->have_posts() ) : $profiles_query->the_post(); // El bucle modifica $post global temporalmente
            $profile_id = get_the_ID();
            $profile_title = get_the_title();
            $profile_status = get_post_meta( $profile_id, '_pos_profile_status', true ) ?: 'available';
            $status_label = ucfirst($profile_status);
            $edit_link = get_edit_post_link( $profile_id );
            ?>
            <li style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px dotted #eee;">
                <strong><?php echo esc_html( $profile_title ); ?></strong>
                (<?php printf( esc_html__('Estado: %s', 'pos-streaming'), '<em>' . esc_html($status_label) . '</em>' ); ?>)
                <?php if ($edit_link): ?>
                    - <a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Editar', 'pos-streaming'); ?></a>
                <?php endif; ?>
            </li>
            <?php
        endwhile;
        echo '</ul>';
    else :
        echo '<p><em>' . esc_html__( 'No se han encontrado perfiles asociados a esta cuenta.', 'pos-streaming' ) . '</em></p>';
    endif;

    wp_reset_postdata(); // <-- IMPORTANTE: Restaurar el $post global original (el de la cuenta)

    // Botón/Enlace para añadir nuevo perfil
    echo '<p><a href="' . esc_url( $add_new_profile_link ) . '" class="button button-secondary">' . esc_html__( 'Añadir Nuevo Perfil a esta Cuenta', 'pos-streaming' ) . '</a></p>';

    echo '</div>';
}


// =========================================================================
// 2. GUARDADO DE METADATOS
// =========================================================================

/**
 * Guarda los metadatos al guardar/actualizar un post 'pos_account'.
 *
 * @param int $post_id ID del post que se está guardando.
 * @param WP_Post $post Objeto del post.
 */
function pos_streaming_save_account_meta( $post_id, $post ) {
    // Verificaciones (Nonce, Autoguardado, Permisos, Tipo Post)
    if ( ! isset( $_POST['pos_account_nonce'] ) || ! wp_verify_nonce( $_POST['pos_account_nonce'], 'pos_save_account_meta' ) ) return $post_id;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
    if ( 'pos_account' !== $post->post_type ) return $post_id;

    // --- Guardar los campos ---
    if ( isset( $_POST['pos_service_type'] ) ) {
        update_post_meta( $post_id, '_pos_service_type', sanitize_key( $_POST['pos_service_type'] ) );
    }
    if ( isset( $_POST['pos_total_profiles'] ) ) {
        update_post_meta( $post_id, '_pos_total_profiles', absint( $_POST['pos_total_profiles'] ) );
    }
    if ( isset( $_POST['pos_account_status'] ) ) {
        update_post_meta( $post_id, '_pos_account_status', sanitize_key( $_POST['pos_account_status'] ) );
    }
    if ( isset( $_POST['pos_account_expiry_date'] ) ) {
        $expiry_date = sanitize_text_field( $_POST['pos_account_expiry_date'] );
        if ( preg_match("/^\d{4}-\d{2}-\d{2}$/", $expiry_date) ) {
             update_post_meta( $post_id, '_pos_account_expiry_date', $expiry_date );
        } else {
             delete_post_meta( $post_id, '_pos_account_expiry_date' );
        }
    } else {
         delete_post_meta( $post_id, '_pos_account_expiry_date' );
    }
    if ( isset( $_POST['pos_account_data'] ) ) {
        update_post_meta( $post_id, '_pos_account_data', sanitize_textarea_field( $_POST['pos_account_data'] ) );
    } else {
        // Si el campo textarea no se envía (ej. si se elimina del HTML), borrar el meta
        delete_post_meta($post_id, '_pos_account_data');
    }
}
add_action( 'save_post_pos_account', 'pos_streaming_save_account_meta', 10, 2 );

/**
 * Guarda los metadatos al guardar/actualizar un post 'pos_profile'.
 *
 * @param int $post_id ID del post que se está guardando.
 * @param WP_Post $post Objeto del post.
 */
function pos_streaming_save_profile_meta( $post_id, $post ) {
     // Verificaciones
    if ( ! isset( $_POST['pos_profile_nonce'] ) || ! wp_verify_nonce( $_POST['pos_profile_nonce'], 'pos_save_profile_meta' ) ) return $post_id;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
    if ( 'pos_profile' !== $post->post_type ) return $post_id;

    // Guardar campos
    if ( isset( $_POST['pos_parent_account_id'] ) ) {
        $parent_id = absint( $_POST['pos_parent_account_id'] );
        if ( $parent_id > 0 && 'pos_account' === get_post_type( $parent_id ) ) {
             update_post_meta( $post_id, '_pos_parent_account_id', $parent_id );
        } else {
             delete_post_meta( $post_id, '_pos_parent_account_id' );
        }
    }
    if ( isset( $_POST['pos_profile_status'] ) ) {
        $status = sanitize_key( $_POST['pos_profile_status'] );
        update_post_meta( $post_id, '_pos_profile_status', $status );

        $current_assigned_order_id = isset($_POST['pos_assigned_order_id_current']) ? absint($_POST['pos_assigned_order_id_current']) : 0;
        // Si el estado es 'available' Y el ID oculto está vacío (porque se hizo clic en desasignar)
        if ( $status === 'available' && $current_assigned_order_id === 0 ) {
            delete_post_meta( $post_id, '_pos_assigned_order_id' );
        }
        // Nota: La asignación de un NUEVO pedido se hará desde la API al crear la orden.
    }
    if ( isset( $_POST['pos_profile_pin'] ) ) {
        update_post_meta( $post_id, '_pos_profile_pin', sanitize_text_field( $_POST['pos_profile_pin'] ) );
    }
}
add_action( 'save_post_pos_profile', 'pos_streaming_save_profile_meta', 10, 2 );


// =========================================================================
// 3. PERSONALIZACIÓN COLUMNAS TABLA ADMIN 'pos_account'
// =========================================================================

/**
 * Añade/Modifica las columnas mostradas en la tabla de administración para 'pos_account'.
 *
 * @param array $columns Array existente de columnas.
 * @return array Array modificado de columnas.
 */
function pos_streaming_add_account_columns( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['service_type'] = __( 'Servicio', 'pos-streaming' );
    $new_columns['profiles'] = __( 'Perfiles (Disp/Total)', 'pos-streaming' );
    $new_columns['status'] = __( 'Estado Cuenta', 'pos-streaming' );
    $new_columns['expiry_date'] = __( 'Vencimiento', 'pos-streaming' );
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter( 'manage_pos_account_posts_columns', 'pos_streaming_add_account_columns' );

/**
 * Muestra el contenido para las columnas personalizadas en la tabla de 'pos_account'.
 *
 * @param string $column_name La clave de la columna actual.
 * @param int    $post_id     El ID del post actual (pos_account).
 */
function pos_streaming_display_account_columns( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'service_type':
            $service = get_post_meta( $post_id, '_pos_service_type', true );
            echo $service ? esc_html( ucfirst( $service ) ) : 'N/A';
            break;
        case 'profiles':
            $total = get_post_meta( $post_id, '_pos_total_profiles', true );
            $total_display = ($total === '0' || $total > 0) ? absint($total) : 'N/A';
            $available_count = 'N/A';
            if (is_numeric($total) && $total > 0) {
                $args_profiles = array(
                    'post_type' => 'pos_profile', 'post_status' => 'any', 'posts_per_page' => -1,
                    'meta_query' => array( 'relation' => 'AND',
                        array( 'key' => '_pos_parent_account_id', 'value' => $post_id, 'compare' => '=' ),
                        array( 'key' => '_pos_profile_status', 'value' => 'available', 'compare' => '=' ),
                    ), 'fields' => 'ids',
                );
                $available_query = new WP_Query($args_profiles);
                $available_count = $available_query->found_posts;
            }
            echo esc_html( $available_count ) . ' / ' . esc_html( $total_display );
            break;
        case 'status':
            $status = get_post_meta( $post_id, '_pos_account_status', true );
            echo $status ? esc_html( ucfirst( $status ) ) : __('Activa', 'pos-streaming');
            break;
        case 'expiry_date':
            $expiry_date = get_post_meta( $post_id, '_pos_account_expiry_date', true );
            if ( $expiry_date && preg_match("/^\d{4}-\d{2}-\d{2}$/", $expiry_date) ) {
                try {
                     $date_obj = new DateTime($expiry_date);
                     echo esc_html( $date_obj->format(get_option('date_format')) );
                } catch (Exception $e) { echo 'N/A'; }
            } else { echo 'N/A'; }
            break;
    }
}
add_action( 'manage_pos_account_posts_custom_column', 'pos_streaming_display_account_columns', 10, 2 );

/**
 * Define qué columnas personalizadas son ordenables.
 *
 * @param array $sortable_columns Array existente de columnas ordenables.
 * @return array Array modificado.
 */
function pos_streaming_make_account_columns_sortable( $sortable_columns ) {
    $sortable_columns['service_type'] = '_pos_service_type';
    $sortable_columns['status'] = '_pos_account_status';
    $sortable_columns['expiry_date'] = '_pos_account_expiry_date';
    return $sortable_columns;
}
add_filter( 'manage_edit-pos_account_sortable_columns', 'pos_streaming_make_account_columns_sortable' );

/**
 * Modifica la WP_Query principal cuando se ordena por una columna personalizada.
 *
 * @param WP_Query $query La consulta principal de la tabla de administración.
 */
function pos_streaming_sort_account_columns_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || $query->get('post_type') !== 'pos_account' ) {
        return;
    }
    $orderby = $query->get( 'orderby' );

    if ( '_pos_service_type' === $orderby || '_pos_account_status' === $orderby ) {
        $query->set( 'meta_key', $orderby );
        $query->set( 'orderby', 'meta_value' );
    } elseif ( '_pos_account_expiry_date' === $orderby ) {
        $query->set( 'meta_key', $orderby );
        $query->set( 'orderby', 'meta_value' );
        // $query->set( 'meta_type', 'DATE' ); // Descomentar si la ordenación de fecha no funciona bien
    }
}
add_action( 'pre_get_posts', 'pos_streaming_sort_account_columns_query' );

?>
