<?php
/**
 * Funciones para crear la página de administración del POS Streaming.
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );

/**
 * Añade la página del menú para el POS Streaming y su submenú principal.
 */
function pos_streaming_add_admin_menu() {

    add_menu_page(
        __( 'POS Streaming', 'pos-streaming' ),
        __( 'POS Streaming', 'pos-streaming' ),
        'manage_woocommerce',
        'pos-streaming',      // Slug del menú principal
        'pos_streaming_render_page', // Función que renderiza la página principal
        'dashicons-store',
        58
    );
    add_submenu_page(
        'pos-streaming',          // Slug del menú padre
        __( 'Punto de Venta', 'pos-streaming' ), // Título de la página
        __( 'POS', 'pos-streaming' ), // Título del submenú
        'manage_woocommerce',     // Capacidad
        'pos-streaming',          // <-- MISMO SLUG que add_menu_page
        'pos_streaming_render_page' // <-- MISMA FUNCIÓN callback que add_menu_page
    );

    // **NUEVO:** Submenú: Configuración
    add_submenu_page(
        'pos-streaming',                        // Slug del menú padre
        __( 'Configuración POS', 'pos-base' ),  // Título de la página
        __( 'Configuración', 'pos-base' ),      // Título del submenú
        'manage_options',                       // Capacidad (normalmente admin para ajustes)
        'pos-streaming-settings',               // Slug ÚNICO para esta página
        'pos_streaming_render_settings_page'    // NUEVA función callback para renderizarla
    );
}
add_action( 'admin_menu', 'pos_streaming_add_admin_menu' );




/**
 * Renderiza el contenido HTML de la página del POS Streaming.
 * Incluye estructura de pestañas (tabs).
 */
function pos_streaming_render_page() {
    // Comprobación de seguridad
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'pos-streaming' ) );
    }

    ?>
    <div class="wrap" id="pos-streaming-app-wrapper">
        <!-- <h1><?php // echo esc_html__( 'POS Streaming - Punto de Venta', 'pos-streaming' ); ?></h1> -->

        <!-- Pestañas de Navegación -->
        <h2 class="nav-tab-wrapper">
            <a href="#pos-tab-pos" class="nav-tab nav-tab-active" data-tab="pos"><?php esc_html_e( 'POS', 'pos-streaming' ); ?></a>
            <a href="#pos-tab-calendar" class="nav-tab" data-tab="calendar"><?php esc_html_e( 'Calendario', 'pos-streaming' ); ?></a>
            <a href="#pos-tab-sales" class="nav-tab" data-tab="sales"><?php esc_html_e( 'Ventas', 'pos-streaming' ); ?></a>
        </h2>

        <!-- Contenido de las Pestañas -->
        <div id="pos-tab-content-wrapper">

            <!-- Pestaña POS -->
            <div id="pos-tab-pos" class="pos-tab-content active">
                <div id="pos-main-layout">
                    <!-- Columna Izquierda: Productos -->
                    <div id="pos-left-column">
                        <div id="pos-products-area">
                            <h2><?php esc_html_e( 'Productos Streaming', 'pos-streaming' ); ?></h2>
                            <div class="pos-section-content">
                                <input type="search" id="pos-product-search" placeholder="<?php esc_attr_e( 'Buscar producto por nombre o SKU...', 'pos-streaming' ); ?>">
                                <div id="pos-product-list">
                                    <p><?php esc_html_e( 'Cargando productos...', 'pos-streaming' ); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- El calendario se movió a su propia pestaña -->
                    </div> <!-- /#pos-left-column -->

                    <!-- Columna Derecha: Cliente, Carrito y Pago -->
                    <div id="pos-right-column">
                        <div id="pos-customer-area">
                            <h2><?php esc_html_e( 'Cliente', 'pos-streaming' ); ?></h2>
                            <div class="pos-section-content">
                                <!-- Búsqueda/Selección de Cliente -->
                                <div class="pos-customer-search">
                                    <input type="search" id="pos-customer-search-input" placeholder="<?php esc_attr_e( 'Buscar cliente por nombre, email o teléfono...', 'pos-streaming' ); ?>">
                                    <div id="pos-customer-search-results"></div>
                                    <a href="#TB_inline?width=400&height=400&inlineId=pos-customer-modal-content"
                                       id="pos-add-new-customer-btn"
                                       class="button thickbox"
                                       title="<?php esc_attr_e( 'Añadir Nuevo Cliente', 'pos-streaming' ); ?>">
                                        <?php esc_html_e( 'Añadir Nuevo Cliente', 'pos-streaming' ); ?>
                                    </a>
                                </div>

                                <!-- Contenido del Modal (oculto) -->
                                <div id="pos-customer-modal-content" style="display:none;">
                                    <div id="pos-customer-details">
                                        <input type="hidden" id="pos-customer-id" value="">
                                        <input type="hidden" id="pos-customer-avatar-id" value="">
                                        <div class="pos-customer-modal-layout">
                                            <div id="pos-customer-avatar-section">
                                                <img id="pos-customer-avatar-preview" src="<?php echo esc_url( get_avatar_url( 0, ['size' => 96, 'default' => 'mystery'] ) ); ?>" alt="<?php esc_attr_e( 'Avatar del cliente', 'pos-streaming' ); ?>" width="96" height="96">
                                                <br>
                                                <button type="button" id="pos-change-avatar-btn" class="button button-small">
                                                    <?php esc_html_e( 'Cambiar Imagen', 'pos-streaming' ); ?>
                                                </button>
                                                <button type="button" id="pos-remove-avatar-btn" class="button-link button-link-delete" style="display: none;">
                                                    <?php esc_html_e( 'Quitar Imagen', 'pos-streaming' ); ?>
                                                </button>
                                            </div>
                                            <div id="pos-customer-fields-wrapper">
                                                <p>
                                                    <label for="pos-customer-first-name"><?php esc_html_e( 'Nombre:', 'pos-streaming' ); ?></label>
                                                    <input type="text" id="pos-customer-first-name" name="pos_customer_first_name" required class="regular-text">
                                                </p>
                                                <p>
                                                    <label for="pos-customer-last-name"><?php esc_html_e( 'Apellido:', 'pos-streaming' ); ?></label>
                                                    <input type="text" id="pos-customer-last-name" name="pos_customer_last_name" class="regular-text">
                                                </p>
                                                <p>
                                                    <label for="pos-customer-email"><?php esc_html_e( 'Email:', 'pos-streaming' ); ?></label>
                                                    <input type="email" id="pos-customer-email" name="pos_customer_email" class="regular-text">
                                                </p>
                                                <p>
                                                    <label for="pos-customer-phone"><?php esc_html_e( 'Teléfono:', 'pos-streaming' ); ?></label>
                                                    <input type="tel" id="pos-customer-phone" name="pos_customer_phone" class="regular-text">
                                                </p>
                                                <p style="grid-column: 1 / -1;"> <?php // Ocupa todo el ancho ?>
                                                    <label for="pos-customer-note"><?php esc_html_e( 'Nota del Cliente:', 'pos-streaming' ); ?></label>
                                                    <textarea id="pos-customer-note" name="pos_customer_note" rows="4" class="large-text"></textarea>
                                                </p>
                                            </div>
                                        </div>
                                        <p class="submit">
                                            <button type="button" id="pos-save-customer-btn" class="button button-primary"><?php esc_html_e( 'Guardar Cliente', 'pos-streaming' ); ?></button>
                                            <button type="button" id="pos-cancel-customer-btn" class="button button-secondary"><?php esc_html_e( 'Cancelar', 'pos-streaming' ); ?></button>
                                        </p>
                                        <div id="pos-customer-form-feedback" style="display: none; margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <!-- Fin Contenido del Modal -->

                                <div id="pos-selected-customer-info" style="display: none;">
                                     <p>
                                        <img id="selected-customer-avatar" src="<?php echo esc_url( get_avatar_url( 0, ['size' => 32, 'default' => 'mystery'] ) ); ?>" width="32" height="32" style="vertical-align: middle; margin-right: 5px; border-radius: 50%;">
                                        <strong><?php esc_html_e( 'Cliente:', 'pos-streaming' ); ?></strong> <span id="selected-customer-name"></span>
                                     </p>
                                     <button id="pos-edit-customer-btn" class="button button-small"><?php esc_html_e( 'Editar', 'pos-streaming' ); ?></button>
                                     <button id="pos-change-customer-btn" class="button button-small"><?php esc_html_e( 'Cambiar', 'pos-streaming' ); ?></button>
                                </div>
                            </div>
                        </div>

                        <div id="pos-cart-area">
                            <h2><?php esc_html_e( 'Carrito', 'pos-streaming' ); ?></h2>
                            <div class="pos-section-content">
                                <ul id="pos-cart-items">
                                    <li class="empty-cart"><?php esc_html_e( 'El carrito está vacío.', 'pos-streaming' ); ?></li>
                                </ul>
                                <div id="pos-cart-totals">
                                    <p><strong><?php esc_html_e( 'Subtotal:', 'pos-streaming' ); ?></strong> <span id="pos-cart-subtotal-amount">0.00</span></p>
                                    <p id="pos-cart-discount-row" style="display: none;">
                                        <strong><?php esc_html_e( 'Descuento:', 'pos-streaming' ); ?></strong> <span id="pos-cart-discount-amount">0.00</span>
                                    </p>
                                    <p><strong><?php esc_html_e( 'Total:', 'pos-streaming' ); ?></strong> <span id="pos-cart-total-amount">0.00</span></p>
                                </div>
                            </div>
                        </div>

                        <div id="pos-checkout-area">
                            <h2><?php esc_html_e( 'Pago', 'pos-streaming' ); ?></h2>
                            <div class="pos-section-content">
                                <div class="pos-sale-type-area">
                                    <label for="pos-sale-type"><?php esc_html_e( 'Tipo de Venta:', 'pos-streaming' ); ?></label>
                                    <select id="pos-sale-type" name="pos_sale_type">
                                        <option value="direct"><?php esc_html_e( 'Directo', 'pos-streaming' ); ?></option>
                                        <option value="subscription"><?php esc_html_e( 'Suscripción', 'pos-streaming' ); ?></option>
                                        <option value="credit"><?php esc_html_e( 'Crédito', 'pos-streaming' ); ?></option>
                                    </select>
                                </div>
                                <div id="pos-subscription-fields" style="display: none; border-top: 1px dashed #ddd; padding-top: 15px; margin-top: 15px;">
                                    <h4><?php esc_html_e( 'Detalles de Suscripción', 'pos-streaming' ); ?></h4>
                                    <p>
                                        <label for="pos-subscription-title"><?php esc_html_e( 'Título (Calendario):', 'pos-streaming' ); ?></label>
                                        <input type="text" id="pos-subscription-title" name="pos_subscription_title" class="regular-text" placeholder="<?php esc_attr_e( 'Ej: Netflix Juan Perez', 'pos-streaming' ); ?>">
                                    </p>
                                    <p>
                                        <label for="pos-subscription-expiry-date"><?php esc_html_e( 'Fecha Vencimiento:', 'pos-streaming' ); ?></label>
                                        <input type="date" id="pos-subscription-expiry-date" name="pos_subscription_expiry_date" class="regular-text">
                                    </p>
                                    <p>
                                        <label for="pos-subscription-color"><?php esc_html_e( 'Color Evento:', 'pos-streaming' ); ?></label>
                                        <input type="color" id="pos-subscription-color" name="pos_subscription_color" value="#3a87ad">
                                    </p>

                                    <?php // --- NUEVO SELECTOR DE PERFIL --- ?>
                                    <div id="pos-profile-selector-area" style="margin-top: 10px; padding-top: 10px; border-top: 1px dotted #eee;">
                                        <p>
                                            <label for="pos-profile-selector"><?php esc_html_e( 'Asignar Perfil Disponible:', 'pos-streaming' ); ?></label>
                                            <select id="pos-profile-selector" name="pos_assigned_profile_id">
                                                <option value=""><?php esc_html_e( 'Cargando / Seleccionar...', 'pos-streaming' ); ?></option>
                                                <?php // Las opciones se cargarán con JavaScript ?>
                                            </select>
                                            <span id="pos-profile-loading-indicator" style="display: none; margin-left: 10px;" class="spinner is-active"></span>
                                        </p>
                                        <p id="pos-profile-selector-feedback" style="color: #dc3545; font-style: italic;"></p>
                                    </div>
                                    <?php // --- FIN NUEVO SELECTOR --- ?>

                                </div>
                                <div class="pos-payment-method">
                                    <label for="pos-payment-method"><?php esc_html_e( 'Método de Pago:', 'pos-streaming' ); ?></label>
                                    <select id="pos-payment-method" name="pos_payment_method">
                                        <option value="" disabled selected><?php esc_html_e( 'Cargando...', 'pos-streaming' ); ?></option>
                                    </select>
                                </div>
                                <div class="pos-coupon-area">
                                    <label for="pos-coupon-code"><?php esc_html_e( 'Código de Cupón:', 'pos-streaming' ); ?></label>
                                    <div class="pos-coupon-input-group">
                                        <input type="text" id="pos-coupon-code" placeholder="<?php esc_attr_e( 'Ingresar cupón', 'pos-streaming' ); ?>">
                                        <button id="pos-apply-coupon-button" class="button button-secondary">
                                            <?php esc_html_e( 'Aplicar', 'pos-streaming' ); ?>
                                        </button>
                                    </div>
                                    <div id="pos-coupon-message" class="pos-coupon-feedback"></div>
                                </div>
                                <div class="pos-order-note-area">
                                    <label for="pos-order-note-input"><?php esc_html_e( 'Nota del Pedido:', 'pos-streaming' ); ?></label>
                                    <textarea id="pos-order-note-input" name="pos_order_note" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Añadir detalles específicos para esta venta...', 'pos-streaming' ); ?>"></textarea>
                                </div>
                                <button id="pos-complete-sale-button" class="button button-primary button-large" disabled>
                                    <?php esc_html_e( 'Completar Venta', 'pos-streaming' ); ?>
                                </button>
                            </div>
                        </div>
                    </div> <!-- /#pos-right-column -->
                </div> <!-- /#pos-main-layout -->
            </div> <!-- /#pos-tab-pos -->

            <!-- Pestaña Calendario -->
            <div id="pos-tab-calendar" class="pos-tab-content">
                <div id="pos-calendar-area">
                    <h2><?php esc_html_e( 'Calendario Vencimientos', 'pos-streaming' ); ?></h2>
                    <div class="pos-section-content">
                        <div id='pos-calendar'></div>
                    </div>
                </div>
            </div> <!-- /#pos-tab-calendar -->

            <!-- Pestaña Ventas -->
            <div id="pos-tab-sales" class="pos-tab-content">
                <h2><?php esc_html_e( 'Historial de Ventas', 'pos-streaming' ); ?></h2>
                <div class="pos-section-content">
                    <table id="pos-sales-datatable" class="display wp-list-table widefat fixed striped" style="width:100%">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Pedido #', 'pos-streaming' ); ?></th>
                                <th><?php esc_html_e( 'Fecha', 'pos-streaming' ); ?></th>
                                <th><?php esc_html_e( 'Cliente', 'pos-streaming' ); ?></th>
                                <th><?php esc_html_e( 'Total', 'pos-streaming' ); ?></th>
                                <th><?php esc_html_e( 'Tipo (POS)', 'pos-streaming' ); ?></th>
                                <th><?php esc_html_e( 'Notas', 'pos-streaming' ); ?></th>
                                <th><?php esc_html_e( 'Meta', 'pos-streaming' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php // El contenido será cargado por DataTables vía AJAX ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- /#pos-tab-sales -->

        </div> <!-- /#pos-tab-content-wrapper -->

    </div> <!-- /.wrap #pos-streaming-app-wrapper -->
    <?php
}

// **NUEVO:** Función para renderizar la página de Configuración
function pos_streaming_render_settings_page() {
    // Comprobación de seguridad - ¿Tiene el usuario permiso para gestionar opciones?
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'pos-base' ) );
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Configuración de POS Streaming', 'pos-base' ); ?></h1>

        <p><?php esc_html_e( 'Aquí podrás configurar los módulos y otras opciones del plugin POS Streaming.', 'pos-base' ); ?></p>

        <?php
        // --- Aquí irá el formulario de configuración usando la API de Ajustes de WordPress ---
        // Por ahora, solo un marcador de posición.
        ?>
        <form method="post" action="options.php">
            <?php
            // settings_fields( 'pos_streaming_options_group' ); // Nombre del grupo de opciones (definir más adelante)
            // do_settings_sections( 'pos-streaming-settings' ); // Slug de la página de ajustes
            // submit_button();
            ?>
            <p><em><?php esc_html_e( 'Próximamente: Opciones de configuración de módulos (Streaming, Hotel, etc.).', 'pos-base' ); ?></em></p>
        </form>

    </div>
    <?php
}


/**
 * Renderiza el contenido HTML de la página de administración de Proveedores.
 */
function pos_streaming_render_providers_page() {
   
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'pos-streaming' ) );
    }

    $accounts_table = new POS_Accounts_List_Table();
    $accounts_table->process_bulk_action(); 

    ?>
    <div class="wrap" id="pos-streaming-providers-wrapper">
        <h1><?php echo esc_html__( 'Gestión de Proveedores (Cuentas)', 'pos-streaming' ); ?></h1>

        <?php settings_errors( 'pos_accounts_messages' ); ?>
        <p><?php esc_html_e( 'Aquí puedes ver y gestionar las cuentas principales de tus servicios (proveedores).', 'pos-streaming' ); ?></p>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=pos_account' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Añadir Nueva Cuenta', 'pos-streaming' ); ?>
        </a>
        <div id="pos-providers-content" style="margin-top: 15px;">
            <?php $accounts_table->prepare_items(); ?>
            <form method="post">
                <?php
                    wp_nonce_field( 'bulk-' . $accounts_table->_args['plural'] ); // Nonce para acciones masivas
                    if ( isset( $_REQUEST['page'] ) ) {
                        echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
                    }
                ?>
                <?php $accounts_table->display(); ?>
            </form>
        </div>
    </div>
    <?php
}

?>
