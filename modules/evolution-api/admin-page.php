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

    // NOTA: El encolado de scripts (SweetAlert, instance-manager.js) y la
    //       localización de datos se hacen en la función pos_evolution_api_enqueue_manager_scripts().

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
                    <?php if ( empty( $managed_instance_name ) ) : // --- Si NO hay instancia gestionada --- ?>
                        <p><?php esc_html_e( 'Aún no has creado una instancia de Evolution API gestionada por este plugin.', 'pos-base' ); ?></p>
                        <button type="button" id="create-instance-button" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Crear Nueva Instancia', 'pos-base' ); ?>
                        </button>
                        <p class="description"><?php esc_html_e( 'Esto creará una nueva instancia en tu servidor Evolution API y la vinculará a este plugin.', 'pos-base' ); ?></p>
                    <?php else : // --- Si YA hay una instancia gestionada --- ?>
                        <p>
                            <?php printf( esc_html__( 'Gestionando instancia: %s', 'pos-base' ), '<strong>' . esc_html( $managed_instance_name ) . '</strong>' ); ?>
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
                        <button type="button" id="delete-instance-button" class="button button-danger" style="color: white; border-color: #d63638;">
                            <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-top: -2px;"></span>
                            <?php esc_html_e( 'Eliminar Instancia', 'pos-base' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php // Separador visual ?>
            <hr style="margin: 25px 0;">

            <!-- 5. Sección de Estado Actual (con detalles) -->
            <div id="instance-status-section" style="margin-bottom: 20px;">
                <h2><?php esc_html_e( 'Estado Actual de la Instancia', 'pos-base' ); ?></h2>
                <div id="instance-status-content" style="padding: 15px; background-color: #fff; border: 1px solid #ccd0d4; min-height: 50px;">
                    <?php if ( empty( $managed_instance_name ) ) : ?>
                        <p><?php esc_html_e( 'No hay ninguna instancia creada o gestionada por este plugin todavía.', 'pos-base' ); ?></p>
                    <?php else: ?>
                         <?php // Mensaje inicial de carga ?>
                         <p id="status-loading-message"><span class="spinner is-active" style="float: left; margin-right: 5px;"></span><?php esc_html_e( 'Consultando estado...', 'pos-base' ); ?></p>

                        <?php // Contenedor para los detalles específicos (inicialmente oculto) ?>
                        <div id="instance-details" style="display: none; margin-top: 10px; overflow: hidden; /* Clear float */">
                            <?php // --- Imagen de Perfil --- ?>
                            <img id="instance-profile-picture" src="#" alt="<?php esc_attr_e('Foto de perfil', 'pos-base'); ?>" style="display: none; max-width: 100px; height: auto; border-radius: 50%; float: left; margin-right: 15px; margin-bottom: 10px; border: 1px solid #eee;">

                            <?php // --- Detalles en Texto (sin WID) --- ?>
                            <div style="overflow: hidden;"> <?php // Wrapper para limpiar el float de la imagen ?>
                                <p style="margin: 5px 0;"><strong><?php esc_html_e('Estado:', 'pos-base'); ?></strong> <span id="instance-state-value">-</span></p>
                                <?php // --- LÍNEA DEL WID ELIMINADA --- ?>
                                <p style="margin: 5px 0;"><strong><?php esc_html_e('Nombre Dispositivo:', 'pos-base'); ?></strong> <span id="instance-pushname-value">-</span></p>
                                <p style="margin: 5px 0;"><strong><?php esc_html_e('Propietario (WID):', 'pos-base'); ?></strong> <span id="instance-owner-value">-</span></p> <?php // Aclaramos que Owner es el WID ?>
                            </div>
                        </div>
                        

                         <?php // Área para mostrar mensajes generales de éxito/error/advertencia ?>
                         <div id="status-message-area" style="margin-top: 10px;">
                            <?php // Los mensajes se insertarán aquí vía JS ?>
                         </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 6. Sección Log de Actividad -->
            <div id="instance-log-section" style="margin-top: 25px;">
                <h2><?php esc_html_e( 'Log de Actividad Reciente', 'pos-base' ); ?></h2>
                <div style="max-height: 200px; overflow-y: auto; background: #f0f0f1; padding: 10px; border: 1px solid #ccd0d4; font-family: monospace; font-size: 12px;">
                    <ul id="instance-log-list" style="list-style: none; margin: 0; padding: 0;">
                        <li class="log-entry-placeholder"><?php esc_html_e('Inicializando log...', 'pos-base'); ?></li>
                    </ul>
                </div>
                 <button type="button" id="clear-log-button" class="button button-secondary button-small" style="margin-top: 5px;"><?php esc_html_e('Limpiar Log', 'pos-base'); ?></button>
            </div>

            <!-- 7. Sección del Código QR (oculta por defecto) -->
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

            <?php // 8. Inputs ocultos (menos críticos ahora, pero pueden dejarse) ?>
            <input type="hidden" id="managed-instance-name-input" value="<?php echo esc_attr( $managed_instance_name ); ?>">
            <input type="hidden" id="evolution-api-nonce" value="<?php echo esc_attr( wp_create_nonce( 'evolution_api_ajax_nonce' ) ); ?>">

        <?php endif; // Fin de if ($is_configured) ?>
    </div>

    <?php
} // Fin de pos_evolution_api_render_instance_page()


/**
 * Encola los scripts y estilos necesarios para la página de gestión de instancia.
 * También localiza los datos necesarios para el script JS.
 *
 * @param string $hook_suffix El sufijo del hook de la página actual.
 */
function pos_evolution_api_enqueue_manager_scripts( $hook_suffix ) {

    // Determinar el hook_suffix correcto para nuestra subpágina.
    $target_hook = 'pos-base_page_pos-evolution-api-instance'; // Ajusta si es diferente

    // Solo encolar en nuestra página específica
    if ( $hook_suffix !== $target_hook ) {
        return;
    }
    error_log("[EVO_API_ENQUEUE] Enqueueing scripts for hook: " . $hook_suffix); // Log para confirmar

    // --- Manejo de SweetAlert2 ---
    // Asumimos que POS Base lo encola con el handle 'pos-base-sweetalert2'
    // Si no es así, descomenta la línea del CDN o encola una versión local.
    // $sweetalert_handle = 'pos-base-sweetalert2'; // <- AJUSTA ESTE HANDLE SI ES DIFERENTE
    wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true ); // CDN como fallback si no existe el de POS Base

    // 2. Encolar nuestro script principal
    $script_handle = 'pos-evolution-api-manager';
    $script_url = EVOLUTION_API_MODULE_URL . 'assets/js/instance-manager.js';
    // Usar filemtime para versionado automático y evitar caché
    $script_version = file_exists( EVOLUTION_API_MODULE_DIR . 'assets/js/instance-manager.js' ) ? filemtime( EVOLUTION_API_MODULE_DIR . 'assets/js/instance-manager.js' ) : '1.0.0';
    // Añadir dependencia de SweetAlert (usando el handle correcto)
    $dependencies = ['jquery', 'sweetalert2']; // Dependencias

    wp_enqueue_script( $script_handle, $script_url, $dependencies, $script_version, true ); // true para cargar en footer

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

            // Textos específicos de acciones
            'createTitle'           => __( 'Crear Nueva Instancia', 'pos-base' ),
            'instanceNameLabel'     => __( 'Nombre para la instancia', 'pos-base' ),
            'instanceNamePlaceholder' => __( 'Ej: tienda_principal (solo letras, números, -, _)', 'pos-base' ),
            'createButtonText'      => __( 'Crear y Obtener QR', 'pos-base' ),
            'errorNameRequired'     => __( '¡Necesitas escribir un nombre!', 'pos-base' ),
            'errorNameInvalid'      => __( 'Nombre inválido. Usa solo letras, números, guiones bajos o medios.', 'pos-base' ),
            'disconnectTitle'       => __( '¿Desconectar Instancia?', 'pos-base' ),
            'disconnectText'        => __( 'Esto cerrará la sesión de WhatsApp en el servidor, pero no eliminará la instancia.', 'pos-base' ),
            'disconnectConfirm'     => __( 'Sí, desconectar', 'pos-base' ),
            'deleteTitle'           => __( '¿Eliminar Instancia Permanentemente?', 'pos-base' ),
            'deleteText1'           => __( '¡Esta acción es irreversible! Se eliminará la instancia', 'pos-base' ),
            'deleteText2'           => __( 'del servidor Evolution API.', 'pos-base' ),
            'deleteConfirmPrompt'   => __( 'Escribe el nombre de la instancia para confirmar:', 'pos-base' ),
            'deleteConfirm'         => __( 'Sí, eliminarla', 'pos-base' ),
            'errorNameMismatch'     => __( 'El nombre no coincide. Escribe exactamente:', 'pos-base' ),

            // Textos para nuevas funcionalidades UI
            'statusRefreshed'       => __( 'Estado actualizado.', 'pos-base' ),
            'logCleared'            => __( 'Log limpiado.', 'pos-base' ),
            'logInitialized'        => __( 'Interfaz de gestión inicializada.', 'pos-base' ),
            'autoRefreshing'        => __( 'Actualizando estado automáticamente...', 'pos-base' ),
            'currentState'          => __( 'Estado actual:', 'pos-base' ), // Usado en JS para construir mensaje
            'connectedAs'           => __( 'Conectado como:', 'pos-base' ), // Usado en JS
        ],
    ];

    wp_localize_script( $script_handle, 'evolutionApiData', $data_for_js );
    error_log("[EVO_API_ENQUEUE] Data localized for JS: " . print_r($data_for_js, true)); // Log para confirmar datos

     // Opcional: Encolar un archivo CSS si tienes estilos específicos
     // wp_enqueue_style('pos-evolution-api-manager-styles', EVOLUTION_API_MODULE_URL . 'assets/css/instance-manager.css', [], $script_version);
}
// Enganchar la función de encolado al hook correcto
add_action( 'admin_enqueue_scripts', 'pos_evolution_api_enqueue_manager_scripts' );

?>
