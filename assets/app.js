/**
 * POS Streaming Application Logic
 * Version: 1.1.0 (Tabs Functionality)
 */
jQuery(function ($) {
    'use strict';
    // Log inicial para verificar si los parámetros de PHP están disponibles
    console.log('DEBUG: posBaseParams:', typeof posBaseParams !== 'undefined' ? posBaseParams : '¡NO DEFINIDO!');

    // --- Cache de Selectores DOM ---
    // Pestañas (Tabs) <-- NUEVO
    const $tabWrapper = $('.nav-tab-wrapper'); // Contenedor de las pestañas
    const $tabContents = $('.pos-tab-content'); // Todos los paneles de contenido

    // Productos y Carrito
    const productSearchInput = $('#pos-product-search');
    const productListContainer = $('#pos-product-list');
    const cartItemsContainer = $('#pos-cart-items');
    const cartSubtotalAmount = $('#pos-cart-subtotal-amount');
    const cartDiscountRow = $('#pos-cart-discount-row');
    const cartDiscountAmount = $('#pos-cart-discount-amount');
    const cartTotalAmount = $('#pos-cart-total-amount');
    const completeSaleButton = $('#pos-complete-sale-button');

    // Cliente
    const $appWrapper = $('#pos-streaming-app-wrapper');
    const $customerSearchInput = $('#pos-customer-search-input');
    const $customerSearchResults = $('#pos-customer-search-results');
    const $customerModalContent = $('#pos-customer-modal-content');
    const $customerForm = $customerModalContent.find('#pos-customer-details');
    const $customerFormTitle = $customerForm.find('#pos-customer-form-title');
    const $customerIdInput = $customerForm.find('#pos-customer-id');
    const $customerFirstNameInput = $customerForm.find('#pos-customer-first-name');
    const $customerLastNameInput = $customerForm.find('#pos-customer-last-name');
    const $customerEmailInput = $customerForm.find('#pos-customer-email');
    const $customerPhoneInput = $customerForm.find('#pos-customer-phone');
    const $customerAvatarPreview = $customerForm.find('#pos-customer-avatar-preview');
    const $customerAvatarIdInput = $customerForm.find('#pos-customer-avatar-id');
    const $changeAvatarBtn = $customerForm.find('#pos-change-avatar-btn');
    const $removeAvatarBtn = $customerForm.find('#pos-remove-avatar-btn');
    const $saveCustomerBtn = $customerForm.find('#pos-save-customer-btn');
    const $cancelCustomerBtn = $customerForm.find('#pos-cancel-customer-btn');
    const $customerFormFeedback = $customerForm.find('#pos-customer-form-feedback');
    const $addNewCustomerBtn = $('#pos-add-new-customer-btn');
    const $editCustomerBtn = $('#pos-edit-customer-btn');
    const $selectedCustomerInfo = $('#pos-selected-customer-info');
    const $selectedCustomerAvatar = $('#selected-customer-avatar');
    const $selectedCustomerName = $('#selected-customer-name');
    const $changeCustomerBtn = $('#pos-change-customer-btn');
    const $customerSearchSection = $('.pos-customer-search');
    const $customerNoteInput = $customerForm.find('#pos-customer-note');

    // Checkout & Pago
    const $saleTypeSelect = $('#pos-sale-type');
    const $subscriptionFields = $('#pos-subscription-fields');
    const $subscriptionTitle = $('#pos-subscription-title');
    const $subscriptionExpiryDate = $('#pos-subscription-expiry-date');
    const $subscriptionColor = $('#pos-subscription-color');
    const $paymentMethodSelect = $('#pos-payment-method');
    const $couponCodeInput = $('#pos-coupon-code');
    const $applyCouponButton = $('#pos-apply-coupon-button');
    const $couponMessage = $('#pos-coupon-message');
    const $orderNoteInput = $('#pos-order-note-input');
    const $saleDateInput = $('#pos-sale-date');

    // Calendario
    let calendar = null; // Variable para guardar la instancia del calendario

    // Ventas (DataTables) <-- NUEVO
    let salesDataTable = null; // Variable para guardar la instancia de DataTables


    // --- Estado de la Aplicación ---
    // Carrito y Productos
    let cart = [];
    let isLoadingProducts = false;
    let isLoadingCartAction = false;
    let currentSearchTerm = '';
    let isCurrentlyFeatured = true;
    let currentPage = 1;
    let productDebounceTimer;
    let priceDebounceTimer;
    let appliedCoupon = null
    let isLoadingCouponAction = false

    // Cliente
    let customerDebounceTimer;
    let iti = null;
    let mediaFrame = null;
    let currentCustomerId = null;
    let isLoadingCustomerAction = false;

    // Checkout
    let isLoadingCheckoutAction = false; // Para evitar completar venta múltiple

    // --- Constantes y Configuración ---
    const DEBOUNCE_DELAY = 500;
    const PRODUCTS_PER_PAGE = 50;
    const PRICE_DEBOUNCE_DELAY = 400;

    // --- Funciones ---

    /**
     * Maneja el clic en una pestaña de navegación.
     */
    function handleTabClick(event) {
        event.preventDefault(); // Evitar salto de página por el href="#"

        const $clickedTab = $(event.currentTarget); // El <a> que se clickeó
        const targetTabId = $clickedTab.data('tab'); // Obtener 'pos', 'calendar', o 'sales'

        if ($clickedTab.hasClass('nav-tab-active')) {
            return; // No hacer nada si ya está activa
        }

        console.log(`Tab clicked: ${targetTabId}`);

        // 1. Actualizar clases de las pestañas
        $tabWrapper.find('.nav-tab').removeClass('nav-tab-active'); // Quitar activo de todas
        $clickedTab.addClass('nav-tab-active'); // Poner activo en la clickeada

        // 2. Mostrar/Ocultar contenido (usando clase 'active' y CSS)
        $tabContents.removeClass('active'); // Quitar clase 'active' de todos los contenidos
        const $targetContent = $(`#pos-tab-${targetTabId}`); // Seleccionar el contenido por ID
        $targetContent.addClass('active'); // Añadir clase 'active' al contenido correcto (CSS lo mostrará)

        // 3. Refrescar componentes si es necesario (ej: Calendario)
        if (targetTabId === 'calendar' && calendar) {
            // FullCalendar puede necesitar recalcular su tamaño si estaba oculto
            console.log('Refrescando tamaño del calendario...');
            // Usamos un pequeño timeout para asegurar que el contenedor es visible antes de actualizar
            setTimeout(() => {
                if (calendar && typeof calendar.updateSize === 'function') {
                    calendar.updateSize();
                }
            }, 50);
        } else if (targetTabId === 'sales' && salesDataTable) {
            // DataTables puede necesitar reajustar las columnas si estaba oculto
            console.log('Ajustando columnas de DataTables...');
            setTimeout(() => {
                // Asegurarse de que la instancia todavía existe
                if (salesDataTable && typeof salesDataTable.columns === 'function') {
                    // 1. Ajustar las columnas primero
                    salesDataTable.columns.adjust();

                    // 2. Luego, recalcular la responsividad (si la extensión está activa)
                    // Comprobar si la extensión Responsive está disponible antes de llamarla
                    if (typeof salesDataTable.responsive === 'object' && typeof salesDataTable.responsive.recalc === 'function') {
                        salesDataTable.responsive.recalc();
                    } else {
                        // Opcional: Advertir si la extensión no está cargada/activa
                        console.warn('DataTables Responsive extension no parece estar activa.');
                    }
                }
            }, 50); // El timeout sigue siendo buena idea
        }
    }

    // --- Funciones de Productos y Carrito (Existentes y Modificadas) ---
    function showMessage(container, message, type = 'info') {
        container.html(`<p class="message-feedback ${type}">${message}</p>`);
    }

    async function fetchProducts(searchTerm = '', page = 1, featuredOnly = false) {
        if (typeof posBaseParams === 'undefined' || !posBaseParams.rest_url || !posBaseParams.nonce) {
            console.error('Error Crítico: posBaseParams no está definido o incompleto.');
            showMessage(productListContainer, 'Error de configuración. Contacta al administrador.', 'error');
            return;
        }
        if (isLoadingProducts) return;
        isLoadingProducts = true;
        currentSearchTerm = searchTerm;
        isCurrentlyFeatured = featuredOnly && !searchTerm;
        currentPage = page;
        showMessage(productListContainer, posBaseParams.i18n?.loading || 'Cargando...', 'loading');
        const params = new URLSearchParams({ page: page, per_page: PRODUCTS_PER_PAGE });
        if (searchTerm) params.append('search', searchTerm);
        if (featuredOnly && !searchTerm) params.append('featured', 'true');
        const apiUrl = `${posBaseParams.rest_url}products?${params.toString()}`;
        console.log('API Call (Products):', apiUrl);
        try {
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': posBaseParams.nonce }
            });
            let responseBodyText = await response.text();
            if (!response.ok) {
                let errorMsg = posBaseParams.i18n?.error_general || 'Ocurrió un error inesperado.';
                try { const errorData = JSON.parse(responseBodyText); errorMsg = errorData.message || errorMsg; } catch (e) { console.error("Respuesta no JSON:", responseBodyText); }
                throw new Error(`${errorMsg} (Status: ${response.status})`);
            }
            const products = JSON.parse(responseBodyText);
            renderProducts(products);
        } catch (error) {
            console.error('Error en fetchProducts:', error);
            showMessage(productListContainer, `${posBaseParams.i18n?.error_general || 'Error'}: ${error.message}`, 'error');
        } finally {
            isLoadingProducts = false;
        }
    }

    function renderProducts(products) {
        // Log para depuración: Muestra los datos crudos recibidos de la API
        console.log("Datos recibidos para renderizar productos:", JSON.stringify(products));
    
        productListContainer.empty(); // Limpiar contenedor antes de añadir nuevos productos
    
        // Validar que la respuesta sea un array
        if (!Array.isArray(products)) {
            console.error("La respuesta de la API no es un array:", products);
            showMessage(productListContainer, posBaseParams.i18n?.error_api_response || 'Respuesta inesperada de la API.', 'error');
            return;
        }
    
        // Mostrar mensaje si no hay productos
        if (products.length === 0) {
            const msg = isCurrentlyFeatured
                ? (posBaseParams.i18n?.no_featured_products_found || 'No hay productos destacados disponibles.')
                : (posBaseParams.i18n?.no_products_found || 'No se encontraron productos para la búsqueda.');
            showMessage(productListContainer, msg, 'info');
            return;
        }
    
        // Añadir título a la lista (Destacados o Resultados de búsqueda)
        const listTitle = isCurrentlyFeatured
            ? (posBaseParams.i18n?.featured_products_title || 'Productos Destacados')
            : (currentSearchTerm
                ? `${posBaseParams.i18n?.search_results_title || 'Resultados para'} "${currentSearchTerm}"`
                : (posBaseParams.i18n?.all_products_title || 'Todos los Productos')
                );
        productListContainer.append(`<h3 class="pos-product-list-title">${listTitle}</h3>`);
    
        // Iterar sobre cada producto y crear su elemento HTML
        products.forEach((product, index) => {
            // Validar datos básicos del producto
            if (!product || typeof product !== 'object' || !product.id) {
                console.warn(`[Índice ${index}] Producto inválido o incompleto recibido:`, product);
                return; // Saltar este producto si es inválido
            }
    
            // Determinar tipo de producto y si tiene variaciones válidas
            const isVariable = product.type === 'variable' && Array.isArray(product.variations) && product.variations.length > 0;
            const isSimple = product.type === 'simple';
    
            // Determinar estado de stock para productos simples
            const simpleStockStatus = isSimple ? product.stock_status : 'N/A'; // 'N/A' o similar si no es simple
            const isSimpleInStock = isSimple && simpleStockStatus === 'instock';
    
            // --- Generar HTML para las variaciones (si es producto variable) ---
            let variationsHtml = '';
            if (isVariable) {
                variationsHtml = '<ul class="product-variations-list">';
                product.variations.forEach(variation => {
                    // Validar datos de la variación
                    if (!variation || typeof variation !== 'object' || !variation.variation_id) {
                        console.warn(`[Producto ID ${product.id}] Variación inválida recibida:`, variation);
                        return; // Saltar esta variación si es inválida
                    }
    
                    const variationId = variation.variation_id;
                    const isVariationInStock = variation.stock_status === 'instock';
    
                    // Texto de stock para la variación
                    const stockText = isVariationInStock
                        ? (variation.stock_quantity !== null ? `${posBaseParams.i18n?.stock || 'Stock'}: ${variation.stock_quantity}` : (posBaseParams.i18n?.instock || 'En stock'))
                        : (posBaseParams.i18n?.outofstock || 'Agotado');
    
                    // Texto de precio para la variación (usar price_html si existe, sino formatear price)
                    // Asegurarse de que el precio sea un número antes de formatear
                    const variationPrice = parseFloat(variation.price);
                    const priceText = variation.price_html || (!isNaN(variationPrice) ? variationPrice.toFixed(2) : (posBaseParams.i18n?.price_na || 'N/A'));
    
                    // Nombre de la variación (atributos)
                    let variationName = '';
                    if (variation.attributes && typeof variation.attributes === 'object') {
                        variationName = Object.values(variation.attributes).join(' / ');
                    }
                    variationName = variationName || (posBaseParams.i18n?.variation || 'Variación'); // Fallback
    
                    // Construir el HTML del item de la variación
                    variationsHtml += `
                        <li class="product-variation-item ${isVariationInStock ? 'instock' : 'outofstock'}" data-variation-id="${variationId}">
                            <span class="variation-details">
                                <span class="variation-name">${variationName}</span>
                                ${variation.sku ? `<span class="variation-sku">(SKU: ${variation.sku})</span>` : ''}
                                - <span class="variation-price">${priceText}</span>
                            </span>
                            <span class="variation-stock-status">${stockText}</span>
                            <span class="variation-actions">
                                <button type="button" class="button button-small add-variation-to-cart"
                                        data-product-id="${product.id}" data-variation-id="${variationId}"
                                        ${!isVariationInStock ? 'disabled' : ''}
                                        title="${posBaseParams.i18n?.add_variation_to_cart || 'Añadir'} ${variationName}">
                                    ${posBaseParams.i18n?.add_to_cart || 'Añadir'}
                                </button>
                            </span>
                        </li>`;
                });
                variationsHtml += '</ul>';
            } // Fin de la generación de HTML de variaciones
    
            // --- Generar HTML para las acciones principales (botón Añadir para simples, texto para variables) ---
            let actionHtml = '';
            if (isSimple) {
                actionHtml = `
                    <button type="button" class="button button-primary add-simple-to-cart"
                            data-product-id="${product.id}" ${!isSimpleInStock ? 'disabled' : ''}
                            title="${posBaseParams.i18n?.add_product_to_cart || 'Añadir'} ${product.name || 'producto'}">
                        ${posBaseParams.i18n?.add_to_cart || 'Añadir'}
                    </button>`;
            } else if (isVariable) {
                actionHtml = `<span class="select-variation-label">${posBaseParams.i18n?.select_variation || 'Selecciona opción:'}</span>`;
            }
            // Podríamos añadir un 'else' para otros tipos de producto si fuera necesario
    
            // --- Formatear precio principal (para simples y variables) ---
            // Usar product.price que la API envía (debería ser el precio normal o el mínimo de variación)
            const mainPriceValue = parseFloat(product.price);
            const mainPriceText = !isNaN(mainPriceValue) ? mainPriceValue.toFixed(2) : (posBaseParams.i18n?.price_na || 'N/A');
    
            // Texto de stock para productos simples
            const simpleStockText = isSimpleInStock
                ? (product.stock_quantity !== null ? `${posBaseParams.i18n?.stock || 'Stock'}: ${product.stock_quantity}` : (posBaseParams.i18n?.instock || 'En stock'))
                : (posBaseParams.i18n?.outofstock || 'Agotado');
    
            // --- Construir el HTML completo del item del producto ---
            const productItem = $(`
                <div class="pos-product-item
                            ${isVariable ? 'product-type-variable' : (isSimple ? 'product-type-simple' : 'product-type-other')}
                            ${!isVariable && !isSimpleInStock ? 'product-outofstock' : ''}"
                    data-product-id="${product.id}">
    
                    <div class="product-main-info">
                        <img src="${product.image_url || posBaseParams.placeholder_image_url || ''}"
                            alt="${product.name || ''}" class="pos-product-thumbnail">
    
                        <div class="product-details">
                            <strong class="product-name">${product.name || (posBaseParams.i18n?.unnamed_product || 'Sin nombre')}</strong>
                            <div class="product-meta">
                                ${product.sku ? `<span class="product-sku">SKU: ${product.sku}</span>` : ''}
    
                                <!-- ***** LÍNEA MODIFICADA ***** -->
                                ${isSimple ?
                                    `<span class="product-price">${posBaseParams.i18n?.price_label || 'Precio'}: ${mainPriceText}</span>` :
                                    (isVariable ?
                                        `<span class="product-price">${posBaseParams.i18n?.price_from_label || 'Desde'}: ${mainPriceText}</span>` :
                                        '' // No mostrar precio principal si no es simple ni variable
                                    )
                                }
                                <!-- ***** FIN LÍNEA MODIFICADA ***** -->
    
                                ${isSimple ? `<span class="product-stock-status stock-${simpleStockStatus}">${simpleStockText}</span>` : ''}
                            </div>
                        </div>
    
                        <div class="product-actions">
                            ${actionHtml}
                        </div>
                    </div>
    
                    ${variationsHtml} <!-- Aquí se inserta la lista de variaciones si existen -->
    
                </div>
            `);
    
            // Guardar los datos completos del producto en el elemento DOM para fácil acceso posterior
            productItem.data('productData', product);
    
            // Añadir el elemento del producto al contenedor en la página
            productListContainer.append(productItem);
    
        }); // Fin del bucle forEach
    
    } // Fin de la función renderProducts
    

    function addToCart(itemData) {
        if (isLoadingCartAction) return;
        const sourcePrice = itemData.original_price !== undefined ? itemData.original_price : itemData.price;
        if (!itemData || !itemData.id || typeof sourcePrice === 'undefined') {
            console.error('addToCart: Datos inválidos o falta precio original.', itemData);
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Datos del producto inválidos.', 'error');
            return;
        }
        if (itemData.stock_status !== 'instock') {
            console.warn(`Intento añadir agotado: ${itemData.name}`);
            if (typeof Swal !== 'undefined') Swal.fire('Agotado', 'Producto no disponible.', 'warning');
            return;
        }
        isLoadingCartAction = true;
        const existingCartItemIndex = cart.findIndex(item => item.id === itemData.id);
        if (existingCartItemIndex > -1) {
            cart[existingCartItemIndex].quantity++;
            console.log(`Cantidad incrementada para item ${itemData.id}`);
        } else {
            const priceToAdd = parseFloat(sourcePrice) || 0;
            const newItem = {
                id: itemData.id, product_id: itemData.product_id, variation_id: itemData.variation_id,
                name: itemData.name, sku: itemData.sku,
                original_price: priceToAdd, current_price: priceToAdd,
                quantity: 1, image_url: itemData.image_url, type: itemData.type,
                stock_status: itemData.stock_status
            };
            cart.push(newItem);
            console.log(`Nuevo item añadido al carrito:`, newItem);
        }
        updateCartUI(); calculateTotals(); isLoadingCartAction = false;
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${itemData.name} añadido`, showConfirmButton: false, timer: 1500 });
        }
    }

    function removeFromCart(itemId) {
        if (isLoadingCartAction) return;
        isLoadingCartAction = true;
        const initialLength = cart.length;
        cart = cart.filter(item => item.id !== itemId);
        if (cart.length < initialLength) { updateCartUI(); calculateTotals(); }
        else { console.warn(`Item ID: ${itemId} no encontrado para eliminar.`); }
        isLoadingCartAction = false;
    }

    function updateCartUI() {
        cartItemsContainer.empty();
        if (cart.length === 0) {
            cartItemsContainer.html(`<li class="empty-cart">${posBaseParams.i18n?.cart_empty || 'El carrito está vacío.'}</li>`);
        } else {
            cart.forEach(item => {
                const itemSubtotal = (item.current_price * item.quantity).toFixed(2);
                const priceInputId = `pos-cart-item-price-${item.id}`;
                const cartItemHTML = `
                    <li data-item-id="${item.id}">
                        <span class="pos-cart-item-details">
                            <span class="qty">${item.quantity}</span> x ${item.name} ${item.sku ? `<small>(${item.sku})</small>` : ''}
                        </span>
                        <span class="pos-cart-item-price">
                            <input type="number" step="0.01" min="0" class="pos-cart-item-price-input" id="${priceInputId}" value="${item.current_price.toFixed(2)}" data-item-id="${item.id}" aria-label="Precio unitario editable">
                        </span>
                        <span class="pos-cart-item-subtotal">${itemSubtotal}</span>
                        <span class="pos-cart-item-remove" title="Eliminar item" data-remove-id="${item.id}">&times;</span>
                    </li>`;
                cartItemsContainer.append(cartItemHTML);
            });
        }
    }

    function calculateTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            const price = parseFloat(item.current_price) || 0;
            const quantity = parseInt(item.quantity, 10) || 0;
            subtotal += price * quantity;
        });

        let discount = 0;
        let discountType = '';

        // Calcular descuento del cupón
        if (appliedCoupon) {
            discountType = appliedCoupon.discount_type;
            const couponAmount = parseFloat(appliedCoupon.amount) || 0;

            if (discountType === 'percent') {
                discount = (subtotal * couponAmount) / 100;
            } else if (discountType === 'fixed_cart') {
                discount = Math.min(couponAmount, subtotal);
            } else if (discountType === 'fixed_product') {
                console.warn(`Cupón 'fixed_product' (${appliedCoupon.code}) tratado como 'fixed_cart' en POS.`);
                discount = Math.min(couponAmount, subtotal);
            }
            discount = Math.max(0, discount);
            console.log(`Descuento calculado (${discountType}): ${discount.toFixed(2)}`);
        }

        let total = subtotal - discount;
        total = Math.max(0, total); // El total no puede ser negativo

        updateTotalsUI(subtotal, discount, total);
    }

    function updateTotalsUI(subtotal, discount, total) {
        cartSubtotalAmount.text(subtotal.toFixed(2));
        if (discount > 0 && appliedCoupon) {
            cartDiscountAmount.html(`${discount.toFixed(2)} <small>(${appliedCoupon.code})</small>`);
            cartDiscountRow.show();
        } else {
            cartDiscountRow.hide();
            cartDiscountAmount.empty();
        }
        cartTotalAmount.text(total.toFixed(2));
        updateCheckoutButtonState();
    }

    function showCouponMessage(message, isError = false) {
        $couponMessage.html(message)
            .removeClass('success error')
            .addClass(isError ? 'error' : 'success')
            .show();
    }

    function validateCouponAPI(couponCode) {
        const url = `${posBaseParams.rest_url}coupons/validate`;
        console.log(`API Call (Validate Coupon): POST ${url}`);
        isLoadingCouponAction = true;
        $applyCouponButton.prop('disabled', true).text(posBaseParams.i18n?.validating || 'Validando...');

        return $.ajax({
            url: url,
            method: 'POST',
            beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce),
            contentType: 'application/json',
            data: JSON.stringify({ code: couponCode })
        }).always(() => {
            isLoadingCouponAction = false;
            $applyCouponButton.prop('disabled', false).text(posBaseParams.i18n?.apply || 'Aplicar');
        });
    }

    function handleApplyCoupon() {
        if (isLoadingCouponAction) return;

        const couponCode = $couponCodeInput.val().trim();
        if (!couponCode) {
            showCouponMessage(posBaseParams.i18n?.coupon_code_required || 'Ingresa un código de cupón.', true);
            $couponCodeInput.focus();
            return;
        }

        $couponMessage.empty().hide();
        appliedCoupon = null;
        calculateTotals();

        validateCouponAPI(couponCode)
            .done(response => {
                console.log('Cupón válido:', response);
                appliedCoupon = response;
                const successMsg = posBaseParams.i18n?.coupon_applied_success || 'Cupón "%s" aplicado.';
                showCouponMessage(
                    `<span>${successMsg.replace('%s', `<strong>${response.code}</strong>`)}</span>
                     <button type="button" class="button-link pos-remove-coupon-button" title="${posBaseParams.i18n?.remove_coupon || 'Quitar cupón'}">&times;</button>`,
                    false
                );
                $couponCodeInput.prop('disabled', true);
                $applyCouponButton.hide();
                calculateTotals();
            })
            .fail(error => {
                console.error('Error validando cupón:', error);
                const errorMsg = error?.responseJSON?.message || posBaseParams.i18n?.coupon_invalid || 'Cupón inválido.';
                showCouponMessage(errorMsg, true);
                appliedCoupon = null;
                calculateTotals();
                $couponCodeInput.prop('disabled', false).focus();
                $applyCouponButton.show();
            });
    }

    function handleRemoveCoupon() {
        console.log('Quitando cupón:', appliedCoupon?.code);
        appliedCoupon = null;
        $couponMessage.empty().hide();
        $couponCodeInput.val('').prop('disabled', false).focus();
        $applyCouponButton.show();
        calculateTotals();
    }

    function updateCartItemPrice(itemId, newPrice) {
        const itemIndex = cart.findIndex(item => item.id === itemId);
        if (itemIndex > -1) {
            cart[itemIndex].current_price = newPrice;
            console.log(`Precio actualizado para item ${itemId}:`, cart[itemIndex]);
            const subtotalElement = cartItemsContainer.find(`li[data-item-id="${itemId}"] .pos-cart-item-subtotal`);
            if (subtotalElement.length) {
                subtotalElement.text((newPrice * cart[itemIndex].quantity).toFixed(2));
            }
            calculateTotals();
        } else {
            console.error(`Item ID ${itemId} no encontrado para actualizar precio.`);
        }
    }

    function handleCartPriceInputChange(event) {
        clearTimeout(priceDebounceTimer);
        const input = $(event.currentTarget);
        const itemId = parseInt(input.data('item-id'), 10);
        const newPriceStr = input.val();
        const preliminaryPrice = parseFloat(newPriceStr);
        if (isNaN(preliminaryPrice) && newPriceStr !== '') {
             console.warn(`Entrada no numérica detectada para ${itemId}: ${newPriceStr}`);
             return;
        }
        priceDebounceTimer = setTimeout(() => {
            const finalPrice = parseFloat(newPriceStr);
            if (isNaN(finalPrice) || finalPrice < 0) {
                const itemIndex = cart.findIndex(item => item.id === itemId);
                if (itemIndex > -1) {
                    const originalPrice = cart[itemIndex].original_price;
                    input.val(originalPrice.toFixed(2));
                    console.warn(`Precio inválido/vacío ingresado para ${itemId}. Revertido a original: ${originalPrice.toFixed(2)}`);
                    updateCartItemPrice(itemId, originalPrice);
                }
                return;
            }
            console.log(`Debounce: Price change for item ${itemId} to ${finalPrice.toFixed(2)}`);
            updateCartItemPrice(itemId, finalPrice);
        }, PRICE_DEBOUNCE_DELAY);
    }

    function updateCheckoutButtonState() {
        const canCheckout = cart.length > 0 && currentCustomerId !== null;
        completeSaleButton.prop('disabled', !canCheckout);
        console.log(`Checkout button ${canCheckout ? 'enabled' : 'disabled'}. Cart items: ${cart.length}, Customer ID: ${currentCustomerId}`);
    }

    function handleAddSimpleClick(event) {
        const button = $(event.currentTarget); const productId = button.data('product-id');
        const productItemElement = button.closest('.pos-product-item'); const productData = productItemElement.data('productData');
        if (!productData) { console.error(`Datos no encontrados para simple ID: ${productId}`); if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudieron obtener datos.', 'error'); return; }
        const itemToAdd = { id: productData.id, product_id: productData.id, variation_id: null, name: productData.name, sku: productData.sku, price: parseFloat(productData.price) || 0, original_price: parseFloat(productData.price) || 0, image_url: productData.image_url, type: 'simple', stock_status: productData.stock_status };
        addToCart(itemToAdd);
    }

    function handleAddVariationClick(event) {
        const button = $(event.currentTarget);
        const productId = button.data('product-id');
        const variationId = button.data('variation-id');
        const productItemElement = button.closest('.pos-product-item');
        const productData = productItemElement.data('productData');
    
        if (!productData || !Array.isArray(productData.variations)) {
            console.error(`Datos/variaciones no encontrados para padre ID: ${productId}`);
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudieron obtener datos.', 'error');
            return;
        }
    
        const variationData = productData.variations.find(v => v.variation_id === variationId);
    
        if (!variationData) {
            console.error(`Datos no encontrados para variación ID: ${variationId}`);
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudieron obtener datos de variación.', 'error');
            return;
        }
    
        // --- INICIO CORRECCIÓN ---
        // Construir el nombre de la variación a partir de los atributos
        let variationNameSuffix = '';
        if (variationData.attributes && typeof variationData.attributes === 'object') {
            variationNameSuffix = Object.values(variationData.attributes).join(' / ');
        }
        variationNameSuffix = variationNameSuffix || (posBaseParams.i18n?.variation || 'Variación'); // Fallback
    
        const itemToAdd = {
            id: variationData.variation_id,
            product_id: productId,
            variation_id: variationData.variation_id,
            // Usar el nombre del producto padre + el sufijo de atributos calculado
            name: `${productData.name} - ${variationNameSuffix}`, // <-- CORREGIDO
            // Guardar también el nombre corto de la variación por si lo necesitas en renderCart
            variationName: variationNameSuffix, // <-- AÑADIDO (opcional pero útil)
            sku: variationData.sku,
            price: parseFloat(variationData.price) || 0,
            original_price: parseFloat(variationData.price) || 0,
            image_url: variationData.image_url || productData.image_url, // Usar imagen de variación o padre
            type: 'variation',
            stock_status: variationData.stock_status
        };
        // --- FIN CORRECCIÓN ---
    
        console.log("Añadiendo variación al carrito:", itemToAdd); // Log para verificar
        addToCart(itemToAdd);
    }
  
    function handleRemoveCartItemClick(event) {
        const removeButton = $(event.currentTarget); const itemIdToRemove = parseInt(removeButton.data('remove-id'), 10);
        if (!isNaN(itemIdToRemove)) { removeFromCart(itemIdToRemove); }
        else { console.error('ID inválido para eliminar:', removeButton.data('remove-id')); }
    }

    // --- Funciones de Cliente ---
    function showLoading(message = posBaseParams.i18n?.loading || 'Cargando...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: message, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        } else { console.log(message); }
    }

    function hideLoading() {
        if (typeof Swal !== 'undefined') { Swal.close(); }
    }

    function initializeIntlTelInput() {
        if (iti) { try { iti.destroy(); } catch(e) { console.warn("Error destroying iti:", e); } }
        const phoneInput = $customerPhoneInput[0];
        if (phoneInput && typeof intlTelInput !== 'undefined') {
            iti = intlTelInput(phoneInput, {
                utilsScript: posBaseParams.intlTelInputUtilsScript, initialCountry: "auto",
                geoIpLookup: callback => { callback("pe"); }, separateDialCode: true, nationalMode: false,
            });
            console.log('intl-tel-input initialized');
        } else { console.error('Phone input or intlTelInput library not found'); }
    }

    function resetCustomerForm() {
        $customerFormTitle.text(posBaseParams.i18n?.add_customer || 'Nuevo Cliente');
        $customerIdInput.val('');
        $customerFirstNameInput.val(''); $customerLastNameInput.val('');
        $customerEmailInput.val(''); $customerPhoneInput.val('');
        $customerAvatarIdInput.val('');
        $customerNoteInput.val(''); // <-- AÑADIR ESTA LÍNEA
        $customerAvatarPreview.attr('src', posBaseParams.default_avatar_url || '');
        $removeAvatarBtn.hide();
        $customerFormFeedback.hide().removeClass('notice-success notice-error').text('');
        if (iti) { try { iti.setNumber(''); } catch(e) { console.warn("Error resetting iti number:", e); } }
        console.log('Customer form reset (manual)');
    }

    function populateCustomerForm(customerData) {
        resetCustomerForm();
        $customerFormTitle.text(posBaseParams.i18n?.edit_customer || 'Editar Cliente');
        $customerIdInput.val(customerData.id);
        $customerFirstNameInput.val(customerData.first_name || '');
        $customerLastNameInput.val(customerData.last_name || '');
        $customerEmailInput.val(customerData.email || '');
        if (iti && customerData.phone) { 
            iti.setNumber(customerData.phone); 
        } else { 
            $customerPhoneInput.val(customerData.phone || ''); 
        }
        $customerNoteInput.val(customerData.note || ''); // <-- AÑADIR ESTA LÍNEA
        $customerAvatarIdInput.val(customerData.avatar_id || '');
        $customerAvatarPreview.attr('src', customerData.avatar_url || posBaseParams.default_avatar_url || '');
        if (customerData.avatar_id && customerData.avatar_url !== posBaseParams.default_avatar_url) { $removeAvatarBtn.show(); }
        else { $removeAvatarBtn.hide(); }
        console.log('Customer form populated for ID:', customerData.id);
    }

    function showCustomerFormFeedback(message, isError = false) {
        $customerFormFeedback.text(message)
            .removeClass(isError ? 'notice-success' : 'notice-error')
            .addClass(isError ? 'notice-error' : 'notice-success').slideDown();
    }

    function updateSelectedCustomerDisplay(customerData) {
        if (customerData && customerData.id) {
            currentCustomerId = customerData.id;
            const displayName = `${customerData.first_name || ''} ${customerData.last_name || ''}`.trim();
            $selectedCustomerName.text(displayName || (posBaseParams.i18n?.anonymous || 'Invitado'));
            $selectedCustomerAvatar.attr('src', customerData.avatar_url || posBaseParams.default_avatar_url || '');
            $selectedCustomerInfo.show(); $customerSearchSection.hide(); $customerSearchResults.hide().empty();
        } else { handleDeselectCustomer(); }
        updateCheckoutButtonState();
    }

    function handleOpenNewCustomerModal(event) {
        console.log('Opening modal for new customer...');
        resetCustomerForm(); setTimeout(initializeIntlTelInput, 150);
    }

    function handleOpenEditCustomerModal() {
        if (!currentCustomerId || isLoadingCustomerAction) { console.warn("No customer selected or action in progress."); return; }
        console.log('Opening modal to edit customer ID:', currentCustomerId);
        isLoadingCustomerAction = true; showLoading(posBaseParams.i18n?.loading_customer_data || 'Cargando datos...');
        getCustomerData(currentCustomerId)
            .done(customerData => {
                hideLoading(); populateCustomerForm(customerData);
                if (typeof tb_show !== 'undefined') {
                    tb_show(posBaseParams.i18n?.edit_customer || 'Editar Cliente', '#TB_inline?width=600&height=350&inlineId=pos-customer-modal-content', null);
                    setTimeout(initializeIntlTelInput, 150);
                } else { console.error('Thickbox (tb_show) no está definido.'); if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudo abrir el editor.', 'error'); }
            })
            .fail(error => {
                hideLoading(); console.error("Error fetching customer data:", error);
                const errorMsg = error?.responseJSON?.message || posBaseParams.i18n?.error_general || 'Error al cargar datos.';
                if (typeof Swal !== 'undefined') Swal.fire('Error', errorMsg, 'error');
            })
            .always(() => { isLoadingCustomerAction = false; });
    }

    function handleSaveCustomer() {
        console.log('handleSaveCustomer triggered.');
        if (isLoadingCustomerAction) { console.log('Save aborted: isLoadingCustomerAction is true.'); return; }
        const firstName = $customerFirstNameInput.val().trim();
        if (!firstName) {
            console.log('Save aborted: First name is empty.');
            showCustomerFormFeedback(posBaseParams.i18n?.customer_required_fields + ' (Nombre)', true);
            $customerFirstNameInput.focus(); return;
        }
        let phoneNumber = '';
        if (iti) {
            const rawNumberInput = $customerPhoneInput.val().trim(); const isValid = iti.isValidNumber();
            const validationErrorCode = iti.getValidationError();
            console.log(`Phone Input Debug: Raw='${rawNumberInput}', IsValid=${isValid}, ErrorCode=${validationErrorCode}`);
            if (isValid) {
                phoneNumber = iti.getNumber(); console.log('Número válido obtenido de iti:', phoneNumber);
            } else if (rawNumberInput) {
                console.warn(`Número de teléfono NO válido según intl-tel-input. Error: ${validationErrorCode}. Intentando reconstruir.`);
                try {
                    const countryData = iti.getSelectedCountryData();
                    if (countryData && countryData.dialCode) {
                        let cleanRawNumber = rawNumberInput.replace(/^0+/, '').replace(/\s+/g, '');
                        phoneNumber = `+${countryData.dialCode}${cleanRawNumber}`;
                        console.log(`Número reconstruido manualmente: ${phoneNumber}`);
                    } else { console.warn('No se pudieron obtener datos del país para reconstruir.'); phoneNumber = rawNumberInput; }
                } catch (e) { console.error("Error reconstruyendo número:", e); phoneNumber = rawNumberInput; }
            } else { console.log('Campo de teléfono vacío.'); }
        } else { console.error("intl-tel-input (iti) no está inicializado."); phoneNumber = $customerPhoneInput.val().trim(); }
        const customerData = {
            first_name: firstName, last_name: $customerLastNameInput.val().trim(),
            email: $customerEmailInput.val().trim(), phone: phoneNumber,
            meta_data: [
                { key: 'pos_customer_avatar_id', value: $customerAvatarIdInput.val() || '' },
                { key: '_pos_customer_note', value: $customerNoteInput.val() } // <-- AÑADIR ESTE OBJETO AL ARRAY
            ]
        };
        const customerId = $customerIdInput.val(); const isEditing = !!customerId;
        console.log('Saving customer...', customerData, 'Is Editing:', isEditing);
        isLoadingCustomerAction = true; showLoading(posBaseParams.i18n?.saving || 'Guardando...');
        $saveCustomerBtn.prop('disabled', true);
        saveCustomerData(customerData, customerId)
            .done(savedCustomer => {
                console.log('Save successful:', savedCustomer); hideLoading();
                if (typeof tb_remove !== 'undefined') tb_remove();
                updateSelectedCustomerDisplay(savedCustomer);
                if (typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: posBaseParams.i18n?.customer_saved_success || 'Cliente guardado', timer: 1500, showConfirmButton: false }); }
                $customerSearchInput.val(''); $customerSearchResults.hide().empty();
            })
            .fail(error => {
                console.error('Save failed:', error); hideLoading();
                const errorMsg = error?.responseJSON?.message || posBaseParams.i18n?.customer_saved_error || 'Error al guardar.';
                showCustomerFormFeedback(errorMsg, true);
            })
            .always(() => {
                console.log('Save .always() callback executed.');
                isLoadingCustomerAction = false; $saveCustomerBtn.prop('disabled', false);
            });
    }

    function handleCancelCustomerModal() {
        if (typeof tb_remove !== 'undefined') tb_remove(); console.log('Customer modal cancelled');
    }

    function handleDeselectCustomer() {
        currentCustomerId = null; $selectedCustomerInfo.hide(); $selectedCustomerName.text('');
        $selectedCustomerAvatar.attr('src', posBaseParams.default_avatar_url || '');
        $customerSearchSection.show(); $customerSearchInput.val('').focus(); $customerSearchResults.hide().empty();
        console.log('Customer deselected'); updateCheckoutButtonState();
    }

    function handleOpenMediaUploader() {
        if (isLoadingCustomerAction) return;
        if (mediaFrame) { mediaFrame.open(); return; }
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            console.error('wp.media no está definido.');
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'El cargador de medios no está disponible.', 'error'); return;
        }
        mediaFrame = wp.media({
            title: posBaseParams.i18n?.select_avatar_title || 'Seleccionar Avatar',
            button: { text: posBaseParams.i18n?.use_this_avatar || 'Usar imagen' },
            library: { type: 'image' }, multiple: false
        });
        mediaFrame.on('select', function () {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            console.log('Media selected:', attachment);
            $customerAvatarPreview.attr('src', attachment.sizes?.thumbnail?.url || attachment.url);
            $customerAvatarIdInput.val(attachment.id); $removeAvatarBtn.show();
        });
        mediaFrame.open();
    }

    function handleRemoveAvatar() {
        $customerAvatarPreview.attr('src', posBaseParams.default_avatar_url || '');
        $customerAvatarIdInput.val(''); $removeAvatarBtn.hide(); console.log('Avatar removed');
    }

    function searchCustomers(searchTerm) {
        const url = `${posBaseParams.rest_url}customers?search=${encodeURIComponent(searchTerm)}&per_page=10`;
        console.log(`API Call (Customer Search): GET ${url}`);
        return $.ajax({ url: url, method: 'GET', beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce) });
    }

    function getCustomerData(customerId) {
        const url = `${posBaseParams.rest_url}customers/${customerId}`;
        console.log(`API Call (Get Customer): GET ${url}`);
        return $.ajax({ url: url, method: 'GET', beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce) });
    }

    function saveCustomerData(data, customerId = null) {
        const method = customerId ? 'PUT' : 'POST';
        const url = customerId ? `${posBaseParams.rest_url}customers/${customerId}` : `${posBaseParams.rest_url}customers`;
        console.log(`API Call (Save Customer): ${method} ${url}`, data);
        return $.ajax({
            url: url, method: method, beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce),
            contentType: 'application/json', data: JSON.stringify(data)
        });
    }

    function handleCustomerSearchInput() {
        const searchTerm = $customerSearchInput.val().trim(); clearTimeout(customerDebounceTimer);
        if (searchTerm.length < 2) { $customerSearchResults.hide().empty(); return; }
        customerDebounceTimer = setTimeout(() => {
            console.log(`Debounce: Buscando cliente "${searchTerm}"`);
            $customerSearchResults.html(`<li class="loading">${posBaseParams.i18n?.searching || 'Buscando...'}</li>`).show();
            searchCustomers(searchTerm)
                .done(results => { renderCustomerSearchResults(results); })
                .fail(error => { console.error('Error buscando clientes:', error); $customerSearchResults.html(`<li class="error">${posBaseParams.i18n?.search_error || 'Error al buscar.'}</li>`).show(); });
        }, DEBOUNCE_DELAY);
    }

    function renderCustomerSearchResults(results) {
        $customerSearchResults.empty();
        if (!Array.isArray(results) || results.length === 0) {
            $customerSearchResults.html(`<li class="no-results">${posBaseParams.i18n?.no_customers_found || 'No se encontraron clientes.'}</li>`).show(); return;
        }
        results.forEach(customer => {
            const displayName = `${customer.first_name || ''} ${customer.last_name || ''}`.trim();
            const email = customer.email || ''; const phone = customer.phone || '';
            const avatarUrl = customer.avatar_url || posBaseParams.default_avatar_url || '';
            const resultItem = $(`
                <li data-customer-id="${customer.id}">
                    <img src="${avatarUrl}" alt="" width="30" height="30">
                    <span class="name">${displayName || 'Invitado'}</span>
                    <small class="details">${email}${email && phone ? ' | ' : ''}${phone}</small>
                </li>`);
            resultItem.data('customerData', customer); $customerSearchResults.append(resultItem);
        });
        $customerSearchResults.show();
    }

    function handleSelectCustomerResult(event) {
        const selectedLi = $(event.currentTarget); const customerData = selectedLi.data('customerData');
        if (customerData && customerData.id) {
            console.log('Cliente seleccionado:', customerData); updateSelectedCustomerDisplay(customerData);
            $customerSearchInput.val(''); $customerSearchResults.hide().empty();
        } else { console.error('No se pudieron obtener los datos del cliente seleccionado.'); }
    }

    // --- Funciones de Checkout y Pago ---
    function handleSaleTypeChange() {
        const selectedType = $saleTypeSelect.val();
        if (selectedType === 'subscription') {
            $subscriptionFields.slideDown();
        } else {
            $subscriptionFields.slideUp();
        }
        console.log('Tipo de venta cambiado a:', selectedType);
    }

    function handleCompleteSale() {
        if (isLoadingCheckoutAction || completeSaleButton.prop('disabled')) {
            console.warn("Checkout en progreso o botón deshabilitado.");
            return;
        }

        // --- Validaciones Iniciales ---
        if (cart.length === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'El carrito está vacío.', 'error');
            return;
        }
        if (!currentCustomerId) {
            // Permitir venta como invitado si currentCustomerId es null o 0
                if (currentCustomerId !== 0 && currentCustomerId !== null) {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se ha seleccionado un cliente.', 'error');
                    return;
                }
                console.log("Procediendo con venta como invitado (Customer ID: 0)");
                currentCustomerId = 0; // Asegurar que sea 0 para invitado
        }
        const selectedPaymentMethod = $paymentMethodSelect.val();
        if (!selectedPaymentMethod) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Selecciona un método de pago.', 'error');
            return;
        }

        const saleDate = $saleDateInput.val(); // <-- NUEVA LÍNEA
        if (!saleDate) { // <-- NUEVA VALIDACIÓN (Opcional)
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Por favor, selecciona una fecha de venta.', 'error');
            $saleDateInput.focus();
            return;
        }
        // --- Fin Validaciones Iniciales ---

        const saleType = $saleTypeSelect.val();
        let subscriptionData = null;

        // --- Validar Campos de Suscripción (si aplica) ---
        if (saleType === 'subscription') {
            const title = $subscriptionTitle.val().trim();
            const expiryDate = $subscriptionExpiryDate.val();
            const color = $subscriptionColor.val();
            if (!title || !expiryDate) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Por favor, completa el título y la fecha de vencimiento de la suscripción.', 'error');
                // Intentar enfocar el primer campo inválido
                if (!title) $subscriptionTitle.focus(); else $subscriptionExpiryDate.focus();
                return; // Detener si faltan datos de suscripción base
            }
            subscriptionData = { title: title, expiry_date: expiryDate, color: color };

            // --- INICIO: VALIDACIÓN DEL PERFIL STREAMING (MODIFICADA) ---
            // Obtener el contenedor del selector (ya lo usamos antes)
            const $profileSelectorWrap = $('.pos-streaming-profile-selector-wrap');
            const selectedProfileId = $('#pos-streaming-profile-select').val(); // Obtener ID del select

            // SOLO validar si el tipo es suscripción Y el selector está visible
            if ($profileSelectorWrap.is(':visible') && (!selectedProfileId || selectedProfileId === '')) {
                // Error: Se requiere perfil para suscripción
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Debes seleccionar un perfil disponible para la suscripción.', 'error');
                $('#pos-streaming-profile-select').focus(); // Enfocar el selector
                return; // Detener la ejecución de completeSale
            }
            // --- FIN: VALIDACIÓN DEL PERFIL STREAMING (MODIFICADA) ---
        }
        // --- Fin Validar Campos de Suscripción ---


        // --- Construir Datos del Pedido ---
        const orderData = {
            customer_id: currentCustomerId, // Será 0 si es invitado
            pos_order_note: $orderNoteInput.val().trim(),
            payment_method: selectedPaymentMethod,
            payment_method_title: $paymentMethodSelect.find('option:selected').text(),
            set_paid: (saleType !== 'credit'), // No marcar como pagado si es crédito
            billing: {}, // Se llenarán en el backend si es cliente existente
            shipping: {}, // No usado por ahora
            line_items: cart.map(item => ({
                product_id: item.product_id,
                variation_id: item.variation_id || 0,
                quantity: item.quantity,
                // 'total' y 'subtotal' se calculan en backend basado en 'price'
                price: item.current_price // Enviar el precio unitario actual
            })),
            meta_data: [
                { key: '_pos_sale_type', value: saleType } // Guardar siempre el tipo de venta
            ],
            coupon_lines: [],
            pos_order_note: $orderNoteInput.val().trim(),
            pos_sale_date: saleDate
        };

        // Añadir datos del cupón si existe
        if (appliedCoupon) {
            orderData.coupon_lines.push({ code: appliedCoupon.code });
        }

        // Añadir metadatos de suscripción base si aplica
        if (subscriptionData) {
            orderData.meta_data.push({ key: '_pos_subscription_title', value: subscriptionData.title });
            orderData.meta_data.push({ key: '_pos_subscription_expiry_date', value: subscriptionData.expiry_date });
            orderData.meta_data.push({ key: '_pos_subscription_color', value: subscriptionData.color });
        }

        // --- INICIO: AÑADIR ID DEL PERFIL STREAMING A META_DATA ---
        if (saleType === 'subscription') {
            const selectedProfileId = $('#pos-streaming-profile-select').val();
            // Ya validamos que no esté vacío arriba, pero volvemos a comprobar por seguridad
            if (selectedProfileId && selectedProfileId !== '') {
                orderData.meta_data.push({
                    key: '_pos_assigned_profile_id',
                    value: selectedProfileId
                });
                console.log('Añadido _pos_assigned_profile_id al pedido:', selectedProfileId);
            }
            // No necesitamos un 'else' aquí porque ya validamos antes
        }
        // --- FIN: AÑADIR ID DEL PERFIL STREAMING A META_DATA ---

        console.log("Datos del pedido a enviar:", orderData);
        // --- Fin Construir Datos del Pedido ---


        // --- Enviar Pedido ---
        isLoadingCheckoutAction = true;
        completeSaleButton.prop('disabled', true).text(posBaseParams.i18n?.processing || 'Procesando...');
        showLoading(posBaseParams.i18n?.creating_order || 'Creando pedido...');

        createOrder(orderData) // Llamar a la función que hace el AJAX POST a /orders
            .done(response => {
                hideLoading();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: posBaseParams.i18n?.order_created_success || '¡Pedido Creado!',
                        text: `Pedido #${response.id} creado correctamente.`,
                        showConfirmButton: true
                    });
                }
                resetPOSState(); // Limpiar carrito, cliente, etc.
                refreshSalesDataTable(); // Actualizar tabla de ventas
                // --- INICIO: AÑADIR ESTA LÍNEA ---
                if (calendar && typeof calendar.refetchEvents === 'function') {
                    console.log('Refrescando eventos del calendario...');
                    calendar.refetchEvents(); // Vuelve a pedir los eventos al endpoint API
                } else {
                    console.warn('Intento de refrescar calendario, pero no está inicializado o no es válido.');
                }

            })
            .fail(error => {
                hideLoading();
                console.error("Error creando pedido:", error);
                const errorMsg = error?.responseJSON?.message || posBaseParams.i18n?.order_created_error || 'Error al crear el pedido.';
                if (typeof Swal !== 'undefined') Swal.fire('Error', errorMsg, 'error');
                // No resetear estado si falla, para que el usuario pueda intentar de nuevo
            })
            .always(() => {
                isLoadingCheckoutAction = false;
                completeSaleButton.text(posBaseParams.i18n?.complete_sale || 'Completar Venta');
                // El estado del botón se actualizará basado en si hay cliente/carrito
                updateCheckoutButtonState();
            });
        // --- Fin Enviar Pedido ---
    } // Fin handleCompleteSale
    

    async function loadPaymentMethods() {
        const placeholderOption = `<option value="" disabled selected>${posBaseParams.i18n?.loading_payment_methods || 'Cargando métodos...'}</option>`;
        $paymentMethodSelect.html(placeholderOption).prop('disabled', true);

        if (typeof posBaseParams === 'undefined' || !posBaseParams.rest_url || !posBaseParams.nonce) {
            console.error('loadPaymentMethods: posBaseParams no está definido o incompleto.');
            $paymentMethodSelect.html(`<option value="" disabled selected>${posBaseParams.i18n?.error_loading_payment_methods || 'Error config.'}</option>`);
            return;
        }

        try {
            const apiUrl = `${posBaseParams.rest_url}payment-gateways`;
            console.log(`API Call (Payment Gateways): GET ${apiUrl}`);
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': posBaseParams.nonce }
            });

            if (!response.ok) {
                let errorMsg = `Error ${response.status}`;
                try { const errorData = await response.json(); errorMsg = errorData.message || errorMsg; } catch(e) { console.warn("No se pudo parsear el cuerpo de la respuesta de error."); }
                throw new Error(errorMsg);
            }

            const gateways = await response.json();

            if (Array.isArray(gateways) && gateways.length > 0) {
                $paymentMethodSelect.empty();
                $paymentMethodSelect.append(`<option value="" disabled selected>${posBaseParams.i18n?.select_payment_method || '-- Selecciona Método --'}</option>`);
                gateways.forEach(gateway => {
                    const escapedTitle = $('<div>').text(gateway.title).html();
                    $paymentMethodSelect.append(`<option value="${gateway.id}">${escapedTitle}</option>`);
                });
                $paymentMethodSelect.prop('disabled', false);
                console.log('Métodos de pago cargados:', gateways);
            } else {
                $paymentMethodSelect.html(`<option value="" disabled selected>${posBaseParams.i18n?.no_payment_methods || 'No hay métodos'}</option>`);
                console.warn('No se encontraron métodos de pago activos.');
            }

        } catch (error) {
            console.error('Error cargando métodos de pago:', error);
            $paymentMethodSelect.html(`<option value="" disabled selected>${posBaseParams.i18n?.error_loading_payment_methods || 'Error al cargar'}</option>`);
        }
    }

    function createOrder(orderData) {
        const url = `${posBaseParams.rest_url}orders`;
        console.log(`API Call (Create Order): POST ${url}`, orderData);
        return $.ajax({
            url: url, method: 'POST',
            beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce),
            contentType: 'application/json',
            data: JSON.stringify(orderData)
        });
    }

    function resetPOSState() {
        cart = []; updateCartUI(); calculateTotals();
        handleDeselectCustomer();
        $saleTypeSelect.val('direct'); handleSaleTypeChange();
        $subscriptionTitle.val(''); $subscriptionExpiryDate.val(''); $subscriptionColor.val('#3a87ad');
        $paymentMethodSelect.val('');
        $orderNoteInput.val('');
        try {
            // Obtener fecha actual en formato YYYY-MM-DD (zona horaria del navegador)
            const today = new Date().toISOString().split('T')[0];
            $saleDateInput.val(today);
        } catch (e) {
            console.error("Error al resetear fecha de venta:", e);
            // Fallback por si falla toISOString o split
            $saleDateInput.val('');
        }
        appliedCoupon = null; $couponCodeInput.val('').prop('disabled', false); $couponMessage.empty().hide(); $applyCouponButton.show();
        console.log("Estado del POS reseteado después de la venta.");
    }

    // --- Funciones del Calendario ---
    function initCalendar() {
        const calendarEl = document.getElementById('pos-calendar');
        if (!calendarEl) { console.error('Elemento del calendario #pos-calendar no encontrado.'); return; }
        if (typeof FullCalendar === 'undefined') { console.error('Librería FullCalendar no cargada.'); calendarEl.innerHTML = '<p style="color:red;">Error: Librería del calendario no disponible.</p>'; return; }
        if (typeof posBaseParams === 'undefined' || !posBaseParams.rest_url || !posBaseParams.nonce) { console.error('initCalendar: posBaseParams no está definido o incompleto.'); calendarEl.innerHTML = '<p style="color:red;">Error de configuración para cargar eventos.</p>'; return; }

        // Destruir instancia anterior si existe (para evitar duplicados al recargar)
        if (calendar) {
            try { calendar.destroy(); } catch(e) { console.warn("Error destruyendo calendario previo:", e); }
            calendar = null;
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', locale: 'es',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
            buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', list: 'Lista' },
            navLinks: true, editable: false, dayMaxEvents: true,
            events: {
                url: `${posBaseParams.rest_url}calendar-events?_wpnonce=${posBaseParams.nonce}`,
                method: 'GET',
                failure: function(error) {
                    console.error('Error cargando eventos del calendario:', error);
                    if (typeof Swal !== 'undefined') { Swal.fire('Error', 'No se pudieron cargar los eventos del calendario.', 'error'); }
                },
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const eventData = info.event; const props = eventData.extendedProps;
                console.log('Evento clickeado:', eventData);
                if (props.order_url) { window.open(props.order_url, '_blank'); }
                else if (props.order_id && posBaseParams.admin_url) {
                    const editUrl = `${posBaseParams.admin_url}admin.php?page=wc-orders&action=edit&id=${props.order_id}`;
                    window.open(editUrl, '_blank');
                } else { Swal.fire('Info', `Vencimiento: ${eventData.title}${props.order_id ? `\nPedido ID: ${props.order_id}` : ''}`, 'info'); }
            },
            loading: function(isLoading) {
                const calendarWrapper = $(calendarEl).closest('.pos-section-content');
                if (isLoading) { console.log('Calendario cargando eventos...'); calendarWrapper.addClass('loading-calendar'); }
                else { console.log('Calendario terminó de cargar eventos.'); calendarWrapper.removeClass('loading-calendar'); }
            }
        });

        calendar.render();
        console.log('FullCalendar inicializado.');
    }

    /**
     * Inicializa la tabla de ventas con DataTables.
     */
    function initSalesDataTable() {
        const $salesTable = $('#pos-sales-datatable');
        if (!$salesTable.length) {
            console.warn('Tabla de ventas #pos-sales-datatable no encontrada.');
            return;
        }
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('Librería DataTables no cargada.');
            $salesTable.replaceWith('<p style="color:red;">Error: Librería DataTables no disponible.</p>');
            return;
        }
        if (typeof posBaseParams === 'undefined' || !posBaseParams.rest_url || !posBaseParams.nonce) {
            console.error('initSalesDataTable: posBaseParams no está definido o incompleto.');
            $salesTable.replaceWith('<p style="color:red;">Error de configuración para cargar datos de ventas.</p>');
            return;
        }

        // Destruir instancia anterior si existe
        if (salesDataTable && $.fn.DataTable.isDataTable($salesTable)) {
            console.log('Destruyendo instancia previa de DataTables...');
            salesDataTable.destroy();
            $salesTable.empty(); // Limpiar thead/tbody si DataTables los modificó
        }

        console.log('Inicializando DataTables para #pos-sales-datatable...');
        salesDataTable = $salesTable.DataTable({
            processing: true, // Muestra indicador de procesamiento
            serverSide: true, // Habilita procesamiento del lado del servidor
            ajax: {
                url: `${posBaseParams.rest_url}sales-datatable`, // URL del endpoint API
                type: 'GET',
                // Añadir el nonce a la cabecera de la petición AJAX
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce);
                },
                // Especificar que los datos vienen en la propiedad 'data' de la respuesta JSON
                dataSrc: 'data',
                // Manejo de errores AJAX
                error: function (xhr, error, thrown) {
                    console.error("Error en AJAX de DataTables:", error, thrown);
                    console.log("Respuesta del servidor:", xhr.responseText);
                    // Mostrar un mensaje de error al usuario en la tabla
                    $salesTable.find('tbody').html(
                        '<tr><td colspan="5" class="dataTables_empty">' + (posBaseParams.i18n?.dt_error || 'Error al cargar los datos.') + '</td></tr>'
                    );
                    // Opcional: usar SweetAlert
                    // if (typeof Swal !== 'undefined') { Swal.fire('Error', 'No se pudieron cargar las ventas.', 'error'); }
                }
            },
      
            // Definición de las columnas (5 columnas agrupadas)
            columns: [
                // Índice API | Propiedad 'data' | 'name' (para referencia/server-side) | Opciones
                { data: 0, name: 'date_created', orderable: true, searchable: false }, // Col 1 (Pedido/Fecha/Tipo) - Ordenar por Fecha
                { data: 1, name: 'customer', orderable: true, searchable: true },     // Col 2 (Cliente/Contacto) - Ordenar por Nombre (aprox), Permitir búsqueda
                { data: 2, name: 'products', orderable: false, searchable: true },    // Col 3 (Producto(s)) - No ordenable, Permitir búsqueda (server-side)
                { data: 3, name: '_pos_subscription_expiry_date', orderable: true, searchable: false }, // Col 4 (Vencimiento/Historial) - Ordenar por Vencimiento
                { data: 4, name: 'details', orderable: false, searchable: true, className: 'pos-dt-column-wide' } // Col 5 (Notas/Detalles) - No ordenable, Permitir búsqueda, Clase ancha
            ],
         
            
            // Configuración del idioma usando las traducciones de posBaseParams
            language: {
                processing: posBaseParams.i18n?.dt_processing || 'Procesando...',
                search: posBaseParams.i18n?.dt_search || 'Buscar:',
                lengthMenu: posBaseParams.i18n?.dt_lengthMenu || 'Mostrar _MENU_ registros',
                info: posBaseParams.i18n?.dt_info || 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: posBaseParams.i18n?.dt_infoEmpty || 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: posBaseParams.i18n?.dt_infoFiltered || '(filtrado de _MAX_ registros totales)',
                loadingRecords: posBaseParams.i18n?.dt_loadingRecords || 'Cargando...',
                zeroRecords: posBaseParams.i18n?.dt_zeroRecords || 'No se encontraron registros coincidentes',
                emptyTable: posBaseParams.i18n?.dt_emptyTable || 'No hay datos disponibles en la tabla',
                paginate: {
                    first: posBaseParams.i18n?.dt_paginate_first || 'Primero',
                    previous: posBaseParams.i18n?.dt_paginate_previous || 'Anterior',
                    next: posBaseParams.i18n?.dt_paginate_next || 'Siguiente',
                    last: posBaseParams.i18n?.dt_paginate_last || 'Último'
                },
                aria: {
                    sortAscending: posBaseParams.i18n?.dt_aria_sortAscending || ': activar para ordenar la columna ascendente',
                    sortDescending: posBaseParams.i18n?.dt_aria_sortDescending || ': activar para ordenar la columna descendente'
                }
            },
            // Orden inicial (por fecha, descendente)
            order: [[1, 'desc']],
            // Habilitar diseño responsivo
            responsive: true,
            // Guardar estado (paginación, búsqueda) - opcional
            // stateSave: true,
            // Longitud de página por defecto
            pageLength: 10,
            // Opciones de longitud de página
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        });

        console.log('DataTables inicializado correctamente.');
    }

    /**
     * Refresca los datos de la tabla de ventas sin recargar la página.
     */
    function refreshSalesDataTable() {
        if (salesDataTable) {
            console.log('Refrescando DataTables de ventas...');
            // null = no resetea la paginación
            // false = no resetea el orden/búsqueda
            salesDataTable.ajax.reload(null, false);
        } else {
            console.warn('Intento de refrescar DataTables, pero no está inicializado.');
        }
    }

    // --- Event Listeners ---
    function bindEvents() {
        // Pestañas <-- NUEVO
        $tabWrapper.on('click', 'a.nav-tab', handleTabClick);

        // Productos
        productSearchInput.on('input', function () {
            const searchTerm = $(this).val().trim(); clearTimeout(productDebounceTimer);
            productDebounceTimer = setTimeout(() => { const searchChanged = searchTerm !== currentSearchTerm; const shouldSearchFeatured = !searchTerm; if (searchChanged) { fetchProducts(searchTerm, 1, shouldSearchFeatured); } }, DEBOUNCE_DELAY);
        });
        productListContainer.on('click', 'button.add-simple-to-cart', handleAddSimpleClick);
        productListContainer.on('click', 'button.add-variation-to-cart', handleAddVariationClick);

        // Carrito
        cartItemsContainer.on('click', '.pos-cart-item-remove', handleRemoveCartItemClick);
        cartItemsContainer.on('input', '.pos-cart-item-price-input', handleCartPriceInputChange);

        // Cliente Modal
        $addNewCustomerBtn.on('click', handleOpenNewCustomerModal);
        $editCustomerBtn.on('click', handleOpenEditCustomerModal);
        $saveCustomerBtn.on('click', handleSaveCustomer);
        $cancelCustomerBtn.on('click', handleCancelCustomerModal);
        $changeAvatarBtn.on('click', handleOpenMediaUploader);
        $removeAvatarBtn.on('click', handleRemoveAvatar);
        $changeCustomerBtn.on('click', handleDeselectCustomer);

        // Búsqueda Cliente
        $customerSearchInput.on('input', handleCustomerSearchInput);
        $customerSearchResults.on('click', 'li[data-customer-id]', handleSelectCustomerResult);
        $(document).on('click', function(event) {
            if (!$(event.target).closest('.pos-customer-search').length) { $customerSearchResults.hide(); }
        });

        // Cupón
        $applyCouponButton.on('click', handleApplyCoupon);
        $couponMessage.on('click', '.pos-remove-coupon-button', handleRemoveCoupon);
        $couponCodeInput.on('keypress', function(event) { if (event.key === 'Enter') { event.preventDefault(); handleApplyCoupon(); } });

        // Checkout
        $saleTypeSelect.on('change', handleSaleTypeChange);
        completeSaleButton.on('click', handleCompleteSale);
    }

     // --- Listener para Enviar SMS desde Modal Thickbox (CORREGIDO) ---
     // Delegar el evento al 'document' para asegurar que funcione incluso cuando Thickbox mueva el modal
     $(document).on('click', '.pos-send-sms-button', function(event) {
         event.preventDefault(); // Prevenir comportamiento por defecto del botón
 
         const sendButton = $(this); // 'this' es el botón clickeado
         // Encontrar el contenedor del modal usando closest() desde el botón
         const modalWrapper = sendButton.closest('.pos-sms-modal-wrapper');
         if (!modalWrapper.length) {
             console.error('No se encontró el contenedor del modal (.pos-sms-modal-wrapper)');
             return; // Salir si no se encuentra el contenedor
         }
 
         // Seleccionar elementos dentro del contexto del modal encontrado
         const feedbackDiv = modalWrapper.find('.pos-sms-feedback');
         const spinner = modalWrapper.find('.spinner');
         const messageTextarea = modalWrapper.find('.pos-sms-message-input');
         const phone = sendButton.data('phone');
         const orderId = sendButton.data('orderId'); // Opcional
         const message = messageTextarea.length ? messageTextarea.val().trim() : '';
 
         // --- El resto de la lógica (validación, AJAX, feedback) permanece igual ---
 
         // Validar mensaje
         if (!message) {
             feedbackDiv.text(posBaseParams.i18n.error_message_required || 'Por favor, escribe un mensaje.').css('color', 'red');
             return;
         }
         if (!phone) {
             feedbackDiv.text(posBaseParams.i18n.error_phone_missing || 'Falta el número de teléfono.').css('color', 'red');
             return;
         }
 
         // Mostrar estado de carga
         sendButton.prop('disabled', true);
         if (spinner.length) spinner.addClass('is-active');
         feedbackDiv.text(posBaseParams.i18n.sending_message || 'Enviando mensaje...').css('color', 'inherit');
 
         // Preparar datos para AJAX
         const formData = new URLSearchParams();
         formData.append('action', 'pos_base_send_pos_sms');
         formData.append('_ajax_nonce', posBaseParams.send_sms_nonce);
         formData.append('phone', phone);
         formData.append('message', message);
         // formData.append('order_id', orderId);
 
         // Petición Fetch
         fetch(posBaseParams.ajax_url, {
             method: 'POST',
             body: formData
         })
         .then(response => response.json())
         .then(result => {
             if (result.success) {
                 feedbackDiv.text(result.data || (posBaseParams.i18n.message_sent_success || 'Mensaje enviado con éxito.')).css('color', 'green');
                 if (messageTextarea.length) messageTextarea.val('');
                 setTimeout(() => {
                     tb_remove(); // Cerrar Thickbox
                 }, 1500);
             } else {
                 throw new Error(result.data || (posBaseParams.i18n.error_sending_message || 'Error al enviar el mensaje.'));
             }
         })
         .catch(error => {
             console.error('Error sending POS SMS:', error);
             feedbackDiv.text(error.message || (posBaseParams.i18n.error_sending_message || 'Error al enviar el mensaje.')).css('color', 'red');
         })
         .finally(() => {
             sendButton.prop('disabled', false);
             if (spinner.length) spinner.removeClass('is-active');
         });
     });
     // --- Fin Listener Enviar SMS (CORREGIDO) ---
 
    
    // --- Inicialización ---
    function init() {
        console.log('POS Streaming App Initializing...');
        if (typeof posBaseParams === 'undefined' || !posBaseParams.rest_url || !posBaseParams.nonce) {
            console.error('FATAL: posBaseParams no disponible. Abortando.');
            $('body').prepend('<div class="notice notice-error"><p>Error crítico: Faltan parámetros de configuración del plugin POS Streaming.</p></div>');
            return;
        }
        console.log('posBaseParams OK:', posBaseParams);
        productSearchInput.attr('placeholder', posBaseParams.i18n?.search_placeholder || 'Buscar producto...');
        $customerSearchInput.attr('placeholder', posBaseParams.i18n?.search_customer_placeholder || 'Buscar cliente...');

        bindEvents();
        fetchProducts('', 1, true);
        updateCartUI();
        calculateTotals();
        handleDeselectCustomer();
        handleSaleTypeChange();
        loadPaymentMethods();
        initCalendar();
        initSalesDataTable(); // <-- **NUEVO:** Inicializar DataTables
        console.log('POS Streaming App Initialized.');
    }

    // Ejecutar inicialización
    init();

}); // Fin jQuery ready
