/* global jQuery, posBaseTourData */
(function($) {
    'use strict';

    // Variable global para almacenar el índice actual (accesible en los listeners)
    var currentTourIndex = 0;
    // Variable global para almacenar los datos de los pointers
    window.pointers = {};
    // Variable global para almacenar las claves (IDs) de los pointers
    window.pointerKeys = [];


    // Función para abrir un pointer específico
    function openPointer(index, pointerData) {
        currentTourIndex = index; // Actualizar índice global

        // --- INICIO: DEFINICIÓN DE OPTIONS (Simplificada) ---
        // Ya no necesitamos la lógica compleja en el callback 'buttons'
        var options = $.extend({}, pointerData.options, { // Usar copia para no modificar original
            buttons: function(event, t) {
                // Callback mínimo, ya que la lógica principal estará en los listeners directos.
                // Podríamos dejarlo vacío o solo con un log si wp-pointer lo requiere.
                console.log('WP Pointer Internal Button Callback Triggered (Event:', event, ') - Logic handled by direct listeners.');
                // NO añadir lógica de avance/cierre aquí.
            },
            close: function() {
                 console.log('POS Tour: Close callback executed (likely via X button).');
                 // Aquí sí podríamos llamar a dismissTour() si se cierra con la X
                 // dismissTour(true); // true indica cierre manual
            }
        });
        // --- FIN: DEFINICIÓN DE OPTIONS ---

        // Verificar si el elemento target existe en la página
        var $targetElement = $(pointerData.target);
        if ($targetElement.length === 0) {
            console.warn('POS Tour: Target element not found for step:', pointerData.target);
            var nextIndex = index + 1;
            // Asegurarse de que pointerKeys está definido antes de usarlo
            if (window.pointerKeys && nextIndex < window.pointerKeys.length) {
                setTimeout(function() {
                    // Asegurarse de que pointers está definido
                    if (window.pointers) {
                        openPointer(nextIndex, window.pointers[window.pointerKeys[nextIndex]]);
                    }
                }, 500);
            }
            return;
        }

        // Ajustar el texto del botón 'Siguiente' si es el último paso
        // Asegurarse de que pointerKeys está definido
        if (window.pointerKeys && index === window.pointerKeys.length - 1) {
            $.each(options.buttons, function(i, button) {
                if (button.name === 'next') {
                    button.label = (typeof posBaseTourData !== 'undefined' && posBaseTourData.i18n && posBaseTourData.i18n.finish) ? posBaseTourData.i18n.finish : 'Finalizar';
                    return false;
                }
            });
        }

        // Destruir instancia anterior si existe
        if ($targetElement.data('wpPointer')) {
             console.log('POS Tour: Destroying previous pointer instance for', pointerData.target);
             $targetElement.pointer('destroy');
        }

        // Adjuntar listener UNA SOLA VEZ para el evento 'pointeropen'
        // Este se disparará DESPUÉS de que el pointer se abra
        $targetElement.one('pointeropen', function() {
            console.log('POS Tour: Evento pointeropen detectado para', pointerData.target);

            // --- INICIO: BÚSQUEDA ROBUSTA DEL WIDGET ---
            var $openedPointerWidget = null;
            // Intento 1: Usar la referencia $.data (como antes)
            var pointerInstance = $(this).data('wpPointer');
            if (pointerInstance && pointerInstance.widget && pointerInstance.widget.length) {
                $openedPointerWidget = pointerInstance.widget;
                console.log('POS Tour: Widget encontrado via $.data');
            } else {
                // Intento 2: Buscar el widget visible en el DOM (asumiendo solo uno abierto)
                // Los pointers abiertos tienen la clase 'wp-pointer-open'
                // Esperar un ciclo más por si acaso
                setTimeout(function() {
                    $openedPointerWidget = $('.wp-pointer.wp-pointer-open');
                    if ($openedPointerWidget.length) {
                        console.log('POS Tour: Widget encontrado via búsqueda DOM (.wp-pointer-open) después de timeout(0)');
                        attachFunctionalListeners($openedPointerWidget, $targetElement); // Llamar a la función para adjuntar listeners
                    } else {
                         console.error('POS Tour: No se pudo encontrar el widget del pointer ni por $.data ni por búsqueda DOM (incluso con timeout).');
                         // Loguear el estado del DOM para depuración manual si es necesario
                         // console.log('DOM Body:', $('body').html());
                    }
                }, 0); // Timeout de 0ms
                return; // Salir del handler 'pointeropen' por ahora, la lógica se ejecutará en el timeout
            }
            // --- FIN: BÚSQUEDA ROBUSTA DEL WIDGET ---

            // Si se encontró el widget vía $.data, adjuntar listeners inmediatamente
            if ($openedPointerWidget) {
                attachFunctionalListeners($openedPointerWidget, $targetElement);
            }

        }); // Fin del handler para 'pointeropen'

        // Abrir el pointer
        $targetElement.pointer(options).pointer('open');
        console.log('POS Tour: Abriendo paso ' + (index + 1) + ' apuntando a ' + pointerData.target + ' (Esperando evento pointeropen)');

    } // Fin de openPointer


    // --- INICIO: Función separada para adjuntar listeners ---
    function attachFunctionalListeners($widget, $targetEl) {
        var $pointerContent = $widget.find('.wp-pointer-content');

        if ($pointerContent.length) {
             console.log('POS Tour: Pointer content found. Attaching functional click listeners...');

             // --- Listener para el botón Siguiente/Finalizar ---
             $pointerContent.find('.pos-tour-next-btn')
                .off('click.posTourFunctional')
                .on('click.posTourFunctional', function(e) {
                    e.preventDefault(); e.stopPropagation();
                    console.log('>>> POS Tour: Click funcional en NEXT/FINALIZAR detectado!');
                    var currentIndex = currentTourIndex;
                    var nextIndex = currentIndex + 1;
                    // Usar el target original para cerrar, es más fiable
                    $targetEl.pointer('close');
                    // Asegurarse de que pointerKeys y pointers están definidos
                    if (window.pointerKeys && window.pointers && nextIndex < window.pointerKeys.length) {
                        console.log('POS Tour: Abriendo siguiente paso:', nextIndex);
                        setTimeout(function(){ openPointer(nextIndex, window.pointers[window.pointerKeys[nextIndex]]); }, 150);
                    } else {
                        console.log('POS Tour: Tour finalizado.');
                        // dismissTour();
                    }
             });

             // --- Listener para el botón Cerrar Tour ---
             $pointerContent.find('.pos-tour-close-btn')
                .off('click.posTourFunctional')
                .on('click.posTourFunctional', function(e) {
                    e.preventDefault(); e.stopPropagation();
                    console.log('>>> POS Tour: Click funcional en CERRAR TOUR detectado!');
                    $targetEl.pointer('close');
                    // dismissTour(true);
             });

             // --- Listener para el botón 'X' de cierre ---
             $widget.find('.close')
                .off('click.posTourFunctional')
                .on('click.posTourFunctional', function(e){
                     e.preventDefault(); e.stopPropagation();
                     console.log('>>> POS Tour: Click funcional en CLOSE (X) detectado!');
                     $targetEl.pointer('close');
                     // dismissTour(true);
                });

        } else {
             console.warn('POS Tour DEBUG: Could not find .wp-pointer-content within widget.');
        }
    }
    // --- FIN: Función separada para adjuntar listeners ---


    // Función para marcar el tour como visto (implementación futura con AJAX)
    /*
    function dismissTour(manualClose = false) {
        // ... (Lógica AJAX para guardar user meta) ...
    }
    */

    // --- Ejecución Principal ---
    $(document).ready(function() {
        if (typeof posBaseTourData === 'undefined' || typeof posBaseTourData.pointers === 'undefined') {
            console.log('POS Tour: No tour data found.');
            return;
        }

        // Asignar a variables globales
        window.pointers = posBaseTourData.pointers;
        window.pointerKeys = Object.keys(window.pointers);

        if (window.pointerKeys.length > 0) {
            // --- Lógica para NO iniciar si ya se vio (implementación futura) ---
            // if (posBaseTourData.isDismissed) {
            //     console.log('POS Tour: Tour previously dismissed.');
            //     return;
            // }
            // --- Fin lógica descarte ---

            // Iniciar el tour
            setTimeout(function() {
                // Asegurarse de que pointers y pointerKeys están disponibles
                if (window.pointers && window.pointerKeys && window.pointerKeys.length > 0) {
                    openPointer(0, window.pointers[window.pointerKeys[0]]);
                }
            }, 800); // Retraso inicial
        }
    });

}(jQuery));
