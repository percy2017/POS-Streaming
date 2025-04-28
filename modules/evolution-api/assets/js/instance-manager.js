/**
 * instance-manager.js
 *
 * Lógica para la página de gestión de instancias de Evolution API.
 * Espera que los datos necesarios (nonce, ajaxurl, instanceName, i18n)
 * sean pasados a través de wp_localize_script con el objeto 'evolutionApiData'.
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[EVO_API_JS] DOMContentLoaded - Script Initializing.');

    // --- Verificar si los datos localizados están disponibles ---
    if (typeof evolutionApiData === 'undefined') {
        console.error('[EVO_API_JS] CRITICAL ERROR: evolutionApiData object not found. Check wp_localize_script call in PHP.');
        const managerDiv = document.getElementById('pos-evolution-api-manager');
        if (managerDiv) {
            managerDiv.innerHTML = '<div class="notice notice-error"><p>Error crítico: No se pudieron cargar los datos necesarios (evolutionApiData). Revisa la consola.</p></div>';
        }
        return;
    }
    console.log('[EVO_API_JS] evolutionApiData received:', evolutionApiData);

    // --- Desestructurar datos para fácil acceso ---
    const { ajaxurl, nonce, instanceName, i18n } = evolutionApiData;

    // --- Elementos del DOM ---
    const createButton = document.getElementById('create-instance-button');
    const getQrButton = document.getElementById('get-qr-button');
    const getStatusButton = document.getElementById('get-status-button');
    const disconnectButton = document.getElementById('disconnect-instance-button');
    const deleteButton = document.getElementById('delete-instance-button');
    const qrSection = document.getElementById('qr-code-section');
    const qrContainer = document.getElementById('qr-code-container');
    const qrLoadingMessage = document.getElementById('qr-loading-message');
    const closeQrButton = document.getElementById('close-qr-button');
    const statusContainer = document.getElementById('instance-status-content'); // Contenedor general de estado
    const statusLoadingMessage = document.getElementById('status-loading-message'); // Mensaje "Consultando..."
    const instanceDetailsDiv = document.getElementById('instance-details'); // Div para detalles específicos
    const statusMessageArea = document.getElementById('status-message-area'); // Div para mensajes generales
    const stateSpan = document.getElementById('instance-state-value'); // Span para el estado
    const profilePicImg = document.getElementById('instance-profile-picture'); // <-- AÑADIR ESTA LÍNEA
    // const widSpan = document.getElementById('instance-wid-value'); // Span para el WID
    const pushnameSpan = document.getElementById('instance-pushname-value'); // Span para el nombre
    const ownerSpan = document.getElementById('instance-owner-value'); // <-- Span para el Owner
    const logListElement = document.getElementById('instance-log-list'); // Lista para el log
    const clearLogButton = document.getElementById('clear-log-button'); // Botón limpiar log

    console.log('[EVO_API_JS] DOM Elements selected.');

    // --- Variables de Estado y Configuración ---
    let isRefreshingStatus = false; // Flag para evitar solapamiento de refrescos
    const refreshInterval = 30000; // Intervalo en milisegundos (30 segundos)
    let refreshTimerId = null; // ID del temporizador para poder detenerlo
    let logEntries = []; // Array para almacenar entradas del log
    const maxLogEntries = 15; // Máximo de entradas a mostrar en UI

    // --- Funciones Auxiliares ---

    // Mostrar estado de carga en botones
    function showLoading(button) {
        if (!button) return;
        console.log('[EVO_API_JS] showLoading for button:', button.id);
        button.disabled = true;
    }

    // Ocultar estado de carga en botones
    function hideLoading(button) {
         if (!button) return;
         console.log('[EVO_API_JS] hideLoading for button:', button.id);
         button.disabled = false;
    }

    // Mostrar mensajes generales (éxito/error/advertencia)
    function displayStatusMessage(message, type = 'info') {
        console.log(`[EVO_API_JS] --- displayStatusMessage function START - Type: ${type}`);
        if (!statusMessageArea) {
             console.error('[EVO_API_JS] displayStatusMessage: Missing status-message-area DOM element.');
             return;
        }
        // Ocultar spinner de carga inicial si aún está visible
        if (statusLoadingMessage) statusLoadingMessage.style.display = 'none';
        // Mostrar mensaje
        statusMessageArea.innerHTML = `<div class="notice notice-${type} is-dismissible" style="margin: 0;"><p>${message}</p></div>`;
        console.log(`[EVO_API_JS] --- displayStatusMessage function END - Type: ${type}, Message set: ${message}`);
    }

// Actualizar los detalles específicos de la instancia (Estado, Nombre, Owner, Foto)
function updateInstanceDetails(state = '-', /* wid eliminado */ pushname = '-', owner = '-', profilePicUrl = null) { // <-- Parámetros actualizados
     console.log(`[EVO_API_JS] --- updateInstanceDetails START - State: ${state}, Pushname: ${pushname}, Owner: ${owner}, PicURL: ${profilePicUrl ? 'Yes' : 'No'}`); // <-- Log actualizado

     // Actualizar textos
     if (stateSpan) stateSpan.textContent = state.toUpperCase();
     // if (widSpan) widSpan.textContent = wid || '-'; // <-- LÍNEA ELIMINADA
     if (pushnameSpan) pushnameSpan.textContent = pushname || '-';
     if (ownerSpan) ownerSpan.textContent = owner || '-';

     // Actualizar imagen de perfil
     if (profilePicImg) {
         if (profilePicUrl) {
             profilePicImg.src = profilePicUrl;
             profilePicImg.style.display = 'block'; // Mostrar imagen
         } else {
             profilePicImg.src = '#'; // Resetear src
             profilePicImg.style.display = 'none'; // Ocultar imagen
         }
     }

     // Mostrar el contenedor de detalles si no estaba visible
     if (instanceDetailsDiv) instanceDetailsDiv.style.display = 'block';
     // Ocultar mensaje de carga inicial
     if (statusLoadingMessage) statusLoadingMessage.style.display = 'none';
     console.log('[EVO_API_JS] --- updateInstanceDetails END ---');
}



    // Mostrar el código QR
    function displayQrCode(base64Qr) {
        console.log('[EVO_API_JS] --- displayQrCode function START ---');
        if (!qrContainer || !qrSection || !qrLoadingMessage) {
             console.error('[EVO_API_JS] displayQrCode: Missing required DOM elements.');
             return;
        }
        console.log('[EVO_API_JS] displayQrCode - Received base64 data (length):', base64Qr ? base64Qr.length : 'null');
        if (qrLoadingMessage) qrLoadingMessage.style.display = 'none'; // Ocultar 'Generando...'
        qrContainer.innerHTML = '';
        const img = document.createElement('img');
        img.src = base64Qr;
        img.alt = i18n.qrAltText || 'Código QR de WhatsApp';
        img.style.maxWidth = '300px'; img.style.height = 'auto'; img.style.display = 'block'; img.style.margin = '0 auto';
        qrContainer.appendChild(img);
        qrSection.style.display = 'block';
        console.log('[EVO_API_JS] QR Code image appended and section displayed.');
        console.log('[EVO_API_JS] --- displayQrCode function END ---');
    }

    // Ocultar el código QR
    function hideQrCode() {
        console.log('[EVO_API_JS] --- hideQrCode function START ---');
        if (!qrSection || !qrContainer) { // No necesitamos qrLoadingMessage aquí
            console.warn('[EVO_API_JS] hideQrCode: Missing qrSection or qrContainer.');
            return;
        };
        qrSection.style.display = 'none';
        qrContainer.innerHTML = ''; // Limpiar imagen
        // Re-append the loading message structure only if qrLoadingMessage exists initially
        if (qrLoadingMessage) {
            const p = document.createElement('p');
            p.id = 'qr-loading-message';
            p.innerHTML = '<span class="spinner is-active" style="float: left; margin-right: 5px;"></span>' + (i18n.qrLoading || 'Generando código QR...');
            qrContainer.appendChild(p);
            p.style.display = 'block'; // Asegurar que sea visible
        }
        console.log('[EVO_API_JS] QR Code section hidden and container reset.');
        console.log('[EVO_API_JS] --- hideQrCode function END ---');
    }

    // --- Log de Actividad en UI ---
    function logActivity(message, type = 'INFO') {
        if (!logListElement) return;
        const timestamp = new Date().toLocaleTimeString();
        const logMessage = `[${timestamp}] [${type}] ${message}`;
        console.log(`[EVO_API_JS_LOG] ${logMessage}`); // Loguear también en consola

        logEntries.unshift(logMessage); // Añadir al inicio
        if (logEntries.length > maxLogEntries) logEntries = logEntries.slice(0, maxLogEntries); // Limitar tamaño

        // Actualizar HTML
        const placeholder = logListElement.querySelector('.log-entry-placeholder');
        if(placeholder) placeholder.remove();
        logListElement.innerHTML = logEntries.map(entry => {
            let className = '';
            if (entry.includes('[ERROR]')) className = 'log-error';
            else if (entry.includes('[WARN]')) className = 'log-warning';
            else if (entry.includes('[SUCCESS]')) className = 'log-success';
            return `<li class="${className}" style="margin-bottom: 3px; padding-bottom: 3px; border-bottom: 1px dotted #ccc;">${entry.replace('[ERROR]', '<strong style="color:red;">[ERROR]</strong>').replace('[WARN]', '<strong style="color:orange;">[WARN]</strong>').replace('[SUCCESS]', '<strong style="color:green;">[SUCCESS]</strong>')}</li>`;
        }).join('');
    }

    // Limpiar log de UI
     if (clearLogButton && logListElement) {
        clearLogButton.addEventListener('click', () => {
            logEntries = [];
            logListElement.innerHTML = `<li class="log-entry-placeholder">${i18n.logCleared || 'Log limpiado.'}</li>`;
            logActivity(i18n.logCleared || 'Log limpiado.', 'SYSTEM');
        });
    }

    // --- Manejador AJAX Genérico ---
    function performAjaxAction(action, data = {}, button = null) {
        logActivity(`Iniciando acción: ${action}`, 'ACTION'); // Log de actividad
        console.log(`[EVO_API_JS] performAjaxAction called. Action: ${action}, Button: ${button ? button.id : 'N/A'}, Data:`, data);
        showLoading(button);

        // Marcar si es refresco de estado
        if (action === 'pos_evolution_get_status') isRefreshingStatus = true;

        // Ocultar QR excepto si se está pidiendo
        if (action !== 'pos_evolution_get_qr') {
            hideQrCode();
        } else {
            console.log('[EVO_API_JS] Action is get_qr, skipping initial hideQrCode.');
             if (qrContainer && qrLoadingMessage) {
                 qrContainer.innerHTML = ''; qrContainer.appendChild(qrLoadingMessage);
                 qrLoadingMessage.style.display = 'block'; qrSection.style.display = 'block';
             }
        }

        const formData = new URLSearchParams({ action: action, _ajax_nonce: nonce, instance_name: instanceName, ...data });
        console.log('[EVO_API_JS] Sending AJAX request. FormData:', formData.toString());

        return fetch(ajaxurl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData })
        .then(response => {
            console.log('[EVO_API_JS] Received AJAX response (raw):', response);
             if (!response.ok) {
                 return response.text().then(text => {
                     console.error(`[EVO_API_JS] AJAX response not OK. Status: ${response.status}, Body: ${text}`);
                     try {
                         const errorData = JSON.parse(text);
                         throw new Error(errorData.data || response.statusText || 'Unknown error');
                     } catch (e) {
                         throw new Error(text || response.statusText || 'Unknown error');
                     }
                 });
             }
            return response.json().catch(error => {
                console.error('[EVO_API_JS] Failed to parse JSON response even though status was OK:', error);
                throw new Error('Invalid JSON response from server.');
            });
        })
        .then(result => {
            console.log('[EVO_API_JS] Parsed AJAX response JSON:', result);
            hideLoading(button);
            // Siempre desmarcar refresco al completar la llamada de estado
            if (action === 'pos_evolution_get_status') isRefreshingStatus = false;

            if (result.success) {
                logActivity(result.data.message || 'Acción completada', 'SUCCESS'); // Log de actividad
                console.log('[EVO_API_JS] AJAX call successful (result.success is true).');
                console.log('[EVO_API_JS] Checking result.data:', result.data);
                const qrData = result.data ? result.data.qr_base64 : undefined;
                console.log('[EVO_API_JS] Value of result.data.qr_base64 BEFORE check:', qrData);

                if (qrData) { // Mostrar QR si existe
                    console.log('[EVO_API_JS] >>> ENTERED IF block (QR data is truthy).');
                    displayQrCode(qrData);
                    displayStatusMessage(result.data.message || i18n.qrObtained || 'Código QR obtenido.', 'success');
                } else { // Si no hay QR
                    console.log('[EVO_API_JS] >>> ENTERED ELSE block (QR data is falsy or missing).');
                    // Mostrar mensaje general (puede ser de error o informativo)
                    displayStatusMessage(result.data.message || i18n.errorNoQr || 'No se pudo obtener el código QR...', 'warning');

                    // Si la acción era obtener QR y falló (no vino QR), intentar obtener estado para dar contexto
                    // PERO solo si el estado NO es ya 'CONNECTED' (no tiene sentido pedir QR si ya está conectado)
                    if (action === 'pos_evolution_get_qr' && getStatusButton && (!result.data || result.data.state !== 'CONNECTED')) {
                        console.log('[EVO_API_JS] QR request did not return QR, attempting to get status for context.');
                        setTimeout(() => performAjaxAction('pos_evolution_get_status', {}, getStatusButton), 500);
                    }
                }

                // Actualizar detalles si es respuesta de get_status
                if (result.data && result.data.state && action === 'pos_evolution_get_status') {
                    console.log('[EVO_API_JS] State data found in get_status response, calling updateInstanceDetails.');
                    const details = result.data.details || {};
                    const instanceDetails = details.instance || {}; // <-- Objeto con wid, pushname, owner, etc.
                    updateInstanceDetails(
                        result.data.state, // <-- Estado corregido por PHP
                        // instanceDetails.owner,
                        instanceDetails.profileName, // <-- Usar profileName según tu JSON
                        instanceDetails.owner, // <-- Pasar el owner
                        instanceDetails.profilePictureUrl
                    );
                    // Mostrar mensaje específico de estado actualizado en el área general
                     displayStatusMessage(i18n.statusRefreshed || 'Estado actualizado.', 'info');
                }

                // Recarga comentada
                if (action === 'pos_evolution_create_instance' || action === 'pos_evolution_delete_instance') {
                    console.warn('[EVO_API_JS] Page reload after create/delete is currently commented out for debugging.');
                    // Swal.fire({...}).then(() => { window.location.reload(); });
                }

            } else { // result.success es false
                const errorMessage = result.data || i18n.errorUnknown || 'Ocurrió un error desconocido.';
                logActivity(errorMessage, 'ERROR'); // Log de actividad
                console.error('[EVO_API_JS] AJAX call failed (result.success is false). Error data:', result.data);
                displayStatusMessage(errorMessage, 'error');
                Swal.fire({ icon: 'error', title: i18n.errorTitle || 'Error', text: errorMessage });
            }
            return result;
        })
        .catch(error => {
            const errorMsgString = (error && error.message) ? String(error.message) : 'Unknown fetch error';
            logActivity(`Error de red/servidor: ${errorMsgString}`, 'ERROR'); // Log de actividad
            console.error('[EVO_API_JS] AJAX fetch failed (Network error or exception):', error);
            hideLoading(button);
            // Asegurarse de desmarcar refresco incluso si hay error
            if (action === 'pos_evolution_get_status') isRefreshingStatus = false;
            const errorMessage = `${i18n.errorNetwork || 'Error de red o de servidor:'} ${errorMsgString}`;
            displayStatusMessage(errorMessage, 'error');
            Swal.fire({ icon: 'error', title: i18n.errorConnection || 'Error de Conexión', text: errorMessage });
            return { success: false, data: errorMessage };
        });
    }

    // --- Event Listeners ---
    console.log('[EVO_API_JS] Attaching event listeners.');

    // --- Botón Crear Instancia ---
    if (createButton) {
        createButton.addEventListener('click', function() {
            logActivity(`Botón '${this.textContent.trim()}' presionado.`, 'UI');
            console.log('[EVO_API_JS] Create Instance button clicked.');
            Swal.fire({
                title: i18n.createTitle || 'Crear Nueva Instancia',
                input: 'text',
                inputLabel: i18n.instanceNameLabel || 'Nombre para la instancia',
                inputPlaceholder: i18n.instanceNamePlaceholder || 'Ej: tienda_principal (solo letras, números, -, _)',
                inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
                showCancelButton: true,
                confirmButtonText: i18n.createButtonText || 'Crear y Obtener QR',
                cancelButtonText: i18n.cancelButtonText || 'Cancelar',
                inputValidator: (value) => {
                    if (!value) return i18n.errorNameRequired || '¡Necesitas escribir un nombre!';
                    if (!/^[a-zA-Z0-9_-]+$/.test(value)) return i18n.errorNameInvalid || 'Nombre inválido. Usa solo letras, números, guiones bajos o medios.';
                },
                showLoaderOnConfirm: true,
                preConfirm: (newInstanceName) => {
                    console.log('[EVO_API_JS] SweetAlert preConfirm for create. Instance name:', newInstanceName);
                    return performAjaxAction('pos_evolution_create_instance', { instance_name_to_create: newInstanceName }, createButton);
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                console.log('[EVO_API_JS] SweetAlert result for create:', result);
                if (result.isDismissed) {
                    console.log('[EVO_API_JS] Create instance cancelled.');
                    logActivity('Creación de instancia cancelada.', 'UI');
                }
                // La recarga o actualización de UI se maneja dentro de performAjaxAction
            });
        });
    } else { console.log('[EVO_API_JS] Create button not found.'); }

    // --- Botón Cerrar QR ---
    if (closeQrButton) {
        closeQrButton.addEventListener('click', function() {
             logActivity(`Botón '${this.textContent.trim()}' presionado.`, 'UI');
             console.log('[EVO_API_JS] Close QR button clicked.');
             hideQrCode();
        });
    } else { console.log('[EVO_API_JS] Close QR button not found.'); }

    // --- Botón Obtener QR ---
    if (getQrButton) {
        getQrButton.addEventListener('click', function() {
            logActivity(`Botón '${this.textContent.trim()}' presionado.`, 'UI');
            console.log('[EVO_API_JS] Get QR button clicked.');
            performAjaxAction('pos_evolution_get_qr', {}, this);
        });
    } else { console.log('[EVO_API_JS] Get QR button not found.'); }

    // --- Botón Obtener Estado ---
    if (getStatusButton) {
        getStatusButton.addEventListener('click', function() {
            logActivity(`Botón '${this.textContent.trim()}' presionado.`, 'UI');
            console.log('[EVO_API_JS] Get Status button clicked.');
            performAjaxAction('pos_evolution_get_status', {}, this);
        });
    } else { console.log('[EVO_API_JS] Get Status button not found.'); }

    // --- Botón Desconectar ---
    if (disconnectButton) {
        disconnectButton.addEventListener('click', function() {
            logActivity(`Botón '${this.textContent.trim()}' presionado.`, 'UI');
            console.log('[EVO_API_JS] Disconnect button clicked.');
            Swal.fire({
                title: i18n.disconnectTitle || '¿Desconectar Instancia?',
                text: i18n.disconnectText || 'Esto cerrará la sesión de WhatsApp en el servidor, pero no eliminará la instancia.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: i18n.disconnectConfirm || 'Sí, desconectar',
                cancelButtonText: i18n.cancelButtonText || 'Cancelar'
            }).then((result) => {
                console.log('[EVO_API_JS] SweetAlert result for disconnect:', result);
                if (result.isConfirmed) {
                    console.log('[EVO_API_JS] Disconnect confirmed, performing AJAX action.');
                    performAjaxAction('pos_evolution_disconnect', {}, disconnectButton);
                } else {
                     console.log('[EVO_API_JS] Disconnect cancelled.');
                     logActivity('Desconexión cancelada.', 'UI');
                }
            });
        });
    } else { console.log('[EVO_API_JS] Disconnect button not found.'); }

    // --- Botón Eliminar ---
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            logActivity(`Botón '${this.textContent.trim()}' presionado.`, 'UI');
            console.log('[EVO_API_JS] Delete button clicked.');
            const deleteHtml = `${i18n.deleteText1 || '¡Esta acción es irreversible! Se eliminará la instancia'} '<strong>${instanceName}</strong>' ${i18n.deleteText2 || 'del servidor Evolution API.'}<br><br>${i18n.deleteConfirmPrompt || 'Escribe el nombre de la instancia para confirmar:'}`;
            Swal.fire({
                title: i18n.deleteTitle || '¿Eliminar Instancia Permanentemente?',
                html: deleteHtml,
                input: 'text',
                inputPlaceholder: instanceName,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: i18n.deleteConfirm || 'Sí, eliminarla',
                cancelButtonText: i18n.cancelButtonText || 'Cancelar',
                inputValidator: (value) => {
                    if (value !== instanceName) return `${i18n.errorNameMismatch || 'El nombre no coincide. Escribe exactamente:'} ${instanceName}`;
                },
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    console.log('[EVO_API_JS] SweetAlert preConfirm for delete.');
                    return performAjaxAction('pos_evolution_delete_instance', {}, deleteButton);
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                 console.log('[EVO_API_JS] SweetAlert result for delete:', result);
                 if (result.isDismissed) {
                     console.log('[EVO_API_JS] Delete instance cancelled.');
                     logActivity('Eliminación cancelada.', 'UI');
                 }
                 // La recarga o actualización de UI se maneja dentro de performAjaxAction
            });
        });
    } else { console.log('[EVO_API_JS] Delete button not found.'); }

    // --- Auto-Refresco ---
    function triggerStatusRefresh() {
        console.log('[EVO_API_JS] Auto-refresh triggered.');
        // Solo refrescar si no hay otra llamada de estado en curso,
        // si existe el botón de estado y si hay una instancia gestionada
        if (!isRefreshingStatus && getStatusButton && instanceName) {
            logActivity(i18n.autoRefreshing || 'Actualizando estado automáticamente...', 'SYSTEM');
            // Simular clic en el botón de refrescar estado
            getStatusButton.click();
        } else {
             console.log('[EVO_API_JS] Auto-refresh skipped (isRefreshingStatus:', isRefreshingStatus, ', getStatusButton:', !!getStatusButton, ', instanceName:', instanceName, ')');
        }
    }

    // --- Carga Inicial del Estado y Inicio de Auto-Refresco ---
    if (instanceName && getStatusButton) {
        logActivity('Cargando estado inicial...', 'SYSTEM');
        console.log('[EVO_API_JS] Initial state load: Found instanceName and Get Status button. Triggering click.');
        // Carga inicial del estado
        setTimeout(() => {
             if (getStatusButton) { getStatusButton.click(); }
             else { console.error("[EVO_API_JS] Initial state load: getStatusButton became null before timeout?"); }
        }, 100);

        // Iniciar auto-refresco después de la carga inicial
        console.log(`[EVO_API_JS] Starting auto-refresh interval (${refreshInterval}ms).`);
        // Limpiar cualquier timer anterior por si acaso
        if (refreshTimerId) clearInterval(refreshTimerId);
        refreshTimerId = setInterval(triggerStatusRefresh, refreshInterval);
    } else {
         logActivity('No hay instancia gestionada. Esperando creación.', 'SYSTEM');
         console.log('[EVO_API_JS] Initial state load: No instanceName found, skipping initial status check and auto-refresh.');
         // Asegurarse de que no haya un timer activo si no hay instancia
         if (refreshTimerId) clearInterval(refreshTimerId);
    }

    logActivity('Interfaz lista.', 'SYSTEM');
    console.log('[EVO_API_JS] Script Initialization Complete.');

}); // End DOMContentLoaded
