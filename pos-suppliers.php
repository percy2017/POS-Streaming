<?php
/**
 * Clase para mostrar la tabla de Cuentas (Proveedores) usando WP_List_Table.
 * Se muestra en la página personalizada 'Proveedores'.
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );

// Asegurarse de que WP_List_Table está disponible
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class POS_Accounts_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Cuenta POS', 'pos-streaming' ),
            'plural'   => __( 'Cuentas POS', 'pos-streaming' ),
            'ajax'     => false
        ) );
    }

    /**
     * Define las columnas de la tabla.
     * AÑADIDO: Columna Fecha Vencimiento.
     *
     * @return array Un array asociativo de columnas. Clave => Título.
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'title'         => __( 'Nombre Cuenta', 'pos-streaming' ),
            'service_type'  => __( 'Servicio', 'pos-streaming' ),
            'total_profiles'=> __( 'Perfiles Totales', 'pos-streaming' ),
            'available_profiles' => __( 'Perfiles Libres', 'pos-streaming' ),
            'status'        => __( 'Estado Cuenta', 'pos-streaming' ),
            'expiry_date'   => __( 'Vencimiento Cuenta', 'pos-streaming' ), // <-- NUEVA COLUMNA
            'date'          => __( 'Fecha Creación', 'pos-streaming' ),
        );
        return $columns;
    }

    /**
     * Define qué columnas son ordenables.
     * AÑADIDO: Ordenación por fecha de vencimiento.
     *
     * @return array Array de columnas ordenables.
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'title'         => array( 'title', false ),
            // Ordenar por meta requiere lógica adicional en prepare_items
            // 'service_type'  => array( 'service_type', false ),
            // 'status'        => array( 'status', false ),
            'expiry_date'   => array( 'expiry_date', false ), // <-- ORDENABLE (por meta)
            'date'          => array( 'date', true ),
        );
        return $sortable_columns;
    }

    /**
     * Prepara los items para mostrar en la tabla.
     * MODIFICADO: Añade lógica para ordenar por metadatos si es necesario.
     */
    public function prepare_items() {
        // Definir columnas
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        // El cuarto parámetro es la columna primaria (la que tiene las acciones)
        $this->_column_headers = array( $columns, $hidden, $sortable, 'title' );

        // Procesar acciones masivas ANTES de la consulta
        $this->process_bulk_action();

        // --- Paginación y Ordenación ---
        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $orderby      = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';
        $order        = isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'DESC';

        // --- Argumentos para WP_Query ---
        $args = array(
            'post_type'      => 'pos_account',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            // Por defecto, ordenar por fecha de publicación
            'orderby'        => 'date',
            'order'          => $order,
        );

        // --- Lógica de Ordenación por Columnas ---
        $valid_orderby_keys = array_keys( $this->get_sortable_columns() );
        if ( in_array( $orderby, $valid_orderby_keys ) ) {
            switch ( $orderby ) {
                case 'title':
                case 'date':
                    // WP_Query maneja 'title' y 'date' directamente
                    $args['orderby'] = $orderby;
                    break;
                case 'expiry_date':
                    // Ordenar por metadato numérico (fecha en formato YYYY-MM-DD)
                    $args['orderby'] = 'meta_value'; // O 'meta_value_num' si guardaras timestamp
                    $args['meta_key'] = '_pos_account_expiry_date';
                    // Podríamos necesitar 'meta_type' => 'DATE' si 'meta_value' no funciona bien
                    break;
                // Añadir casos para 'service_type', 'status' si implementas ordenación por meta
                // case 'status':
                //     $args['orderby'] = 'meta_value';
                //     $args['meta_key'] = '_pos_account_status';
                //     break;
            }
        } else {
             // Si no es una columna válida, volver a ordenar por fecha
             $args['orderby'] = 'date';
        }
        // Asegurar que el orden (ASC/DESC) se aplique
        $args['order'] = $order;


        // --- Realizar la consulta ---
        // error_log("DEBUG TABLE ACC: WP_Query Args: " . print_r($args, true)); // Descomentar si sigue fallando
        $query = new WP_Query( $args );

        // --- Asignar datos y paginación ---
        $this->items = $query->get_posts();
        $total_items = $query->found_posts;

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages
        ) );
    }

    /**
     * Define cómo mostrar el contenido de la columna 'cb' (checkbox).
     * (Sin cambios)
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="account_ids[]" value="%s" />', $item->ID
        );
    }

    /**
     * Define cómo mostrar el contenido de la columna 'title'.
     * CORREGIDO: Usa get_delete_post_link() para el enlace de borrado.
     *
     * @param WP_Post $item El objeto del post actual.
     * @return string HTML del título y acciones.
     */
    public function column_title( $item ) {
        $edit_link = get_edit_post_link( $item->ID );
        // Usar get_delete_post_link() para el enlace correcto con nonce
        // El tercer parámetro 'true' fuerza borrado permanente. Omitir para mover a papelera.
        $delete_link = get_delete_post_link( $item->ID, '', true );

        $title = _draft_or_post_title( $item );

        $actions = array(
            'edit'      => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Editar', 'pos-streaming' ) ),
            'delete'    => sprintf( '<a href="%s" onclick="return confirm(\'%s\')" style="color:#a00;">%s</a>',
                            esc_url( $delete_link ), // Usar el link generado por WP
                            esc_js( sprintf( __( '¿Estás seguro de que quieres eliminar "%s" permanentemente?', 'pos-streaming' ), $title ) ),
                            __( 'Eliminar Permanentemente', 'pos-streaming' )
                        ),
        );

        return sprintf( '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            esc_url( $edit_link ),
            esc_html( $title ),
            $this->row_actions( $actions )
        );
    }

     /**
      * Define cómo mostrar el contenido de columnas personalizadas.
      * CON LOGS DE DEPURACIÓN.
      *
      * @param WP_Post $item El objeto del post actual.
      * @param string $column_name El nombre de la columna.
      * @return string Contenido de la celda.
      */
     public function column_default( $item, $column_name ) {
         $post_id = $item->ID; // ID del post 'pos_account'
         // Log inicial para cada celda que no sea 'cb' o 'title'
         error_log("DEBUG TABLE ACC [{$post_id}]: Intentando renderizar columna '{$column_name}'");
 
         switch ( $column_name ) {
             case 'service_type':
                 $service = get_post_meta( $post_id, '_pos_service_type', true );
                 error_log("DEBUG TABLE ACC [{$post_id}]: get_post_meta('_pos_service_type') devolvió: " . print_r($service, true));
                 return $service ? esc_html( ucfirst( $service ) ) : 'N/A';
 
             case 'total_profiles':
                 $total = get_post_meta( $post_id, '_pos_total_profiles', true );
                 error_log("DEBUG TABLE ACC [{$post_id}]: get_post_meta('_pos_total_profiles') devolvió: " . print_r($total, true));
                 return ($total === '0' || $total > 0) ? absint($total) : 'N/A';
 
             case 'available_profiles':
                 $total_p = get_post_meta( $post_id, '_pos_total_profiles', true );
                 if (!is_numeric($total_p) || $total_p <= 0) {
                      error_log("DEBUG TABLE ACC [{$post_id}]: available_profiles = N/A (total no válido)");
                      return 'N/A';
                 }
                 $args_profiles = array( /* ... query args ... */
                     'post_type' => 'pos_profile', 'post_status' => 'any', 'posts_per_page' => -1,
                     'meta_query' => array( 'relation' => 'AND',
                         array( 'key' => '_pos_parent_account_id', 'value' => $post_id, 'compare' => '=' ),
                         array( 'key' => '_pos_profile_status', 'value' => 'available', 'compare' => '=' ),
                     ), 'fields' => 'ids',
                 );
                 $available_query = new WP_Query($args_profiles);
                 $count = $available_query->found_posts;
                 error_log("DEBUG TABLE ACC [{$post_id}]: available_profiles = {$count}");
                 return $count;
 
             case 'status':
                 $status = get_post_meta( $post_id, '_pos_account_status', true );
                 error_log("DEBUG TABLE ACC [{$post_id}]: get_post_meta('_pos_account_status') devolvió: " . print_r($status, true));
                 return $status ? esc_html( ucfirst( $status ) ) : __('Activa', 'pos-streaming');
 
             case 'expiry_date':
                 $expiry_date = get_post_meta( $post_id, '_pos_account_expiry_date', true );
                 error_log("DEBUG TABLE ACC [{$post_id}]: get_post_meta('_pos_account_expiry_date') devolvió: " . print_r($expiry_date, true));
                 if ( $expiry_date && preg_match("/^\d{4}-\d{2}-\d{2}$/", $expiry_date) ) {
                     try {
                          $date_obj = new DateTime($expiry_date);
                          return $date_obj->format(get_option('date_format'));
                     } catch (Exception $e) {
                          return esc_html($expiry_date);
                     }
                 }
                 return 'N/A';
 
             case 'date': // Columna estándar de fecha de creación
                  $date_val = mysql2date( get_option( 'date_format' ), $item->post_date );
                  // error_log("DEBUG TABLE ACC [{$post_id}]: date = {$date_val}"); // Log opcional para fecha
                  return $date_val;
 
             default:
                 // No debería llegar aquí si get_columns está bien definido
                 error_log("DEBUG TABLE ACC [{$post_id}]: Columna default no manejada: '{$column_name}'");
                 return '';
         }
     }
 

    /**
     * Define las acciones masivas.
     * (Sin cambios)
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk_delete' => __( 'Eliminar Permanentemente', 'pos-streaming' )
        );
        return $actions;
    }

    /**
     * Procesa las acciones masivas.
     * (Sin cambios, ya usa wp_delete_post)
     */
    public function process_bulk_action() {
        $action = $this->current_action();

        if ( 'bulk_delete' === $action ) {
            $ids = isset( $_REQUEST['account_ids'] ) ? array_map( 'absint', $_REQUEST['account_ids'] ) : array();
            if ( empty( $ids ) ) return;
            check_admin_referer( 'bulk-' . $this->_args['plural'] );

            $deleted_count = 0;
            foreach ( $ids as $id ) {
                if ( current_user_can( 'delete_post', $id ) ) {
                    if ( wp_delete_post( $id, true ) ) { // true = borrar permanentemente
                        $deleted_count++;
                        // TODO: Borrar perfiles hijos asociados a esta cuenta
                        // $this->delete_child_profiles($id);
                    }
                }
            }

            if ( $deleted_count > 0 ) {
                add_settings_error(
                    'pos_accounts_messages',
                    'accounts_deleted',
                    sprintf( _n( '%d cuenta eliminada permanentemente.', '%d cuentas eliminadas permanentemente.', $deleted_count, 'pos-streaming' ), $deleted_count ),
                    'updated'
                );
            }
        }
    }

    /**
     * Mensaje a mostrar cuando no hay items.
     * (Sin cambios)
     */
    public function no_items() {
        _e( 'No se encontraron cuentas de proveedores.', 'pos-streaming' );
    }

    /**
     * (Opcional) Función auxiliar para borrar perfiles hijos.
     * Habría que llamarla desde process_bulk_action.
     */
    // private function delete_child_profiles( $account_id ) {
    //     $args = array(
    //         'post_type' => 'pos_profile',
    //         'post_status' => 'any',
    //         'posts_per_page' => -1,
    //         'meta_query' => array(
    //             array(
    //                 'key' => '_pos_parent_account_id',
    //                 'value' => $account_id,
    //                 'compare' => '=',
    //             ),
    //         ),
    //         'fields' => 'ids', // Obtener solo IDs
    //     );
    //     $profile_ids = get_posts( $args );
    //     if ( ! empty( $profile_ids ) ) {
    //         foreach ( $profile_ids as $profile_id ) {
    //             wp_delete_post( $profile_id, true ); // Borrar permanentemente
    //         }
    //     }
    // }

} // Fin de la clase POS_Accounts_List_Table
?>
