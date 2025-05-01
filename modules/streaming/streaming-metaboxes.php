<?php
/**
 * Define y maneja los metaboxes para los CPTs del Módulo Streaming.
 * PRIORITIZA $_GET['post'] para obtener el ID en la pantalla de edición.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Registra los metaboxes para los CPTs del módulo.
 * Se engancha a 'add_meta_boxes'.
 */
function streaming_add_metaboxes() {

    // Metabox para Detalles de la Cuenta Streaming (pos_account)
    add_meta_box(
        'streaming_account_details_metabox',
        __( 'Detalles de la Cuenta Streaming', 'pos-streaming' ),
        'streaming_account_metabox_html',
        'pos_account',
        'normal',
        'high'
    );

    // Metabox para Perfiles Asociados (pos_account)
    add_meta_box(
        'streaming_account_related_profiles_metabox',
        __( 'Perfiles Asociados', 'pos-streaming' ),
        'streaming_account_related_profiles_html',
        'pos_account',
        'side', // Columna lateral
        'default'
    );

    // Metabox para Detalles del Perfil Streaming (pos_profile)
    add_meta_box(
        'streaming_profile_details_metabox',
        __( 'Detalles del Perfil Streaming', 'pos-streaming' ),
        'streaming_profile_metabox_html',
        'pos_profile',
        'normal',
        'high'
    );
}
// El add_action('add_meta_boxes', 'streaming_add_metaboxes') se hará en pos-streaming-module.php


/**
 * Obtiene el ID del post que se está editando de forma robusta.
 * Prioriza $_GET['post'] en la pantalla de edición (post.php).
 *
 * @return int|false El ID del post o false si no se puede determinar.
 */
function streaming_get_current_post_id_robustly() {
    global $pagenow;
    $post_id = false;

    // En la pantalla de edición (post.php), $_GET['post'] es lo más fiable
    if ( is_admin() && $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
        $post_id = absint( $_GET['post'] );
        // error_log('[DEBUG Get ID Robustly] Using $_GET[post]: ' . $post_id); // Descomentar para depurar si es necesario
    }
    // Fallback a get_the_ID() (puede fallar si $post global está corrupto)
    elseif ( get_the_ID() ) {
        $post_id = get_the_ID();
        // error_log('[DEBUG Get ID Robustly] Using get_the_ID(): ' . $post_id); // Descomentar para depurar si es necesario
    }
     // Fallback a $_POST['post_ID'] (útil durante el guardado, menos útil para mostrar)
    elseif ( isset( $_POST['post_ID'] ) ) {
         $post_id = absint( $_POST['post_ID'] );
         // error_log('[DEBUG Get ID Robustly] Using $_POST[post_ID]: ' . $post_id); // Descomentar para depurar si es necesario
    }

    if ( ! $post_id ) {
        error_log('[DEBUG Get ID Robustly] Failed to determine Post ID.');
    }

    return $post_id;
}


/**
 * Muestra el HTML para el metabox de Detalles de la Cuenta Streaming.
 *
 * @param WP_Post $post El objeto del post actual (puede ser incorrecto).
 */
function streaming_account_metabox_html( $post ) { // $post no se usa para el ID

    // --- OBTENER EL ID CORRECTO (ROBUSTAMENTE) ---
    $current_post_id = streaming_get_current_post_id_robustly();
    if ( ! $current_post_id ) {
        echo '<p>Error: No se pudo determinar el ID del post actual.</p>';
        return;
    }
    // --- FIN OBTENER EL ID CORRECTO ---

    wp_nonce_field( 'streaming_save_account_details_action', 'streaming_account_nonce' );


    // Obtener valores guardados USANDO $current_post_id
    $provider       = get_post_meta( $current_post_id, '_pos_account_provider', true );
    $email_user     = get_post_meta( $current_post_id, '_pos_account_email_user', true );
    $password       = get_post_meta( $current_post_id, '_pos_account_password', true );
    $pin            = get_post_meta( $current_post_id, '_pos_account_pin', true );
    $profiles_count = get_post_meta( $current_post_id, '_pos_account_profiles_count', true );
    $expiry_date    = get_post_meta( $current_post_id, '_pos_account_expiry_date', true );
    $description    = get_post_meta( $current_post_id, '_pos_account_description', true );

    // Log para depuración
    error_log('[DEBUG Metabox Load - Data (Account Details)] ID: ' . $current_post_id . ' | Provider: ' . $provider . ' | Email: ' . $email_user . ' | Expiry: ' . $expiry_date);

    ?>
    <style> .form-table th { width: 150px; } </style>
    <table class="form-table">
        <tbody>
            <!-- Campo Email/Usuario -->
            <tr>
                <th scope="row"><label for="pos-account-email-user"><?php esc_html_e( 'Email / Usuario', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="text" id="pos-account-email-user" name="_pos_account_email_user" class="regular-text" value="<?php echo esc_attr( $email_user ); ?>" placeholder="<?php esc_attr_e( 'ej: usuario@dominio.com', 'pos-streaming' ); ?>">
                    <p class="description"><?php esc_html_e( 'El correo o nombre de usuario para acceder a la cuenta del proveedor.', 'pos-streaming' ); ?></p>
                </td>
            </tr>
            <!-- Campo Contraseña -->
            <tr>
                <th scope="row"><label for="pos-account-password"><?php esc_html_e( 'Contraseña', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="password" id="pos-account-password" name="_pos_account_password" class="regular-text" value="<?php echo esc_attr( $password ); ?>" placeholder="<?php esc_attr_e( 'Introduce la contraseña', 'pos-streaming' ); ?>" autocomplete="new-password">
                     <p class="description"><?php esc_html_e( 'La contraseña de la cuenta. ', 'pos-streaming' ); ?><em style="color: #c00;"><?php esc_html_e( 'Nota: Se guarda como texto plano.', 'pos-streaming' ); ?></em></p>
                     <button type="button" class="button button-secondary" id="toggle-password-visibility" style="margin-top: 5px;"><?php esc_html_e('Mostrar/Ocultar', 'pos-streaming'); ?></button>
                </td>
            </tr>
            <!-- Campo PIN Cuenta -->
            <tr>
                <th scope="row"><label for="pos-account-pin"><?php esc_html_e( 'PIN Cuenta (Opcional)', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="text" id="pos-account-pin" name="_pos_account_pin" class="regular-text" value="<?php echo esc_attr( $pin ); ?>" placeholder="<?php esc_attr_e( 'PIN de acceso general, si aplica', 'pos-streaming' ); ?>" autocomplete="off">
                    <p class="description"><?php esc_html_e( 'Algunas cuentas pueden tener un PIN general.', 'pos-streaming' ); ?></p>
                </td>
            </tr>
            <!-- Campo Cantidad de Perfiles -->
            <tr>
                <th scope="row"><label for="pos-account-profiles-count"><?php esc_html_e( 'Cantidad de Perfiles', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="number" id="pos-account-profiles-count" name="_pos_account_profiles_count" class="small-text" value="<?php echo esc_attr( $profiles_count ); ?>" min="1" step="1" placeholder="ej: 5">
                    <p class="description"><?php esc_html_e( 'Número total de perfiles o pantallas que permite esta cuenta.', 'pos-streaming' ); ?></p>
                </td>
            </tr>
            <!-- Campo Fecha de Vencimiento -->
            <tr>
                <th scope="row"><label for="pos-account-expiry-date"><?php esc_html_e( 'Fecha de Vencimiento', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="date" id="pos-account-expiry-date" name="_pos_account_expiry_date" value="<?php echo esc_attr( $expiry_date ); ?>" class="regular-text" pattern="\d{4}-\d{2}-\d{2}">
                    <p class="description"><?php esc_html_e( 'Fecha en que la cuenta con el proveedor expira o necesita renovación (YYYY-MM-DD).', 'pos-streaming' ); ?></p>
                </td>
            </tr>
            <!-- Campo Descripción/Notas -->
            <tr>
                <th scope="row"><label for="pos-account-description"><?php esc_html_e( 'Descripción / Notas', 'pos-streaming' ); ?></label></th>
                <td>
                    <textarea id="pos-account-description" name="_pos_account_description" class="large-text" rows="4"><?php echo esc_textarea( $description ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Notas internas sobre esta cuenta o proveedor.', 'pos-streaming' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            if ( ! $('#toggle-password-visibility').data('initialized') ) {
                $('#toggle-password-visibility').data('initialized', true).on('click', function() {
                    var passwordInput = $('#pos-account-password');
                    var currentType = passwordInput.attr('type');
                    passwordInput.attr('type', currentType === 'password' ? 'text' : 'password');
                });
            }
        });
    </script>
    <?php
}


/**
 * Muestra el HTML para el metabox de Perfiles Asociados en la Cuenta Streaming.
 *
 * @param WP_Post $post El objeto del post actual (puede ser incorrecto).
 */
function streaming_account_related_profiles_html( $post ) { // $post no se usa para el ID

    // --- OBTENER EL ID CORRECTO (ROBUSTAMENTE) ---
    $account_id = streaming_get_current_post_id_robustly();
    if ( ! $account_id ) {
        echo '<p>' . esc_html__( 'Error: No se pudo determinar el ID de la cuenta.', 'pos-streaming' ) . '</p>';
        return;
    }
    // --- FIN OBTENER EL ID CORRECTO ---

    $args = array(
        'post_type'      => 'pos_profile',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'meta_key'       => '_pos_profile_parent_account_id',
        'meta_value'     => $account_id,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    $profiles_query = new WP_Query( $args );

    if ( $profiles_query->have_posts() ) {
        echo '<ul>';
        while ( $profiles_query->have_posts() ) {
            $profiles_query->the_post();
            $profile_id = get_the_ID();
            $profile_title = get_the_title();
            $profile_status_key = get_post_meta( $profile_id, '_pos_profile_status', true );
            $profile_status_label = '';
            $possible_statuses = array(
                'available'    => __( 'Disponible', 'pos-streaming' ),
                'assigned'     => __( 'Asignado', 'pos-streaming' ),
                'maintenance'  => __( 'Mantenimiento', 'pos-streaming' ),
                'expired'      => __( 'Expirado', 'pos-streaming' ),
            );
            if ( isset( $possible_statuses[$profile_status_key] ) ) { $profile_status_label = $possible_statuses[$profile_status_key]; }
            $edit_link = get_edit_post_link( $profile_id );
            echo '<li>';
            if ( $edit_link ) { echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $profile_title ) . '</a>'; }
            else { echo esc_html( $profile_title ); }
            if ( $profile_status_label ) { echo ' <span style="color: #666; font-size: smaller;">(' . esc_html( $profile_status_label ) . ')</span>'; }
            echo '</li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__( 'No hay perfiles asociados a esta cuenta todavía.', 'pos-streaming' ) . '</p>';
    }

    // Añadir el ID de la cuenta actual a la URL para preseleccionar
    $add_new_profile_url = add_query_arg(
        array('post_type' => 'pos_profile', 'parent_account_id' => $account_id),
        admin_url('post-new.php')
    );
    echo '<p style="margin-top: 10px;">';
    echo '<a href="' . esc_url( $add_new_profile_url ) . '" class="button button-secondary">' . esc_html__('Añadir Nuevo Perfil', 'pos-streaming') . '</a>';
    echo '</p>';
}


/**
 * Muestra el HTML para el metabox de Detalles del Perfil Streaming.
 *
 * @param WP_Post $post El objeto del post actual (el perfil).
 */
function streaming_profile_metabox_html( $post ) {
    // --- OBTENER EL ID CORRECTO (ROBUSTAMENTE) ---
    // Aplicamos la misma lógica aquí por si acaso
    $current_post_id = streaming_get_current_post_id_robustly();
    if ( ! $current_post_id && $GLOBALS['pagenow'] !== 'post-new.php' ) { // Permitir en post-new.php
        echo '<p>Error: No se pudo determinar el ID del perfil actual.</p>';
        return;
    }
    // --- FIN OBTENER EL ID CORRECTO ---

    wp_nonce_field( 'streaming_save_profile_details_action', 'streaming_profile_nonce' );

    // Obtener valores guardados USANDO $current_post_id (para edición)
    $parent_account_id = $current_post_id ? get_post_meta( $current_post_id, '_pos_profile_parent_account_id', true ) : false;
    $profile_pin       = $current_post_id ? get_post_meta( $current_post_id, '_pos_profile_pin', true ) : '';
    $profile_status    = $current_post_id ? get_post_meta( $current_post_id, '_pos_profile_status', true ) : '';

    // --- PRESELECCIÓN EN PANTALLA 'AÑADIR NUEVO' ---
    // Si estamos en la pantalla de añadir nuevo (post-new.php) y se pasó un ID de cuenta padre en la URL
    global $pagenow;
    if ( $pagenow === 'post-new.php' && isset( $_GET['parent_account_id'] ) && empty( $parent_account_id ) ) {
        $parent_account_id = absint( $_GET['parent_account_id'] );
        error_log('[DEBUG Metabox Load - Preselect Parent] Using parent_account_id from URL: ' . $parent_account_id);
    }
    // --- FIN PRESELECCIÓN ---

    $possible_statuses = array(
        'available'    => __( 'Disponible', 'pos-streaming' ),
        'assigned'     => __( 'Asignado', 'pos-streaming' ),
        'maintenance'  => __( 'Mantenimiento', 'pos-streaming' ),
        'expired'      => __( 'Expirado', 'pos-streaming' ),
    );

    ?>
    <table class="form-table">
        <tbody>
            <!-- Campo Cuenta Padre (Selector) -->
            <tr>
                <th scope="row"><label for="pos-profile-parent-account"><?php esc_html_e( 'Cuenta Padre', 'pos-streaming' ); ?></label></th>
                <td>
                    <?php
                    $accounts_query = new WP_Query( array(
                        'post_type' => 'pos_account',
                        'post_status' => 'publish', // O 'any' si quieres incluir borradores, etc.
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ) );
                    if ( $accounts_query->have_posts() ) : ?>
                        <select id="pos-profile-parent-account" name="_pos_profile_parent_account_id" class="regular-text">
                            <option value=""><?php esc_html_e( '-- Selecciona una Cuenta --', 'pos-streaming' ); ?></option>
                            <?php while ( $accounts_query->have_posts() ) : $accounts_query->the_post(); ?>
                                <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected( $parent_account_id, get_the_ID() ); ?>><?php echo esc_html( get_the_title() ); ?> (ID: <?php echo esc_html(get_the_ID()); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <?php wp_reset_postdata();
                    else :
                        esc_html_e( 'No hay cuentas streaming creadas. Por favor, crea una primero.', 'pos-streaming' );
                    endif; ?>
                    <p class="description"><?php esc_html_e( 'Selecciona la cuenta de proveedor a la que pertenece este perfil.', 'pos-streaming' ); ?></p>
                </td>
            </tr>
            <!-- Campo PIN del Perfil -->
            <tr>
                <th scope="row"><label for="pos-profile-pin"><?php esc_html_e( 'PIN del Perfil (Opcional)', 'pos-streaming' ); ?></label></th>
                <td>
                    <input type="text" id="pos-profile-pin" name="_pos_profile_pin" class="regular-text" value="<?php echo esc_attr( $profile_pin ); ?>" placeholder="<?php esc_attr_e( 'PIN específico para este perfil', 'pos-streaming' ); ?>" autocomplete="off">
                    <p class="description"><?php esc_html_e( 'Si este perfil específico tiene un PIN de acceso.', 'pos-streaming' ); ?></p>
                </td>
            </tr>
            <!-- Campo Estado del Perfil -->
            <tr>
                <th scope="row"><label for="pos-profile-status"><?php esc_html_e( 'Estado del Perfil', 'pos-streaming' ); ?></label></th>
                <td>
                    <select id="pos-profile-status" name="_pos_profile_status" class="regular-text">
                        <option value=""><?php esc_html_e( '-- Selecciona Estado --', 'pos-streaming' ); ?></option>
                        <?php foreach ( $possible_statuses as $status_key => $status_label ) : ?>
                            <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $profile_status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'El estado actual de este perfil (ej: si está libre o ya asignado a un cliente).', 'pos-streaming' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}



/**
 * Guarda los datos de los metaboxes cuando se guarda un CPT del módulo.
 *
 * @param int     $post_id ID del post que se está guardando.
 * @param WP_Post $post    Objeto del post.
 */
function streaming_save_metabox_data( $post_id, $post ) {
    // --- Verificaciones de Seguridad y Contexto ---
    $nonce_name = ''; $action_name = '';
    if ($post->post_type === 'pos_account') { $nonce_name = 'streaming_account_nonce'; $action_name = 'streaming_save_account_details_action'; }
    elseif ($post->post_type === 'pos_profile') { $nonce_name = 'streaming_profile_nonce'; $action_name = 'streaming_save_profile_details_action'; }
    if ( empty($nonce_name) || ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $action_name ) ) { return $post_id; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }
    if ( wp_is_post_revision( $post_id ) ) { return $post_id; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return $post_id; }
    if ( ! in_array( $post->post_type, array( 'pos_account', 'pos_profile' ) ) ) { return $post_id; }
    // --- Fin Verificaciones ---

    // --- Guardar Datos Específicos de 'pos_account' ---
    if ( $post->post_type === 'pos_account' ) {
        $account_fields = array(
            // '_pos_account_provider' => 'sanitize_key', // Eliminado: Se maneja por el metabox de taxonomía estándar
            '_pos_account_email_user' => 'sanitize_text_field',
            '_pos_account_password' => null, // No sanitizar aquí, se guarda tal cual
            '_pos_account_pin' => 'sanitize_text_field',
            '_pos_account_profiles_count' => 'absint',
            '_pos_account_expiry_date' => 'sanitize_text_field',
            '_pos_account_description' => 'sanitize_textarea_field'
        );

        foreach ( $account_fields as $meta_key => $sanitize_callback ) {
            if ( isset( $_POST[$meta_key] ) ) {
                $value = $_POST[$meta_key];
                $sanitized_value = $value; // Valor por defecto

                // Sanitizar si hay callback y existe la función
                if ( $sanitize_callback && function_exists( $sanitize_callback ) ) {
                    $sanitized_value = call_user_func( $sanitize_callback, $value );
                }

                $is_valid = true; // Asumir validez inicial

                // Validación específica para fecha
                if ($meta_key === '_pos_account_expiry_date' && !empty($sanitized_value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sanitized_value)) {
                    $is_valid = false;
                    error_log('[Streaming Save Error] Invalid date format for _pos_account_expiry_date: ' . $sanitized_value);
                }

                if ( $is_valid ) {
                    // Guardar o actualizar si hay valor (o si es contraseña/pin que pueden estar vacíos intencionalmente)
                    if ( ! empty( $sanitized_value ) || $meta_key === '_pos_account_password' || $meta_key === '_pos_account_pin' ) {
                        update_post_meta( $post_id, $meta_key, $sanitized_value );
                        error_log('[Streaming Save DEBUG] Updated meta: ' . $meta_key . ' for post ' . $post_id . ' with value: ' . $sanitized_value);

                        // La asignación del término 'streaming_provider' la hace el metabox estándar.

                    } else {
                        // Si el valor está vacío y no es contraseña/pin, eliminar el meta
                        delete_post_meta( $post_id, $meta_key );
                        error_log('[Streaming Save DEBUG] Deleted meta (empty value): ' . $meta_key . ' for post ' . $post_id);
                    /*
                        // Si se borra el proveedor, también quitar el término
                        if ( $meta_key === '_pos_account_provider' ) {
                             wp_set_object_terms( $post_id, null, 'streaming_provider', false );
                             error_log('[Streaming Save DEBUG] Removed terms for streaming_provider from post ' . $post_id);
                        }
                    */
                    }
                } else {
                    // Si no es válido (ej: fecha mal formateada), eliminar el meta para no guardar datos incorrectos
                    delete_post_meta( $post_id, $meta_key );
                    error_log('[Streaming Save DEBUG] Deleted meta (invalid value): ' . $meta_key . ' for post ' . $post_id);
                }
            } else {
                // Si el campo no se envió en el POST, eliminar el meta
                delete_post_meta( $post_id, $meta_key );
                error_log('[Streaming Save DEBUG] Deleted meta (not in POST): ' . $meta_key . ' for post ' . $post_id);
                /*
                 // Si se borra el proveedor, también quitar el término
                 if ( $meta_key === '_pos_account_provider' ) {
                    wp_set_object_terms( $post_id, null, 'streaming_provider', false );
                    error_log('[Streaming Save DEBUG] Removed terms for streaming_provider from post ' . $post_id . ' (field not in POST)');
                */
            //    } // <-- Esta llave de cierre estaba mal comentada, la corregí
            }
        }
    }
    // --- Guardar Datos Específicos de 'pos_profile' ---
    elseif ( $post->post_type === 'pos_profile' ) {
        $valid_statuses = array( 'available', 'assigned', 'maintenance', 'expired' );
        // Guardar Cuenta Padre
        if ( isset( $_POST['_pos_profile_parent_account_id'] ) ) {
            $parent_id = absint( $_POST['_pos_profile_parent_account_id'] );
            if ( $parent_id > 0 && get_post_type( $parent_id ) === 'pos_account' ) {
                update_post_meta( $post_id, '_pos_profile_parent_account_id', $parent_id );
            } else {
                delete_post_meta( $post_id, '_pos_profile_parent_account_id' );
            }
        } else {
            delete_post_meta( $post_id, '_pos_profile_parent_account_id' );
        }
        // Guardar PIN del Perfil
        if ( isset( $_POST['_pos_profile_pin'] ) ) {
            // Guardar incluso si está vacío, ya que un PIN vacío es válido
            update_post_meta( $post_id, '_pos_profile_pin', sanitize_text_field( $_POST['_pos_profile_pin'] ) );
        } else {
            // Si no se envía, asumir que no hay PIN
            delete_post_meta( $post_id, '_pos_profile_pin' );
        }
        // Guardar Estado del Perfil
        if ( isset( $_POST['_pos_profile_status'] ) ) {
            $status = sanitize_key( $_POST['_pos_profile_status'] );
            if ( in_array( $status, $valid_statuses ) ) {
                update_post_meta( $post_id, '_pos_profile_status', $status );
            } else {
                delete_post_meta( $post_id, '_pos_profile_status' );
            }
        } else {
            delete_post_meta( $post_id, '_pos_profile_status' );
        }
    }
}

?>
