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

    const { ajaxurl, nonce, i18n } = evolutionApiData; // Obtiene las constantes
    let instanceName = evolutionApiData.instanceName;   // Obtiene instanceName con let

    // --- Elementos del DOM ---
    const createInstanceSection = document.getElementById('create-instance-section'); // <-- AÑADIR
    const manageInstanceSection = document.getElementById('manage-instance-section'); // <-- AÑADIR
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
    const pushnameSpan = document.getElementById('instance-pushname-value'); // Span para el nombre
    const ownerSpan = document.getElementById('instance-owner-value'); // <-- Span para el Owner
    const logListElement = document.getElementById('instance-log-list'); // Lista para el log
    const clearLogButton = document.getElementById('clear-log-button'); // Botón limpiar log

    // --- Deshabilitar botón Eliminar por defecto ---
    if (deleteButton) {
        deleteButton.disabled = true //true;
        deleteButton.title = i18n.statusLoading || 'Cargando estado...'; // Título inicial
    }

 
    
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
        button.disabled = true;
    }

    // Ocultar estado de carga en botones
    function hideLoading(button) {
         if (!button) return;
         button.disabled = false;
    }

    // Mostrar mensajes generales (éxito/error/advertencia)
    function displayStatusMessage(message, type = 'info') {
        if (!statusMessageArea) {
             return;
        }
        // Ocultar spinner de carga inicial si aún está visible
        if (statusLoadingMessage) statusLoadingMessage.style.display = 'none';
        // Mostrar mensaje
        statusMessageArea.innerHTML = `<div class="notice notice-${type} is-dismissible" style="margin: 0;"><p>${message}</p></div>`;
    }


    /**
     * Inicia o reinicia el temporizador para refrescar automáticamente el estado de la instancia.
     * Limpia cualquier temporizador anterior.
     * Solo inicia si el intervalo es > 0 y hay un nombre de instancia gestionada.
     */
    function startStatusRefresh() {
        // Limpiar cualquier timer existente para evitar duplicados
        if (refreshTimerId) {
            clearInterval(refreshTimerId);
            refreshTimerId = null;
            // console.log('[EVO_API_JS] Cleared existing auto-refresh timer.');
        }

        // Obtener intervalo desde los datos localizados (o usar el valor por defecto si no está)
        const interval = evolutionApiData.refreshInterval || 0; // Usar 0 si no está definido

        // Solo iniciar si el intervalo es válido (> 0) y tenemos un nombre de instancia
        if (interval > 0 && instanceName) {
            console.log(`[EVO_API_JS] Starting auto-refresh timer with interval: ${interval}ms for instance: ${instanceName}`);
            logActivity(`Auto-refresco iniciado (cada ${interval / 1000}s).`, 'SYSTEM');

            // Configurar el nuevo intervalo
            refreshTimerId = setInterval(() => {
                // Verificar si ya hay una petición de estado en curso
                if (!isRefreshingStatus) {
                    console.log('[EVO_API_JS] Auto-refresh triggered: Getting status...');
                    logActivity(i18n.autoRefreshing || 'Actualizando estado automáticamente...', 'SYSTEM');
                    // Llamar a performAjaxAction para obtener el estado
                    // Pasar getStatusButton para que muestre el spinner si existe
                    performAjaxAction('pos_evolution_get_status', {}, getStatusButton);
                } else {
                    // Si ya hay una petición en curso, saltar este ciclo
                    console.log('[EVO_API_JS] Auto-refresh skipped: Previous refresh still in progress.');
                }
            }, interval); // Usar el intervalo configurado

        } else {
            // Si el intervalo es 0 o no hay nombre de instancia, asegurarse de que no haya timer activo
            console.log(`[EVO_API_JS] Auto-refresh disabled (Interval: ${interval}ms, InstanceName: '${instanceName}').`);
            if (refreshTimerId) { // Doble verificación por si acaso
                    clearInterval(refreshTimerId);
                    refreshTimerId = null;
            }
        }
    }
    
    // Actualizar los detalles específicos de la instancia (Estado, Nombre, Owner, Foto) y el botón Eliminar
    // MODIFICADO: También muestra la sección de gestión y oculta la de creación.
    function updateInstanceDetails(state = '-', pushname = '-', owner = '-', profilePicUrl = null) {
        console.log(`[EVO_API_JS] --- updateInstanceDetails START - State: ${state}, Pushname: ${pushname}, Owner: ${owner}, PicURL: ${profilePicUrl ? 'Yes' : 'No'}`);

        // *** INICIO MODIFICACIÓN: Mostrar/Ocultar Secciones ***
        if (manageInstanceSection) {
            manageInstanceSection.style.display = 'block'; // Mostrar sección de gestión
            console.log('[EVO_API_JS] updateInstanceDetails: Showing manageInstanceSection.');
        } else {
             console.warn('[EVO_API_JS] updateInstanceDetails: manageInstanceSection not found!');
        }
        if (createInstanceSection) {
            createInstanceSection.style.display = 'none'; // Ocultar sección de creación
            console.log('[EVO_API_JS] updateInstanceDetails: Hiding createInstanceSection.');
        } else {
             console.warn('[EVO_API_JS] updateInstanceDetails: createInstanceSection not found!');
        }
        // Ocultar mensaje "No hay instancia" y mostrar contenedor de estado si no está visible
        const noInstanceMsg = document.getElementById('no-instance-message');
        if (noInstanceMsg) noInstanceMsg.style.display = 'none';
        if (statusContainer) statusContainer.style.display = 'block';
        // *** FIN MODIFICACIÓN ***

        const upperCaseState = state.toUpperCase(); // Convertir a mayúsculas para comparación

        // Actualizar textos
        if (stateSpan) stateSpan.textContent = upperCaseState;
        if (pushnameSpan) pushnameSpan.textContent = pushname || '-';
        if (ownerSpan) ownerSpan.textContent = owner || '-';

        // Actualizar imagen de perfil
        if (profilePicImg) {
            if (profilePicUrl) {
                profilePicImg.src = profilePicUrl;
                profilePicImg.style.display = 'block'; // Asegurarse de que sea visible si hay URL
            } else {
                profilePicImg.src = '#';
                profilePicImg.style.display = 'none'; // Ocultar si no hay URL
            }
        }

        // --- Habilitar/Deshabilitar Botón Eliminar ---
        // (Esta lógica permanece igual)
        if (deleteButton) {
            // Deshabilitar si el estado es CONNECTED (o cualquier otro estado "activo" que consideres, como 'open')
            if (upperCaseState === 'CONNECTED' || upperCaseState === 'OPEN') {
                deleteButton.disabled = true;
                deleteButton.title = i18n.errorDeleteConnected || 'Debes desconectar la instancia antes de eliminarla.';
                console.log('[EVO_API_JS] Delete button DISABLED (state is CONNECTED/OPEN).');
            } else {
                // Habilitar para otros estados (CLOSED, DISCONNECTED, etc.)
                deleteButton.disabled = false;
                // Restaurar el título original o uno genérico para eliminar
                deleteButton.title = i18n.deleteTitle || 'Eliminar Instancia Permanentemente';
                console.log('[EVO_API_JS] Delete button ENABLED (state is not CONNECTED/OPEN).');
            }
        } else {
            console.warn('[EVO_API_JS] Delete button not found in updateInstanceDetails.');
        }
        // --- Fin Habilitar/Deshabilitar ---


        // Mostrar el contenedor de detalles si no estaba visible
        if (instanceDetailsDiv) instanceDetailsDiv.style.display = 'block';
        // Ocultar mensaje de carga inicial
        if (statusLoadingMessage) statusLoadingMessage.style.display = 'none';
        console.log('[EVO_API_JS] --- updateInstanceDetails END ---');
    }


    // Mostrar el código QR
    // MODIFICADO: Detiene el auto-refresco al mostrarse.
    function displayQrCode(base64Qr) {
        // *** INICIO MODIFICACIÓN: Detener auto-refresco ***
        if (refreshTimerId) {
            clearInterval(refreshTimerId);
            refreshTimerId = null;
            console.log('[EVO_API_JS] Auto-refresh timer stopped by displayQrCode.');
            logActivity('Auto-refresco detenido (mostrando QR).', 'SYSTEM');
        }
        // *** FIN MODIFICACIÓN ***

        console.log('[EVO_API_JS] displayQrCode called.');
        logActivity('Mostrando código QR...', 'ACTION');

        // Verificar que los elementos necesarios existen
        if (!qrContainer || !qrSection || !qrLoadingMessage) {
             console.error('[EVO_API_JS] displayQrCode: Missing required DOM elements (qrContainer, qrSection, or qrLoadingMessage).');
             displayStatusMessage('Error interno: No se encontraron los elementos para mostrar el QR.', 'error');
             return; // Salir si faltan elementos
        }

        // Verificar si se proporcionaron datos para el QR
        if (!base64Qr) {
            console.warn('[EVO_API_JS] displayQrCode: No base64Qr data provided.');
            displayStatusMessage(i18n.errorNoQr || 'No se pudo obtener el código QR.', 'warning');
            // Ocultar sección QR si no hay datos
            if (qrSection) qrSection.style.display = 'none';
            // Reiniciar timer si no se muestra QR
            startStatusRefresh();
            return; // Salir si no hay datos
        }

        // Ocultar mensaje 'Generando...'
        if (qrLoadingMessage) qrLoadingMessage.style.display = 'none';

        // Limpiar contenedor antes de añadir nueva imagen
        qrContainer.innerHTML = '';

        // Crear elemento imagen
        const img = document.createElement('img');
        img.id = 'qr-code-image'; // Añadir ID si se necesita referenciar
        img.src = base64Qr; // Asignar datos base64
        img.alt = i18n.qrAltText || 'Código QR de WhatsApp';
        // Aplicar estilos básicos para asegurar visibilidad y tamaño razonable
        img.style.maxWidth = '300px';
        img.style.height = 'auto';
        img.style.display = 'block';
        img.style.margin = '0 auto'; // Centrar imagen

        // Añadir imagen al contenedor
        qrContainer.appendChild(img);

        // Mostrar toda la sección del QR
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

        startStatusRefresh();
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

    // --- Nueva Función: Reiniciar la Interfaz de Usuario ---
    // Muestra la sección de creación y oculta la de gestión.
    function resetInstanceManagerUI(message = '', messageType = 'info') {
        console.log('[EVO_API_JS] --- resetInstanceManagerUI START --- Message:', message);
        logActivity('Reiniciando interfaz de gestión...', 'SYSTEM');

        // 1. Ocultar detalles y contenedor de estado
        if (instanceDetailsDiv) instanceDetailsDiv.style.display = 'none';
        if (statusContainer) statusContainer.style.display = 'block'; // Asegurar que el contenedor general sea visible
        if (statusLoadingMessage) statusLoadingMessage.style.display = 'none'; // Ocultar carga
        const noInstanceMsg = document.getElementById('no-instance-message'); // Mensaje "No hay instancia"
        if (noInstanceMsg) noInstanceMsg.style.display = 'block'; // Mostrar mensaje de "no hay instancia"

        // 2. Limpiar/resetear spans de detalles (opcional, ya que estarán ocultos)
        if (stateSpan) stateSpan.textContent = '-';
        if (pushnameSpan) pushnameSpan.textContent = '-';
        if (ownerSpan) ownerSpan.textContent = '-';
        if (profilePicImg) {
            profilePicImg.src = '#';
            profilePicImg.style.display = 'none';
        }

        // 3. *** MODIFICADO: Ocultar la sección de gestión completa ***
        if (manageInstanceSection) {
            manageInstanceSection.style.display = 'none';
            console.log('[EVO_API_JS] resetInstanceManagerUI: Hiding manageInstanceSection.');
        } else {
            console.warn('[EVO_API_JS] resetInstanceManagerUI: manageInstanceSection not found!');
        }

        // 4. Ocultar sección QR
        hideQrCode(); // Usar la función existente

        // 5. *** MODIFICADO: Mostrar la sección de creación completa ***
        if (createInstanceSection) {
            createInstanceSection.style.display = 'block'; // O 'inline-block' si prefieres
            console.log('[EVO_API_JS] resetInstanceManagerUI: Showing createInstanceSection.');
        } else {
            console.warn('[EVO_API_JS] resetInstanceManagerUI: createInstanceSection not found!');
        }

        // 6. Mostrar mensaje recibido (si lo hay)
        if (message) {
            displayStatusMessage(message, messageType);
        } else {
            // Limpiar área de mensajes si no hay uno específico
            if (statusMessageArea) statusMessageArea.innerHTML = '';
            // Mostrar mensaje por defecto de "no hay instancia" si no viene uno específico
            if (noInstanceMsg) noInstanceMsg.style.display = 'block';
        }

        // 7. Detener auto-refresco
        if (refreshTimerId) {
            clearInterval(refreshTimerId);
            refreshTimerId = null;
            console.log('[EVO_API_JS] Auto-refresh timer stopped.');
            logActivity('Auto-refresco detenido.', 'SYSTEM');
        }

        // 8. Limpiar log (opcional)
        // logEntries = [];
        // if (logListElement) logListElement.innerHTML = `<li class="log-entry-placeholder">${i18n.logCleared || 'Log limpiado.'}</li>`;

        // 9. Actualizar variable global (si es necesario)
        // evolutionApiData.instanceName = ''; // Cuidado con esto

        console.log('[EVO_API_JS] --- resetInstanceManagerUI END ---');
    }
  
       // --- Manejador AJAX Genérico ---
       function performAjaxAction(action, data = {}, button = null) {
           logActivity(`Iniciando acción: ${action}`, 'ACTION'); // Log de actividad
           console.log(`[EVO_API_JS] performAjaxAction called. Action: ${action}, Button: ${button ? button.id : 'N/A'}, Data:`, data);
           showLoading(button);
   
           // Marcar si es refresco de estado
           if (action === 'pos_evolution_get_status') isRefreshingStatus = true;
   
           // Ocultar QR excepto si se está pidiendo
           // Si la acción es 'get_qr', mostrar el mensaje de carga del QR
           if (action !== 'pos_evolution_get_qr') {
               hideQrCode(); // Esto ahora reinicia el timer
           } else {
               console.log('[EVO_API_JS] Action is get_qr, skipping initial hideQrCode. Showing QR loading message.');
                if (qrContainer && qrLoadingMessage && qrSection) { // Asegurarse que qrSection existe
                    qrContainer.innerHTML = ''; // Limpiar contenedor
                    qrContainer.appendChild(qrLoadingMessage); // Añadir mensaje de carga
                    qrLoadingMessage.style.display = 'block'; // Mostrar mensaje de carga
                    qrSection.style.display = 'block'; // Mostrar la sección QR
                }
           }
   
           // Preparar datos para la petición AJAX
           const formData = new URLSearchParams({
               action: action,
               _ajax_nonce: nonce, // Usar el nonce localizado
               instance_name: instanceName, // Usar el nombre de instancia global JS
               ...data // Añadir datos específicos de la acción (ej: instance_name_to_create)
           });
           console.log('[EVO_API_JS] Sending AJAX request. FormData:', formData.toString());
   
           // Realizar la petición AJAX usando Fetch API
           return fetch(ajaxurl, { // ajaxurl es localizado por WP
               method: 'POST',
               headers: {
                   'Content-Type': 'application/x-www-form-urlencoded'
               },
               body: formData
           })
           .then(response => {
               // Manejar respuestas no exitosas (ej: 404, 500)
               console.log('[EVO_API_JS] Received AJAX response (raw):', response);
                if (!response.ok) {
                    // Intentar obtener texto del cuerpo para más detalles
                    return response.text().then(text => {
                        console.error(`[EVO_API_JS] AJAX response not OK. Status: ${response.status}, Body: ${text}`);
                        try {
                            // Intentar parsear como JSON si el servidor envía errores estructurados
                            const errorData = JSON.parse(text);
                            throw new Error(errorData.data || response.statusText || 'Unknown server error');
                        } catch (e) {
                            // Si no es JSON o falla el parseo, usar el texto plano o el estado HTTP
                            throw new Error(text || response.statusText || 'Unknown server error');
                        }
                    });
                }
               // Intentar parsear la respuesta como JSON si fue exitosa (2xx)
               return response.json().catch(error => {
                   console.error('[EVO_API_JS] Failed to parse JSON response even though status was OK:', error);
                   // Si falla el parseo, lanzar un error indicando respuesta inválida
                   throw new Error('Invalid JSON response from server.');
               });
           })
           .then(result => {
               // Procesar la respuesta JSON parseada
               console.log('[EVO_API_JS] Parsed AJAX response JSON:', result);
               hideLoading(button); // Ocultar spinner del botón
               // Siempre desmarcar refresco al completar la llamada de estado
               if (action === 'pos_evolution_get_status') isRefreshingStatus = false;
   
               if (result.success) {
                   // --- ÉXITO ---
                   // Log de actividad con el mensaje del backend
                   logActivity(result.data.message || 'Acción completada', 'SUCCESS');
                   console.log('[EVO_API_JS] AJAX call successful (result.success is true).');
                   console.log('[EVO_API_JS] Checking result.data:', result.data);
   
                   // --- INICIO LÓGICA DE REINICIO ---
                   // Verificar si el estado indica que la configuración local fue eliminada o no existe
                   if (result.data && (result.data.state === 'DELETED_LOCALLY' || result.data.state === 'NOT_CONFIGURED')) {
                       console.log(`[EVO_API_JS] Received state '${result.data.state}'. Resetting UI.`);
                       // Llamar a la función de reinicio con el mensaje de PHP
                       resetInstanceManagerUI(result.data.message || 'Configuración reiniciada.', 'warning');
                       // Detener el procesamiento adicional para esta respuesta exitosa
                       return result; // Salir del .then para esta respuesta específica
                   }
                   // --- FIN LÓGICA DE REINICIO ---
   
   
                   // --- Lógica para otras respuestas exitosas (si no se reinició la UI) ---
                   const qrData = result.data ? result.data.qr_base64 : undefined;
                   console.log('[EVO_API_JS] Value of result.data.qr_base64 BEFORE check:', qrData);
   
                   // Mostrar QR si la respuesta lo contiene (puede ser de 'create' o 'get_qr')
                   if (qrData) {
                       console.log('[EVO_API_JS] >>> ENTERED IF block (QR data is truthy).');
                       displayQrCode(qrData); // Esta función ahora detiene el timer
                       // Mostrar mensaje de QR obtenido, solo si no es un estado de reinicio
                       if (!result.data || (result.data.state !== 'DELETED_LOCALLY' && result.data.state !== 'NOT_CONFIGURED')) {
                            displayStatusMessage(result.data.message || i18n.qrObtained || 'Código QR obtenido.', 'success');
                       }
                   } else { // Si no hay QR en la respuesta
                       console.log('[EVO_API_JS] >>> ENTERED ELSE block (QR data is falsy or missing).');
                       // Mostrar mensaje general (puede ser de error o informativo), solo si no es reinicio
                       if (!result.data || (result.data.state !== 'DELETED_LOCALLY' && result.data.state !== 'NOT_CONFIGURED')) {
                            displayStatusMessage(result.data.message || i18n.errorNoQr || 'No se pudo obtener el código QR...', 'warning');
                       }
   
                       // Si la acción era obtener QR y falló (no vino QR), intentar obtener estado para dar contexto
                       // PERO solo si el estado NO es ya 'CONNECTED' y si la UI NO fue reiniciada
                       if (action === 'pos_evolution_get_qr' && getStatusButton && (!result.data || (result.data.state !== 'CONNECTED' && result.data.state !== 'DELETED_LOCALLY' && result.data.state !== 'NOT_CONFIGURED'))) {
                           console.log('[EVO_API_JS] QR request did not return QR, attempting to get status for context.');
                           // Reiniciar timer aquí porque hideQrCode no se llamó
                           startStatusRefresh();
                           // Pedir estado después de un breve delay
                           setTimeout(() => performAjaxAction('pos_evolution_get_status', {}, getStatusButton), 500);
                       } else if (action !== 'pos_evolution_create_instance') {
                           // Si no hubo QR y no fue una creación, reiniciar el timer (hideQrCode lo hace, pero por si acaso)
                           startStatusRefresh();
                       }
                   }
   
                   // Actualizar detalles si es respuesta de get_status Y NO es un estado de reinicio
                   if (result.data && result.data.state && action === 'pos_evolution_get_status' && result.data.state !== 'DELETED_LOCALLY' && result.data.state !== 'NOT_CONFIGURED') {
                       console.log('[EVO_API_JS] State data found in get_status response, calling updateInstanceDetails.');
                       const details = result.data.details || {};
                       const instanceDetails = details.instance || {};
                       updateInstanceDetails(
                           result.data.state,
                           instanceDetails.profileName,
                           instanceDetails.owner,
                           instanceDetails.profilePictureUrl
                       );
                       // Mostrar mensaje específico de estado actualizado en el área general
                        displayStatusMessage(i18n.statusRefreshed || 'Estado actualizado.', 'info');
                        // Reiniciar timer después de actualizar estado
                        startStatusRefresh();
                   }
   
                   // --- INICIO MODIFICACIÓN: Manejo post-creación (SIN RECARGA) ---
                   if (action === 'pos_evolution_create_instance') {
                        console.log('[EVO_API_JS] Instance created. Showing success message.');
                        // Guardar el QR y nombre de instancia de la respuesta original
                        const createdInstanceName = result.data.instance_name;
                        const createdQrData = result.data.qr_base64; // Puede ser null si ya estaba conectada
   
                        Swal.fire({
                            title: i18n.createSuccessTitle || '¡Éxito!',
                            text: result.data.message || i18n.createSuccessText || 'Instancia creada.',
                            icon: 'success',
                            confirmButtonText: 'OK' // Botón para cerrar manualmente
                        }).then(() => { // Ejecutar después de que el usuario cierre el SweetAlert
                            console.log('[EVO_API_JS] SweetAlert closed after instance creation.');
                            // Actualizar la UI al modo de gestión
                            // Usar un estado temporal o el que devuelva la API si es más preciso
                            const initialState = (result.data.details && result.data.details.instance && result.data.details.instance.state) ? result.data.details.instance.state : 'CREATED';
                            updateInstanceDetails(initialState, '-', '-', null);
   
                            // Actualizar el nombre de instancia global para futuras acciones
                            if (createdInstanceName) {
                                instanceName = createdInstanceName; // Actualizar variable JS
                                // Actualizar input oculto
                                const nameInput = document.getElementById('managed-instance-name-input');
                                if (nameInput) nameInput.value = createdInstanceName;
                                // Actualizar display del nombre
                                const nameDisplay = document.getElementById('managed-instance-name-display');
                                if (nameDisplay) nameDisplay.textContent = createdInstanceName;
   
                                console.log(`[EVO_API_JS] Updated instanceName to: ${instanceName}`);
                            }
   
                            // Volver a mostrar el QR si se recibió al crear
                            if (createdQrData) {
                                console.log('[EVO_API_JS] Re-displaying QR code after SweetAlert close.');
                                displayQrCode(createdQrData); // Esta función detiene el timer
                            } else {
                                // Si no hubo QR (ej. ya estaba conectada), iniciar el refresco de estado normal
                                console.log('[EVO_API_JS] No QR on creation, starting status refresh.');
                                startStatusRefresh();
                            }
                        });
                   }
                   // --- FIN MODIFICACIÓN ---
                   // --- INICIO MODIFICACIÓN: Manejo post-eliminación (SIN RECARGA) ---
                   else if (action === 'pos_evolution_delete_instance') {
                       console.log('[EVO_API_JS] Instance deleted. Showing success message.'); // <-- Actualizar log si quieres
                       Swal.fire({
                           title: i18n.deleteSuccessTitle || '¡Eliminada!',
                           text: result.data.message || i18n.deleteSuccessText || 'Instancia eliminada.', // <-- Quitar "La página se recargará."
                           icon: 'success',
                           confirmButtonText: 'OK'
                       }).then(() => { // <-- AÑADIR ESTE BLOQUE .then()
                           console.log('[EVO_API_JS] SweetAlert closed after instance deletion. Resetting UI.');
                           // Llamar a la función para reiniciar la UI dinámicamente
                           resetInstanceManagerUI(i18n.configResetMessage || 'La configuración local ha sido reiniciada.', 'info');
                       });
                   }
                   // --- FIN MODIFICACIÓN ---
   
               } else {
                   // --- ERROR DEL BACKEND (result.success es false) ---
                   const errorMessage = result.data || i18n.errorUnknown || 'Ocurrió un error desconocido.';
                   logActivity(errorMessage, 'ERROR'); // Log de actividad
                   console.error('[EVO_API_JS] AJAX call failed (result.success is false). Error data:', result.data);
                   displayStatusMessage(errorMessage, 'error');
                   Swal.fire({ icon: 'error', title: i18n.errorTitle || 'Error', text: errorMessage });
                   // Reiniciar timer incluso si hubo error del backend
                   startStatusRefresh();
               }
               return result; // Devolver el resultado para posibles cadenas .then
           })
           .catch(error => {
               // --- ERROR DE RED O EXCEPCIÓN ---
               const errorMsgString = (error && error.message) ? String(error.message) : 'Unknown fetch error';
               logActivity(`Error de red/servidor: ${errorMsgString}`, 'ERROR'); // Log de actividad
               console.error('[EVO_API_JS] AJAX fetch failed (Network error or exception):', error);
               hideLoading(button); // Ocultar spinner
               // Asegurarse de desmarcar refresco incluso si hay error
               if (action === 'pos_evolution_get_status') isRefreshingStatus = false;
               const errorMessage = `${i18n.errorNetwork || 'Error de red o de servidor:'} ${errorMsgString}`;
               displayStatusMessage(errorMessage, 'error');
               Swal.fire({ icon: 'error', title: i18n.errorConnection || 'Error de Conexión', text: errorMessage });
               // Reiniciar timer después de error de red
               startStatusRefresh();
               return { success: false, data: errorMessage }; // Devolver un resultado de error estructurado
           });
       } // Fin de performAjaxAction
   

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
            // console.log('[EVO_API_JS] Delete button clicked.');
            const deleteHtml = `${i18n.deleteText1 || '¡Esta acción es irreversible! Se eliminará la instancia'} '<strong>${instanceName}</strong>' ${i18n.deleteText2 || 'del servidor Evolution API.'}<br><br>${i18n.deleteConfirmPrompt || 'Escribe el nombre de la instancia para confirmar:'}`;
            Swal.fire({
                title: i18n.deleteTitle || '¿Eliminar Instancia?',
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
