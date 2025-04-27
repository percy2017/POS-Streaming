<?php
/**
 * Contiene los hooks (actions y filters) para el Módulo Streaming.
 * Incluye personalizaciones de la tabla de administración para CPTs.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Añade columnas personalizadas a la tabla de administración de Cuentas Streaming.
 *
 * @param array $columns Array de columnas existentes.
 * @return array Array de columnas modificado.
 */
function streaming_add_account_columns( $columns ) {
    // Crear un nuevo array para controlar el orden
    $new_columns = array();

    // Mantener checkbox y título
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];

    // Añadir nuestras columnas personalizadas
    $new_columns['email_user'] = __( 'Email / Usuario', 'pos-streaming' );
    $new_columns['profiles_count'] = __( 'Perfiles', 'pos-streaming' );
    $new_columns['expiry_date'] = __( 'Vencimiento', 'pos-streaming' );
    $new_columns['logo'] = __( 'Logo', 'pos-streaming' ); // Para la imagen destacada

    // Eliminar la columna de fecha por defecto (opcional)
    // unset( $columns['date'] );

    return $new_columns; // Devolver el nuevo array ordenado
}
// Enganchar al filtro específico para el CPT pos_account
add_filter( 'manage_pos_account_posts_columns', 'streaming_add_account_columns' );


/**
 * Muestra el contenido para las columnas personalizadas en la tabla de Cuentas Streaming.
 *
 * @param string $column_name El nombre de la columna actual.
 * @param int    $post_id     El ID del post (cuenta) actual.
 */
function streaming_display_account_column_content( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'email_user':
            $email = get_post_meta( $post_id, '_pos_account_email_user', true );
            echo esc_html( $email );
            break;

        case 'profiles_count':
            $count = get_post_meta( $post_id, '_pos_account_profiles_count', true );
            echo esc_html( $count ? absint( $count ) : '-' ); // Mostrar número o guión
            break;

        case 'expiry_date':
            $date = get_post_meta( $post_id, '_pos_account_expiry_date', true );
            if ( $date ) {
                try {
                    // Intentar formatear la fecha según los ajustes de WordPress
                    $datetime = new DateTime( $date );
                    echo esc_html( $datetime->format( get_option( 'date_format', 'Y-m-d' ) ) );
                } catch ( Exception $e ) {
                    // Si la fecha no es válida, mostrarla tal cual (sanitizada)
                    echo esc_html( $date );
                }
            } else {
                echo '-'; // Mostrar guión si no hay fecha
            }
            break;

        case 'logo':
            if ( has_post_thumbnail( $post_id ) ) {
                // Mostrar la miniatura con un tamaño pequeño
                echo get_the_post_thumbnail( $post_id, array( 60, 60 ) ); // Tamaño 60x60px
            } else {
                echo '—'; // Em dash si no hay logo
            }
            break;

        // Añadir más casos si se añaden más columnas
    }
}
// Enganchar a la acción específica para el CPT pos_account
add_action( 'manage_pos_account_posts_custom_column', 'streaming_display_account_column_content', 10, 2 );


/**
 * Añade el campo selector de perfiles al área de suscripción del POS.
 * Se engancha a 'pos_base_subscription_fields_content'.
 */
function streaming_add_profile_selector_field() {
    ?>
    <p class="form-field form-field-wide pos-streaming-profile-selector-wrap">
        <label for="pos-streaming-profile-select"><?php esc_html_e( 'Perfil Streaming:', 'pos-streaming' ); ?></label>
        <select id="pos-streaming-profile-select" name="_pos_assigned_profile_id" class="regular-text select2" disabled style="width: 100%;">
            <option value=""><?php esc_html_e( 'Cargando perfiles...', 'pos-streaming' ); ?></option>
            <?php // Las opciones se cargarán vía AJAX ?>
        </select>
        <span class="description"><?php esc_html_e( 'Selecciona el perfil disponible a asignar.', 'pos-streaming' ); ?></span>
        <span id="pos-streaming-profile-loading" style="display: none; vertical-align: middle; margin-left: 10px;" class="spinner is-active"></span>
        <span id="pos-streaming-profile-error" style="display: none; color: red; margin-left: 10px;"></span>
    </p>
    <?php
}
add_action( 'pos_base_subscription_fields_content', 'streaming_add_profile_selector_field' );


/**
 * Añade columnas personalizadas a la tabla de administración de Perfiles Streaming.
 *
 * @param array $columns Array de columnas existentes.
 * @return array Array de columnas modificado.
 */
function streaming_add_profile_columns( $columns ) {
    // Crear un nuevo array para controlar el orden
    $new_columns = array();

    // Mantener checkbox y título
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];

    // Añadir nuestras columnas personalizadas
    $new_columns['parent_account'] = __( 'Cuenta Padre', 'pos-streaming' );
    $new_columns['profile_status'] = __( 'Estado', 'pos-streaming' );

    // Mantener la columna de fecha (o quitarla si prefieres)
    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }
    // unset( $columns['date'] ); // Descomentar para quitar la fecha

    return $new_columns; // Devolver el nuevo array ordenado
}
// Enganchar al filtro específico para el CPT pos_profile
add_filter( 'manage_pos_profile_posts_columns', 'streaming_add_profile_columns' );


/**
 * Muestra el contenido para las columnas personalizadas en la tabla de Perfiles Streaming.
 *
 * @param string $column_name El nombre de la columna actual.
 * @param int    $post_id     El ID del post (perfil) actual.
 */
function streaming_display_profile_column_content( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'parent_account':
            $parent_account_id = get_post_meta( $post_id, '_pos_profile_parent_account_id', true );
            if ( $parent_account_id && $parent_account_id > 0 && get_post_type($parent_account_id) === 'pos_account' ) {
                $account_title = get_the_title( $parent_account_id );
                $account_edit_link = get_edit_post_link( $parent_account_id );
                if ( $account_edit_link ) {
                    echo '<a href="' . esc_url( $account_edit_link ) . '">' . esc_html( $account_title ?: sprintf( __( 'Cuenta ID %d', 'pos-streaming' ), $parent_account_id ) ) . '</a>';
                } else {
                    echo esc_html( $account_title ?: sprintf( __( 'Cuenta ID %d', 'pos-streaming' ), $parent_account_id ) );
                }
            } else {
                echo '<em>' . esc_html__( 'No asignada', 'pos-streaming' ) . '</em>';
            }
            break;

        case 'profile_status':
            $status_key = get_post_meta( $post_id, '_pos_profile_status', true );
            $status_label = '';
            // Definir los estados posibles (debe coincidir con los usados en el metabox)
            $possible_statuses = array(
                'available'    => __( 'Disponible', 'pos-streaming' ),
                'assigned'     => __( 'Asignado', 'pos-streaming' ),
                'maintenance'  => __( 'Mantenimiento', 'pos-streaming' ),
                'expired'      => __( 'Expirado', 'pos-streaming' ),
            );
            if ( isset( $possible_statuses[$status_key] ) ) {
                $status_label = $possible_statuses[$status_key];
                // Opcional: Añadir algún estilo visual
                $color = '#777'; // Color por defecto
                if ($status_key === 'available') $color = '#28a745'; // Verde
                if ($status_key === 'assigned') $color = '#ffc107'; // Amarillo/Naranja
                if ($status_key === 'expired') $color = '#dc3545'; // Rojo
                if ($status_key === 'maintenance') $color = '#17a2b8'; // Azul claro
                echo '<span style="color:' . $color . '; font-weight: bold;">' . esc_html( $status_label ) . '</span>';

            } else {
                echo '<em>' . esc_html__( 'No definido', 'pos-streaming' ) . '</em>';
            }
            break;

        // Añadir más casos si se añaden más columnas
    }
}
// Enganchar a la acción específica para el CPT pos_profile
add_action( 'manage_pos_profile_posts_custom_column', 'streaming_display_profile_column_content', 10, 2 );


?>
