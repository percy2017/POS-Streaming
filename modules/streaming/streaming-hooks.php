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
    $new_columns['associated_profiles'] = __( 'Perfiles Asociados', 'pos-streaming' ); // Nueva columna
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

        case 'associated_profiles':
            $args = array(
                'post_type'      => 'pos_profile',
                'post_status'    => 'any', // Considerar todos los estados
                'posts_per_page' => -1,
                'meta_key'       => '_pos_profile_parent_account_id',
                'meta_value'     => $post_id, // ID de la cuenta actual
                'orderby'        => 'title',
                'order'          => 'ASC',
            );
            $profiles_query = new WP_Query( $args );
            if ( $profiles_query->have_posts() ) {
                $profile_links = array();
                while ( $profiles_query->have_posts() ) {
                    $profiles_query->the_post();
                    $profile_id = get_the_ID();
                    $profile_title = get_the_title();
                    $edit_link = get_edit_post_link( $profile_id );
                    if ($edit_link) {
                        $profile_links[] = '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $profile_title ) . '</a>';
                    } else {
                        $profile_links[] = esc_html( $profile_title );
                    }
                }
                echo implode( '<br>', $profile_links ); // Mostrar uno por línea
                wp_reset_postdata();
            } else { echo '—'; } // Mostrar guión si no hay perfiles
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
 * Hace que la columna 'Vencimiento' sea ordenable en la tabla de Cuentas Streaming.
 *
 * @param array $sortable_columns Array de columnas ordenables existentes.
 * @return array Array modificado con la columna 'expiry_date'.
 */
function streaming_make_account_columns_sortable( $sortable_columns ) {
    $sortable_columns['expiry_date'] = 'expiry_date'; // 'expiry_date' es el identificador que usaremos en la query
    return $sortable_columns;
}
add_filter( 'manage_edit-pos_account_sortable_columns', 'streaming_make_account_columns_sortable' );

/**
 * Modifica la consulta principal para ordenar por fecha de vencimiento cuando se solicita.
 *
 * @param WP_Query $query La consulta principal de WordPress.
 */
function streaming_sort_accounts_by_expiry_date( $query ) {
    // Solo modificar la consulta en el admin, para el CPT correcto y si no es la consulta principal
    if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'pos_account' ) {
        return;
    }

    // Verificar si se está ordenando por nuestra columna 'expiry_date'
    if ( $query->get( 'orderby' ) === 'expiry_date' ) {
        $query->set( 'meta_key', '_pos_account_expiry_date' ); // El meta key que contiene la fecha
        // Usar 'meta_value_date' para una ordenación de fechas más precisa (requiere formato YYYY-MM-DD)
        $query->set( 'orderby', 'meta_value_date' );
        // $query->set( 'orderby', 'meta_value' ); // Fallback a ordenación de texto si 'meta_value_date' falla
    }
}
add_action( 'pre_get_posts', 'streaming_sort_accounts_by_expiry_date' );


/**
 * Añade el campo selector de perfiles al área de suscripción del POS.
 * Se engancha a 'pos_base_subscription_fields_content'.
 */
function streaming_add_profile_selector_field() {
    $active_modules = get_option( 'pos_base_active_modules', [] ); // Obtener array, o vacío si no existe

    // Verificar si 'streaming' está en el array de módulos activos
    if ( ! is_array( $active_modules ) || ! in_array( 'streaming', $active_modules, true ) ) {
        // Si el módulo NO está activo, no hacer nada y salir de la función
        return;
    }
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

/**
 * Añade un enlace "Volver a la lista" en el cuadro de publicación para Cuentas Streaming.
 *
 * @param WP_Post $post El objeto del post actual.
 */
function streaming_add_back_to_list_button_in_publish_box( $post ) {
    // Verificar si estamos en el tipo de post 'pos_account'
    if ( 'pos_account' === $post->post_type ) {
        $list_url = admin_url( 'edit.php?post_type=pos_account' );
        ?>
        <div class="misc-pub-section misc-pub-back-to-list">
            <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 5px;"></span>
            <a href="<?php echo esc_url( $list_url ); ?>" class="button button-small">
                <?php esc_html_e( 'Volver a Cuentas', 'pos-streaming' ); ?>
            </a>
        </div>
        <?php
    }
}
// Enganchar a la acción que se ejecuta dentro del cuadro de publicación
add_action( 'post_submitbox_misc_actions', 'streaming_add_back_to_list_button_in_publish_box' );

/**
 * Encola scripts específicos para el admin del módulo Streaming.
 *
 * @param string $hook_suffix El sufijo del hook de la página actual.
 */
function streaming_enqueue_admin_scripts( $hook_suffix ) {
    global $pagenow, $typenow;

    // Verificar si estamos en la página de listado de Cuentas Streaming
    if ( 'edit.php' === $pagenow && 'pos_account' === $typenow ) {
        // Obtener la URL base del módulo
        // Asume estructura /modules/streaming/ desde el archivo principal del plugin
        $module_url = plugin_dir_url( dirname( __FILE__, 2 ) ) . 'modules/streaming/';

        wp_enqueue_script(
            'streaming-admin-script', // Handle único
            $module_url . 'assets/js/streaming-app.js', // Ruta al archivo JS (Usamos streaming-app.js como acordamos)
            array( 'jquery' ), // Dependencia de jQuery
            defined('POS_BASE_VERSION') ? POS_BASE_VERSION : '1.0.0', // Versión
            true // Cargar en el footer
        );
    }
}
add_action( 'admin_enqueue_scripts', 'streaming_enqueue_admin_scripts' );

/**
 * Añade clases CSS al body en el área de administración para el CPT pos_account.
 * Esto ayuda a que el JavaScript pueda identificar la página correcta.
 *
 * @param string $classes Clases existentes del body.
 * @return string Clases modificadas del body.
 */
function streaming_add_admin_body_classes( $classes ) {
    global $pagenow, $typenow;

    // Añadir clases en la página de listado de Cuentas
    if ( 'edit.php' === $pagenow && 'pos_account' === $typenow ) {
        $classes .= ' post-type-pos_account edit-php'; // Añadir las clases necesarias
    } // Añadir clases en la página de listado de Perfiles
    elseif ( 'edit.php' === $pagenow && 'pos_profile' === $typenow ) {
        $classes .= ' post-type-pos_profile edit-php'; // Corregido: Añadir la clase correcta para perfiles
    }
    // Podríamos añadir más condiciones para otras páginas si fuera necesario

    return $classes;
}
add_filter( 'admin_body_class', 'streaming_add_admin_body_classes' );
