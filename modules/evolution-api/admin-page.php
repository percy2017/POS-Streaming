<?php
/**
 * admin-page.php
 *
 * Renderiza la página de administración para crear y gestionar
 * la instancia de Evolution API asociada a este plugin.
 * Incluye secciones para estado detallado, log de actividad y QR.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Renderiza el contenido HTML de la página de gestión de la instancia de Evolution API.
 * Esta es la función callback usada en add_submenu_page() en pos-evolution-api-module.php.
 *
 * MODIFICADO: Renderiza siempre ambos bloques de botones (Crear y Gestionar),
 * ocultando el bloque inapropiado con style="display: none;" para que JS pueda mostrarlos/ocultarlos.
 * MODIFICADO: Sección QR movida antes de la sección Log para mejor accesibilidad.
 */
function pos_evolution_api_render_instance_page() {
    // 1. Comprobación de seguridad: Permiso del usuario
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'pos-base' ) );
    }

    // 2. Obtener configuración y estado actual
    $settings = pos_evolution_api_get_settings(); // De settings.php
    $api_client = new Evolution_API_Client();     // De api-client.php
    $managed_instance_name = $settings['managed_instance_name'] ?? '';
    $is_configured = $api_client->is_configured(); // Verifica si URL y Token están presentes

    // Determinar qué sección de botones ocultar inicialmente
    $create_section_style = ! empty( $managed_instance_name ) ? 'style="display: none;"' : '';
    $manage_section_style = empty( $managed_instance_name ) ? 'style="display: none;"' : '';

    ?>
    <div class="wrap" id="pos-evolution-api-manager">
        <h1><?php echo esc_html__( 'Gestionar Instancia Evolution API', 'pos-base' ); ?></h1>

        <?php // 3. Mostrar advertencia si la API no está configurada ?>
        <?php if ( ! $is_configured ) : ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    printf(
                        /* Translators: %s is the link to the settings page. */
                        wp_kses_post( __( '<strong>Configuración requerida:</strong> Por favor, configura primero la <a href="%s">URL de la API y el Token</a> en la página de configuración general de POS Base para poder gestionar la instancia.', 'pos-base' ) ),
                        esc_url( admin_url( 'admin.php?page=pos-base-settings' ) )
                    );
                    ?>
                </p>
            </div>
        <?php else : ?>
            <?php // --- Contenido principal si la API está configurada --- ?>

            <!-- 4. Sección de Acciones -->
            <div id="instance-actions-section" style="margin-bottom: 20px; padding: 15px; background-color: #fff; border: 1px solid #ccd0d4;">
                <h2><?php esc_html_e( 'Acciones', 'pos-base' ); ?></h2>
                <div id="instance-actions-content">

                    <?php // --- Bloque para Crear Instancia (siempre presente en HTML) --- ?>
                    <div id="create-instance-section" <?php echo $create_section_style; // Ocultar si ya hay instancia ?>>
                        <?php error_log("[EVO_API_ADMIN_PAGE] Renderizando HTML para sección 'Crear Nueva Instancia'. Oculto inicialmente: " . (!empty($create_section_style) ? 'Sí' : 'No')); ?>
                        <p><?php esc_html_e( 'Aún no has creado una instancia de Evolution API gestionada por este plugin.', 'pos-base' ); ?></p>
                        <button type="button" id="create-instance-button" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Crear Nueva Instancia', 'pos-base' ); ?>
                        </button>
                        <p class="description"><?php esc_html_e( 'Esto creará una nueva instancia en tu servidor Evolution API y la vinculará a este plugin.', 'pos-base' ); ?></p>
                    </div>

                    <?php // --- Bloque para Gestionar Instancia (siempre presente en HTML) --- ?>
                    <div id="manage-instance-section" <?php echo $manage_section_style; // Ocultar si NO hay instancia ?>>
                         <?php error_log("[EVO_API_ADMIN_PAGE] Renderizando HTML para sección 'Gestionar Instancia'. Oculto inicialmente: " . (!empty($manage_section_style) ? 'Sí' : 'No')); ?>
                        <p>
                            <?php printf( esc_html__( 'Gestionando instancia: %s', 'pos-base' ), '<strong id="managed-instance-name-display">' . esc_html( $managed_instance_name ) . '</strong>' ); // Añadido ID para posible actualización JS ?>
                        </p>
                        <button type="button" id="get-qr-button" class="button button-secondary">
                            <span class="dashicons dashicons-camera" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Mostrar QR / Reconectar', 'pos-base' ); ?>
                        </button>
                        <button type="button" id="get-status-button" class="button button-secondary">
                            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Refrescar Estado', 'pos-base' ); ?>
                        </button>
                        <button type="button" id="disconnect-instance-button" class="button button-secondary">
                            <span class="dashicons dashicons-exit" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Desconectar Instancia', 'pos-base' ); ?>
                        </button>
                        <button type="button" id="delete-instance-button" class="button button-danger">
                            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Eliminar Instancia', 'pos-base' ); ?>
                        </button>
                    </div>

                </div> <?php // Fin de #instance-actions-content ?>
            </div> <?php // Fin de #instance-actions-section ?>

            <?php // Separador visual ?>
            <hr style="margin: 25px 0;">

            <!-- 5. Sección de Estado Actual (con detalles) -->
            <div id="instance-status-section" style="margin-bottom: 20px;">
                <h2><?php esc_html_e( 'Estado Actual de la Instancia', 'pos-base' ); ?></h2>
                <div id="instance-status-content" style="padding: 15px; background-color: #fff; border: 1px solid #ccd0d4; min-height: 50px;">
                    <?php // Mensaje inicial de carga o si no hay instancia (manejado por JS ahora) ?>
                    <p id="status-loading-message" <?php echo $manage_section_style; // Ocultar si no hay instancia ?>><span class="spinner is-active" style="float: left; margin-right: 5px;"></span><?php esc_html_e( 'Consultando estado...', 'pos-base' ); ?></p>
                    <p id="no-instance-message" <?php echo $create_section_style; // Ocultar si hay instancia ?>><?php esc_html_e( 'No hay ninguna instancia creada o gestionada por este plugin todavía.', 'pos-base' ); ?></p>

                    <?php // Contenedor para los detalles específicos (inicialmente oculto por JS o si no hay instancia) ?>
                    <div id="instance-details" style="display: none; margin-top: 10px; overflow: hidden; /* Clear float */" <?php // JS controlará el display de este div entero ?>>
                        <?php // --- Imagen de Perfil --- ?>
                        <img id="instance-profile-picture" src="#" alt="<?php esc_attr_e('Foto de perfil', 'pos-base'); ?>" style="display: none; max-width: 100px; height: auto; border-radius: 50%; float: left; margin-right: 15px; margin-bottom: 10px; border: 1px solid #eee;">

                        <?php // --- Detalles en Texto --- ?>
                        <div style="overflow: hidden;"> <?php // Wrapper para limpiar el float de la imagen ?>
                            <p style="margin: 5px 0;"><strong><?php esc_html_e('Estado:', 'pos-base'); ?></strong> <span id="instance-state-value">-</span></p>
                            <p style="margin: 5px 0;"><strong><?php esc_html_e('Nombre Dispositivo:', 'pos-base'); ?></strong> <span id="instance-pushname-value">-</span></p>
                            <p style="margin: 5px 0;"><strong><?php esc_html_e('Propietario (WID):', 'pos-base'); ?></strong> <span id="instance-owner-value">-</span></p> <?php // Aclaramos que Owner es el WID ?>
                        </div>
                    </div>

                    <?php // Área para mostrar mensajes generales de éxito/error/advertencia ?>
                    <div id="status-message-area" style="margin-top: 10px;">
                        <?php // Los mensajes se insertarán aquí vía JS ?>
                    </div>
                </div>
            </div>

            <!-- *** INICIO CAMBIO DE ORDEN *** -->
            <!-- 6. Sección del Código QR (oculta por defecto) -->
            <div id="qr-code-section" style="display: none; margin-top: 20px; text-align: center; padding: 20px; border: 1px solid #ccc; background: #f9f9f9;">
                <h2><?php esc_html_e( 'Escanear Código QR', 'pos-base' ); ?></h2>
                <div id="qr-code-container" style="min-height: 250px; display: flex; align-items: center; justify-content: center; background: white; padding: 10px; margin-bottom: 15px; border: 1px solid #eee;">
                    <?php // Placeholder mientras carga el QR ?>
                    <p id="qr-loading-message"><span class="spinner is-active" style="float: left; margin-right: 5px;"></span><?php esc_html_e( 'Generando código QR...', 'pos-base' ); ?></p>
                    <?php // Aquí se insertará la imagen del QR vía JS ?>
                </div>
                <p><small><?php esc_html_e( 'Abre WhatsApp en tu teléfono, ve a Ajustes > Dispositivos Vinculados > Vincular un dispositivo y escanea este código.', 'pos-base' ); ?></small></p>
                <button type="button" id="close-qr-button" class="button button-secondary" style="margin-top: 15px;"><?php esc_html_e( 'Ocultar QR', 'pos-base' ); ?></button>
            </div>

            <!-- 7. Sección Log de Actividad -->
            <div id="instance-log-section" style="margin-top: 25px;">
                <h2><?php esc_html_e( 'Log de Actividad Reciente', 'pos-base' ); ?></h2>
                <div style="max-height: 200px; overflow-y: auto; background: #f0f0f1; padding: 10px; border: 1px solid #ccd0d4; font-family: monospace; font-size: 12px;">
                    <ul id="instance-log-list" style="list-style: none; margin: 0; padding: 0;">
                        <li class="log-entry-placeholder"><?php esc_html_e('Inicializando log...', 'pos-base'); ?></li>
                    </ul>
                </div>
                 <button type="button" id="clear-log-button" class="button button-secondary button-small" style="margin-top: 5px;"><?php esc_html_e('Limpiar Log', 'pos-base'); ?></button>
            </div>
            <!-- *** FIN CAMBIO DE ORDEN *** -->

            <?php // 8. Inputs ocultos (menos críticos ahora, pero pueden dejarse) ?>
            <input type="hidden" id="managed-instance-name-input" value="<?php echo esc_attr( $managed_instance_name ); ?>">
            <input type="hidden" id="evolution-api-nonce" value="<?php echo esc_attr( wp_create_nonce( 'evolution_api_ajax_nonce' ) ); ?>">

        <?php endif; // Fin de if ($is_configured) ?>
    </div> <?php // Fin de .wrap ?>

    <?php
}


/**
 * Encola los scripts y estilos necesarios para la página de gestión de instancia.
 * También localiza los datos necesarios para el script JS.
 *
 * @param string $hook_suffix El sufijo del hook de la página actual.
 */
function pos_evolution_api_enqueue_manager_scripts( $hook_suffix ) {

    //1. Determinar el hook_suffix correcto para nuestra subpágina.
    // El hook se genera como: {hook_del_menu_padre}_page_{slug_del_submenu}
    $target_hook = 'pos-base_page_pos-evolution-api-instance'; // Ajustado al slug correcto

    if ( $hook_suffix !== $target_hook ) {
        // error_log("EVO_API_ENQUEUE: Hook no coincide. Actual: {$hook_suffix} | Esperado: {$target_hook}");
        return; // No cargar en otras páginas
    }
    // error_log("EVO_API_ENQUEUE: Hook coincide ({$hook_suffix}). Encolando scripts.");

    // 2. Encolar nuestro script principal
    $script_handle = 'pos-evolution-api-manager';
    $script_url = EVOLUTION_API_MODULE_URL . 'assets/js/instance-manager.js';
    $script_version = file_exists( EVOLUTION_API_MODULE_DIR . 'assets/js/instance-manager.js' ) ? filemtime( EVOLUTION_API_MODULE_DIR . 'assets/js/instance-manager.js' ) : '1.0.1'; // Incrementado versión
    $dependencies = ['jquery', 'pos-base-sweetalert2']; // Asegúrate que 'pos-base-sweetalert2' esté registrado y encolado por POS Base
    wp_enqueue_script( $script_handle, $script_url, $dependencies, $script_version, true ); // true para cargar en el footer

    // 3. Localizar datos para pasar a nuestro script
    $settings = pos_evolution_api_get_settings();
    $data_for_js = [
        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'evolution_api_ajax_nonce' ),
        'instanceName'  => $settings['managed_instance_name'] ?? '',
        'i18n'          => [ // Textos traducibles para usar en JS
            // Textos generales y de errores
            'qrAltText'             => __( 'Código QR de WhatsApp', 'pos-base' ),
            'successGeneric'        => __( 'Acción completada con éxito.', 'pos-base' ),
            'successTitle'          => __( 'Éxito', 'pos-base' ),
            'operationCompleted'    => __( 'La operación se completó.', 'pos-base' ),
            'errorUnknown'          => __( 'Ocurrió un error desconocido.', 'pos-base' ),
            'errorTitle'            => __( 'Error', 'pos-base' ),
            'errorNetwork'          => __( 'Error de red o de servidor:', 'pos-base' ),
            'errorConnection'       => __( 'Error de Conexión', 'pos-base' ),
            'cancelButtonText'      => __( 'Cancelar', 'pos-base' ),
            'qrLoading'             => __( 'Generando código QR...', 'pos-base' ),
            'qrObtained'            => __( 'Código QR obtenido.', 'pos-base' ),
            'errorNoQr'             => __( 'No se pudo obtener el código QR...', 'pos-base' ),
            'statusLoading'         => __( 'Cargando estado...', 'pos-base' ),
            'createTitle'           => __( 'Crear Nueva Instancia', 'pos-base' ),
            'instanceNameLabel'     => __( 'Nombre para la instancia', 'pos-base' ),
            'instanceNamePlaceholder' => __( 'Ej: tienda_principal (solo letras, números, -, _)', 'pos-base' ),
            'createButtonText'      => __( 'Crear y Obtener QR', 'pos-base' ),
            'errorNameRequired'     => __( '¡Necesitas escribir un nombre!', 'pos-base' ),
            'errorNameInvalid'      => __( 'Nombre inválido. Usa solo letras, números, guiones bajos o medios.', 'pos-base' ),
            'disconnectTitle'       => __( '¿Desconectar Instancia?', 'pos-base' ),
            'disconnectText'        => __( 'Esto cerrará la sesión de WhatsApp en el servidor, pero no eliminará la instancia.', 'pos-base' ),
            'disconnectConfirm'     => __( 'Sí, desconectar', 'pos-base' ),
            'deleteTitle'           => __( '¿Eliminar Instancia?', 'pos-base' ),
            'deleteText1'           => __( '¡Esta acción es irreversible! Se eliminará la instancia', 'pos-base' ),
            'deleteText2'           => __( 'del servidor Evolution API.', 'pos-base' ),
            'deleteConfirmPrompt'   => __( 'Escribe el nombre de la instancia para confirmar:', 'pos-base' ),
            'deleteConfirm'         => __( 'Sí, eliminarla', 'pos-base' ),
            'errorNameMismatch'     => __( 'El nombre no coincide. Escribe exactamente:', 'pos-base' ),
            'errorDeleteConnected'  => __( 'Debes desconectar la instancia antes de eliminarla.', 'pos-base' ),
            'statusRefreshed'       => __( 'Estado actualizado.', 'pos-base' ),
            'logCleared'            => __( 'Log limpiado.', 'pos-base' ),
            'logInitialized'        => __( 'Interfaz de gestión inicializada.', 'pos-base' ),
            'autoRefreshing'        => __( 'Actualizando estado automáticamente...', 'pos-base' ),
            'currentState'          => __( 'Estado actual:', 'pos-base' ),
            'connectedAs'           => __( 'Conectado como:', 'pos-base' ),
            // Nuevos textos para estados de reinicio
            'instanceNotFoundOnServer' => __( 'Instancia no encontrada en el servidor. Puedes eliminar la configuración local.', 'pos-base' ), // Para JS, si PHP no limpiara
            'configResetMessage'    => __( 'La configuración local ha sido reiniciada.', 'pos-base' ), // Mensaje genérico de reinicio
            'stateNotFound'         => __( 'NO ENCONTRADA', 'pos-base' ), // Para mostrar en el estado si PHP no limpiara
            'deleteNotFoundTitle'   => __( 'Eliminar configuración de instancia no encontrada', 'pos-base' ), // Para botón eliminar si PHP no limpiara
        ],
        // Intervalo de auto-refresco en milisegundos (ej: 30 segundos)
        // 0 para deshabilitar.
        'refreshInterval' => 30000,
    ];

    wp_localize_script( $script_handle, 'evolutionApiData', $data_for_js );
}
// Enganchar la función de encolado al hook correcto
add_action( 'admin_enqueue_scripts', 'pos_evolution_api_enqueue_manager_scripts' );

?>
