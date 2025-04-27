<?php
/**
 * Define los pasos para el tour guiado (wp-pointer) de POS Base.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Devuelve un array con la configuración de los pointers para el tour.
 *
 * @return array Array de pointers. Clave es ID único, valor es config.
 */
function pos_base_get_tour_pointers() {

    // --- Define aquí los pasos del tour ---
    $pointers = array(
        // Paso 1: Buscar Productos
        'pos_step_1' => array(
            'target'       => '#pos-product-search', // Selector CSS del input de búsqueda de productos
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%1$s</h3><p>%2$s</p>',
                    __( 'Paso 1: Buscar Productos', 'pos-base' ),
                    __( 'Empieza escribiendo el nombre o SKU de un producto aquí. Los resultados aparecerán debajo.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top',   // El tooltip aparecerá arriba del input
                    'align' => 'left', // Alineado a la izquierda del input
                ),
            ),
        ),

        // Paso 2: Añadir al Carrito (Señala el contenedor de productos)
        'pos_step_2' => array(
            'target'       => '#pos-product-list', // Selector CSS del contenedor donde aparecen los productos
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%1$s</h3><p>%2$s</p>',
                    __( 'Paso 2: Añadir al Carrito', 'pos-base' ),
                    __( 'Una vez encuentres el producto, haz clic en el botón "Añadir" para agregarlo al carrito en la columna derecha.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'right', // El tooltip aparecerá a la derecha del listado
                    'align' => 'middle',// Alineado al medio verticalmente
                ),
            ),
        ),

        // Paso 3: Seleccionar Cliente
        'pos_step_3' => array(
            'target'       => '#pos-customer-search-input', // Selector CSS del input de búsqueda de cliente
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%1$s</h3><p>%2$s</p>',
                    __( 'Paso 3: Seleccionar Cliente', 'pos-base' ),
                    __( 'Busca un cliente existente o haz clic en "Añadir Nuevo Cliente". La venta se asociará a él.', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
        ),

        // Paso 4: Completar Venta
        'pos_step_4' => array(
            'target'       => '#pos-complete-sale-button', // Selector CSS del botón final
            'options'      => array(
                'content'  => sprintf(
                    '<h3>%1$s</h3><p>%2$s</p>',
                    __( 'Paso 4: Completar Venta', 'pos-base' ),
                    __( 'Revisa el carrito, selecciona el tipo de venta y método de pago. ¡Luego haz clic aquí para finalizar!', 'pos-base' )
                ),
                'position' => array(
                    'edge'  => 'bottom', // El tooltip aparecerá debajo del botón
                    'align' => 'middle', // Alineado al centro horizontalmente
                ),
            ),
        ),
        // --- Añadir más pasos aquí si es necesario ---
    );

    // --- Añadir botones a cada paso ---
    foreach ( $pointers as $key => &$pointer ) {
        $pointer['options']['buttons'] = array(
            // Botón Siguiente/Finalizar (se ajustará en JS)
            array(
                'name'  => 'next', // Usaremos este nombre en JS
                'label' => __( 'Siguiente', 'pos-base' ),
                'class' => 'button-primary pos-tour-next-btn', // Clase para identificarlo
            ),
            // Botón Cerrar
            array(
                'name'  => 'close', // Usaremos este nombre en JS
                'label' => __( 'Cerrar Tour', 'pos-base' ),
                'class' => 'button-secondary pos-tour-close-btn', // Clase para identificarlo
            ),
        );
        // Asegurar que position existe
        if (!isset($pointer['options']['position'])) {
             $pointer['options']['position'] = array('edge' => 'top', 'align' => 'middle');
        }
    }
    unset($pointer); // Romper referencia

    return $pointers;
}

?>
