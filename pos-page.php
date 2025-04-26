<?php
/**
 * Funciones para crear la página de administración del POS Streaming.
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );

/**
 * Añade la página del menú para el POS Streaming en el admin de WordPress.
 */
function pos_streaming_add_admin_menu() {
    $hook_suffix = add_menu_page(
        __( 'POS Streaming', 'pos-streaming' ),
        __( 'POS Streaming', 'pos-streaming' ),
        'manage_woocommerce', // Capacidad requerida
        'pos-streaming',      // Slug del menú
        'pos_streaming_render_page', // Función que renderiza la página
        'dashicons-store',    // Icono del menú
        58                    // Posición en el menú
    );

    // (Opcional) Podrías usar $hook_suffix para encolar scripts específicamente para esta página,
    // pero ya lo estamos haciendo en pos_streaming_enqueue_assets comprobando el hook.
}
add_action( 'admin_menu', 'pos_streaming_add_admin_menu' );

/**
 * Renderiza el contenido HTML de la página del POS Streaming.
 * Incluye sección para aplicar cupones, campo de teléfono y modal para cliente con campos adicionales y gestión de avatar.
 */
function pos_streaming_render_page() {
    // Comprobación de seguridad
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'pos-streaming' ) );
    }

    ?>
    <div class="wrap" id="pos-streaming-app-wrapper">
        <!-- <h1><?php echo esc_html__( 'POS Streaming - Punto de Venta', 'pos-streaming' ); ?></h1> -->

        <div id="pos-main-layout">

            <!-- Columna Izquierda: Productos y Calendario -->
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

                <div id="pos-calendar-area">
                    <h2><?php esc_html_e( 'Calendario Vencimientos', 'pos-streaming' ); ?></h2>
                    <div class="pos-section-content">
                        <div id='pos-calendar'></div>
                    </div>
                </div>
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
                            <a href="#TB_inline?width=400&height=320&inlineId=pos-customer-modal-content"
                               id="pos-add-new-customer-btn"
                               class="button thickbox"
                               title="<?php esc_attr_e( 'Añadir Nuevo Cliente', 'pos-streaming' ); ?>">
                                <?php esc_html_e( 'Añadir Nuevo Cliente', 'pos-streaming' ); ?>
                            </a>
                        </div>

                        <!-- Contenido del Modal (oculto) -->
                        <div id="pos-customer-modal-content" style="display:none;">
                            <div id="pos-customer-details">
                                <!-- <h3 id="pos-customer-form-title"><?php esc_html_e( 'Nuevo Cliente', 'pos-streaming' ); ?></h3> -->
                                <input type="hidden" id="pos-customer-id" value="">
                                <input type="hidden" id="pos-customer-avatar-id" value="">

                                <!-- === NUEVO: Contenedor Flex para layout === -->
                                <div class="pos-customer-modal-layout">

                                    <!-- Columna Izquierda: Avatar -->
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
                                    <!-- Fin Columna Izquierda -->

                                    <!-- === NUEVO: Contenedor Grid para campos === -->
                                    <!-- Columna Derecha: Campos -->
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
                                    </div>
                                    <!-- Fin Columna Derecha -->
                                    <!-- ======================================= -->

                                </div> <!-- Fin .pos-customer-modal-layout -->
                                <!-- ======================================= -->


                                <!-- Botones y Feedback quedan fuera del layout flex/grid -->
                                <p class="submit">
                                    <button type="button" id="pos-save-customer-btn" class="button button-primary"><?php esc_html_e( 'Guardar Cliente', 'pos-streaming' ); ?></button>
                                    <button type="button" id="pos-cancel-customer-btn" class="button button-secondary"><?php esc_html_e( 'Cancelar', 'pos-streaming' ); ?></button>
                                </p>
                                <div id="pos-customer-form-feedback" style="display: none; margin-top: 10px;"></div>

                            </div> <!-- Fin #pos-customer-details -->
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
                                <input type="color" id="pos-subscription-color" name="pos_subscription_color" value="#3a87ad"> <!-- Default color -->
                            </p>
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
                        <button id="pos-complete-sale-button" class="button button-primary button-large" disabled>
                            <?php esc_html_e( 'Completar Venta', 'pos-streaming' ); ?>
                        </button>
                    </div>
                </div>
            </div> <!-- /#pos-right-column -->

        </div> <!-- /#pos-main-layout -->

    </div> <!-- /.wrap #pos-streaming-app-wrapper -->
    <?php
}
?>