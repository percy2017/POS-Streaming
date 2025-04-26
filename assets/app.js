/**
 * POS Streaming Application Logic
 * Version: 1.0.9 (Sale Type & Subscription Fields)
 */
jQuery(function ($) {
    'use strict';
    // Log inicial para verificar si los parámetros de PHP están disponibles
    console.log('DEBUG: posStreamingParams:', typeof posStreamingParams !== 'undefined' ? posStreamingParams : '¡NO DEFINIDO!');

    // --- Cache de Selectores DOM ---
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

    // Checkout & Pago
    const $saleTypeSelect = $('#pos-sale-type'); // <-- NUEVO
    const $subscriptionFields = $('#pos-subscription-fields'); // <-- NUEVO
    const $subscriptionTitle = $('#pos-subscription-title'); // <-- NUEVO
    const $subscriptionExpiryDate = $('#pos-subscription-expiry-date'); // <-- NUEVO
    const $subscriptionColor = $('#pos-subscription-color'); // <-- NUEVO
    const $paymentMethodSelect = $('#pos-payment-method'); // <-- NUEVO (Asumiendo que ya existe o lo añadirás)
    const $couponCodeInput = $('#pos-coupon-code'); // <-- NUEVO
    const $applyCouponButton = $('#pos-apply-coupon-button'); // <-- NUEVO
    const $couponMessage = $('#pos-coupon-message'); // <-- NUEVO

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

    // --- Funciones de Productos y Carrito (Existentes y Modificadas) ---
    function showMessage(container, message, type = 'info') {
        container.html(`<p class="message-feedback ${type}">${message}</p>`);
    }

    async function fetchProducts(searchTerm = '', page = 1, featuredOnly = false) {
        if (typeof posStreamingParams === 'undefined' || !posStreamingParams.rest_url || !posStreamingParams.nonce) {
            console.error('Error Crítico: posStreamingParams no está definido o incompleto.');
            showMessage(productListContainer, 'Error de configuración. Contacta al administrador.', 'error');
            return;
        }
        if (isLoadingProducts) return;
        isLoadingProducts = true;
        currentSearchTerm = searchTerm;
        isCurrentlyFeatured = featuredOnly && !searchTerm;
        currentPage = page;
        showMessage(productListContainer, posStreamingParams.i18n?.loading || 'Cargando...', 'loading');
        const params = new URLSearchParams({ page: page, per_page: PRODUCTS_PER_PAGE });
        if (searchTerm) params.append('search', searchTerm);
        if (featuredOnly && !searchTerm) params.append('featured', 'true');
        const apiUrl = `${posStreamingParams.rest_url}products?${params.toString()}`;
        console.log('API Call (Products):', apiUrl);
        try {
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': posStreamingParams.nonce }
            });
            let responseBodyText = await response.text();
            if (!response.ok) {
                let errorMsg = posStreamingParams.i18n?.error_general || 'Ocurrió un error inesperado.';
                try { const errorData = JSON.parse(responseBodyText); errorMsg = errorData.message || errorMsg; } catch (e) { console.error("Respuesta no JSON:", responseBodyText); }
                throw new Error(`${errorMsg} (Status: ${response.status})`);
            }
            const products = JSON.parse(responseBodyText);
            renderProducts(products);
        } catch (error) {
            console.error('Error en fetchProducts:', error);
            showMessage(productListContainer, `${posStreamingParams.i18n?.error_general || 'Error'}: ${error.message}`, 'error');
        } finally {
            isLoadingProducts = false;
        }
    }

    function renderProducts(products) {
        productListContainer.empty();
        if (!Array.isArray(products)) { console.error("API response not array:", products); showMessage(productListContainer, 'Respuesta inesperada.', 'error'); return; }
        if (products.length === 0) { const msg = isCurrentlyFeatured ? (posStreamingParams.i18n?.no_featured_products_found || 'No hay destacados.') : (posStreamingParams.i18n?.no_products_found || 'No se encontraron.'); showMessage(productListContainer, msg, 'info'); return; }
        const listTitle = isCurrentlyFeatured ? 'Productos Destacados' : (currentSearchTerm ? `Resultados para "${currentSearchTerm}"` : 'Todos');
        productListContainer.append(`<h3 class="pos-product-list-title">${listTitle}</h3>`);
        products.forEach((product, index) => {
            if (!product || typeof product !== 'object' || !product.id) { console.warn(`[${index}] Producto inválido:`, product); return; }
            const isVariable = product.type === 'variable' && Array.isArray(product.variations) && product.variations.length > 0;
            const isSimple = product.type === 'simple';
            const simpleStockStatus = isSimple ? product.stock_status : 'N/A';
            const isSimpleInStock = isSimple && simpleStockStatus === 'instock';
            let variationsHtml = '';
            if (isVariable) {
                variationsHtml = '<ul class="product-variations-list">';
                product.variations.forEach(variation => {
                    if (!variation || typeof variation !== 'object' || !variation.variation_id) { console.warn(`[${index}] Variación inválida:`, variation); return; }
                    const variationId = variation.variation_id;
                    const isVariationInStock = variation.stock_status === 'instock';
                    const stockText = isVariationInStock ? (variation.stock_quantity !== null ? `Stock: ${variation.stock_quantity}` : (posStreamingParams.i18n?.instock || 'En stock')) : (posStreamingParams.i18n?.outofstock || 'Agotado');
                    const priceText = variation.price_html || (typeof variation.price === 'number' ? variation.price.toFixed(2) : 'N/A');
                    variationsHtml += `<li class="product-variation-item ${isVariationInStock ? 'instock' : 'outofstock'}" data-variation-id="${variationId}"><span class="variation-details"><span class="variation-name">${variation.variation_name || 'Variación'}</span> ${variation.sku ? `<span class="variation-sku">(SKU: ${variation.sku})</span>` : ''} - <span class="variation-price">${priceText}</span></span><span class="variation-stock-status">${stockText}</span><span class="variation-actions"><button type="button" class="button button-small add-variation-to-cart" data-product-id="${product.id}" data-variation-id="${variationId}" ${!isVariationInStock ? 'disabled' : ''} title="Añadir ${variation.variation_name || 'Variación'}">${posStreamingParams.i18n?.add_to_cart || 'Añadir'}</button></span></li>`;
                });
                variationsHtml += '</ul>';
            }
            let actionHtml = '';
            if (isSimple) { actionHtml = `<button type="button" class="button button-primary add-simple-to-cart" data-product-id="${product.id}" ${!isSimpleInStock ? 'disabled' : ''} title="Añadir ${product.name || 'producto'}">${posStreamingParams.i18n?.add_to_cart || 'Añadir'}</button>`; }
            else if (isVariable) { actionHtml = `<span class="select-variation-label">${posStreamingParams.i18n?.select_variation || 'Selecciona opción:'}</span>`; }
            const simplePriceText = product.price_html || (typeof product.price === 'number' ? product.price.toFixed(2) : 'N/A');
            const simpleStockText = isSimpleInStock ? (posStreamingParams.i18n?.instock || 'En stock') : (posStreamingParams.i18n?.outofstock || 'Agotado');
            const productItem = $(`<div class="pos-product-item ${isVariable ? 'product-type-variable' : (isSimple ? 'product-type-simple' : 'product-type-other')} ${!isVariable && !isSimpleInStock ? 'product-outofstock' : ''}" data-product-id="${product.id}"><div class="product-main-info"><img src="${product.image_url || ''}" alt="${product.name || ''}" class="pos-product-thumbnail"><div class="product-details"><strong class="product-name">${product.name || 'Sin nombre'}</strong><div class="product-meta">${product.sku ? `<span class="product-sku">SKU: ${product.sku}</span>` : ''} ${isSimple ? `<span class="product-price">Precio: ${simplePriceText}</span>` : ''} ${isSimple ? `<span class="product-stock-status stock-${simpleStockStatus}">${simpleStockText}</span>` : ''}</div></div><div class="product-actions">${actionHtml}</div></div>${variationsHtml}</div>`);
            productItem.data('productData', product);
            productListContainer.append(productItem);
        });
    }

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
            cartItemsContainer.html(`<li class="empty-cart">${posStreamingParams.i18n?.cart_empty || 'El carrito está vacío.'}</li>`);
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

        // --- NUEVO: Calcular descuento del cupón ---
        if (appliedCoupon) {
            discountType = appliedCoupon.discount_type;
            const couponAmount = parseFloat(appliedCoupon.amount) || 0;

            if (discountType === 'percent') {
                // Calcular descuento porcentual sobre el subtotal
                discount = (subtotal * couponAmount) / 100;
            } else if (discountType === 'fixed_cart') {
                // Descuento fijo sobre el carrito, no puede ser mayor que el subtotal
                discount = Math.min(couponAmount, subtotal);
            } else if (discountType === 'fixed_product') {
                // Nota: La validación básica no maneja descuentos por producto.
                // Para POS, podríamos tratarlo como 'fixed_cart' o ignorarlo si es complejo.
                // Por simplicidad, lo tratamos como fijo al carrito aquí.
                console.warn(`Cupón 'fixed_product' (${appliedCoupon.code}) tratado como 'fixed_cart' en POS.`);
                discount = Math.min(couponAmount, subtotal);
            }
            // Asegurarse de que el descuento no sea negativo
            discount = Math.max(0, discount);
            console.log(`Descuento calculado (${discountType}): ${discount.toFixed(2)}`);
        }
        // --- Fin cálculo descuento ---

        let total = subtotal - discount;
        total = Math.max(0, total); // El total no puede ser negativo

        updateTotalsUI(subtotal, discount, total);
    }

    function updateTotalsUI(subtotal, discount, total) {
        cartSubtotalAmount.text(subtotal.toFixed(2));
        if (discount > 0 && appliedCoupon) {
            // Mostrar descuento con el código del cupón
            cartDiscountAmount.html(`${discount.toFixed(2)} <small>(${appliedCoupon.code})</small>`);
            cartDiscountRow.show();
        } else {
            cartDiscountRow.hide();
            cartDiscountAmount.empty(); // Limpiar si no hay descuento
        }
        cartTotalAmount.text(total.toFixed(2));
        updateCheckoutButtonState();
    }

    function showCouponMessage(message, isError = false) {
        $couponMessage.html(message) // Usar html para poder añadir botón quitar
            .removeClass('success error')
            .addClass(isError ? 'error' : 'success')
            .show();
    }

    function validateCouponAPI(couponCode) {
        const url = `${posStreamingParams.rest_url}coupons/validate`;
        console.log(`API Call (Validate Coupon): POST ${url}`);
        isLoadingCouponAction = true; // Marcar acción en progreso
        $applyCouponButton.prop('disabled', true).text(posStreamingParams.i18n?.validating || 'Validando...'); // Estado botón

        return $.ajax({
            url: url,
            method: 'POST',
            beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posStreamingParams.nonce),
            contentType: 'application/json',
            data: JSON.stringify({ code: couponCode })
        }).always(() => {
            // Siempre se ejecuta, re-habilitar botón y limpiar estado loading
            isLoadingCouponAction = false;
            $applyCouponButton.prop('disabled', false).text(posStreamingParams.i18n?.apply || 'Aplicar');
        });
    }

    function handleApplyCoupon() {
        if (isLoadingCouponAction) return;

        const couponCode = $couponCodeInput.val().trim();
        if (!couponCode) {
            showCouponMessage(posStreamingParams.i18n?.coupon_code_required || 'Ingresa un código de cupón.', true);
            $couponCodeInput.focus();
            return;
        }

        // Limpiar mensaje anterior y cupón aplicado
        $couponMessage.empty().hide();
        appliedCoupon = null; // Limpiar cupón anterior antes de validar uno nuevo
        calculateTotals(); // Recalcular sin descuento

        validateCouponAPI(couponCode)
            .done(response => {
                // Éxito: Cupón válido
                console.log('Cupón válido:', response);
                appliedCoupon = response; // Guardar datos del cupón aplicado
                const successMsg = posStreamingParams.i18n?.coupon_applied_success || 'Cupón "%s" aplicado.';
                // Mostrar mensaje con botón para quitar
                showCouponMessage(
                    `<span>${successMsg.replace('%s', `<strong>${response.code}</strong>`)}</span>
                     <button type="button" class="button-link pos-remove-coupon-button" title="${posStreamingParams.i18n?.remove_coupon || 'Quitar cupón'}">&times;</button>`,
                    false // No es error
                );
                $couponCodeInput.prop('disabled', true); // Deshabilitar input
                $applyCouponButton.hide(); // Ocultar botón aplicar
                calculateTotals(); // Recalcular totales CON el nuevo descuento
            })
            .fail(error => {
                // Error: Cupón inválido o error de API
                console.error('Error validando cupón:', error);
                const errorMsg = error?.responseJSON?.message || posStreamingParams.i18n?.coupon_invalid || 'Cupón inválido.';
                showCouponMessage(errorMsg, true); // Mostrar mensaje de error
                appliedCoupon = null; // Asegurarse de que no hay cupón aplicado
                calculateTotals(); // Recalcular sin descuento
                $couponCodeInput.prop('disabled', false).focus(); // Habilitar input y enfocar
                $applyCouponButton.show(); // Mostrar botón aplicar
            });
    }

    function handleRemoveCoupon() {
        console.log('Quitando cupón:', appliedCoupon?.code);
        appliedCoupon = null; // Limpiar estado
        $couponMessage.empty().hide(); // Limpiar mensaje
        $couponCodeInput.val('').prop('disabled', false).focus(); // Limpiar y habilitar input
        $applyCouponButton.show(); // Mostrar botón aplicar
        calculateTotals(); // Recalcular totales sin descuento
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
        const itemToAdd = { id: productData.id, product_id: productData.id, variation_id: null, name: productData.name, sku: productData.sku, price: parseFloat(productData.price) || 0, image_url: productData.image_url, type: 'simple', stock_status: productData.stock_status };
        addToCart(itemToAdd);
    }

    function handleAddVariationClick(event) {
        const button = $(event.currentTarget); const productId = button.data('product-id'); const variationId = button.data('variation-id');
        const productItemElement = button.closest('.pos-product-item'); const productData = productItemElement.data('productData');
        if (!productData || !Array.isArray(productData.variations)) { console.error(`Datos/variaciones no encontrados para padre ID: ${productId}`); if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudieron obtener datos.', 'error'); return; }
        const variationData = productData.variations.find(v => v.variation_id === variationId);
        if (!variationData) { console.error(`Datos no encontrados para variación ID: ${variationId}`); if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudieron obtener datos de variación.', 'error'); return; }
        const itemToAdd = { id: variationData.variation_id, product_id: productId, variation_id: variationData.variation_id, name: `${productData.name} - ${variationData.variation_name}`, sku: variationData.sku, price: parseFloat(variationData.price) || 0, image_url: variationData.image_url, type: 'variation', stock_status: variationData.stock_status };
        addToCart(itemToAdd);
    }

    function handleRemoveCartItemClick(event) {
        const removeButton = $(event.currentTarget); const itemIdToRemove = parseInt(removeButton.data('remove-id'), 10);
        if (!isNaN(itemIdToRemove)) { removeFromCart(itemIdToRemove); }
        else { console.error('ID inválido para eliminar:', removeButton.data('remove-id')); }
    }

    // --- Funciones de Cliente ---
    function showLoading(message = posStreamingParams.i18n?.loading || 'Cargando...') {
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
                utilsScript: posStreamingParams.intlTelInputUtilsScript, initialCountry: "auto",
                geoIpLookup: callback => { callback("pe"); }, separateDialCode: true, nationalMode: false,
            });
            console.log('intl-tel-input initialized');
        } else { console.error('Phone input or intlTelInput library not found'); }
    }

    function resetCustomerForm() {
        $customerFormTitle.text(posStreamingParams.i18n?.add_customer || 'Nuevo Cliente');
        $customerIdInput.val('');
        $customerFirstNameInput.val(''); $customerLastNameInput.val('');
        $customerEmailInput.val(''); $customerPhoneInput.val('');
        $customerAvatarIdInput.val('');
        $customerAvatarPreview.attr('src', posStreamingParams.default_avatar_url || '');
        $removeAvatarBtn.hide();
        $customerFormFeedback.hide().removeClass('notice-success notice-error').text('');
        if (iti) { try { iti.setNumber(''); } catch(e) { console.warn("Error resetting iti number:", e); } }
        console.log('Customer form reset (manual)');
    }

    function populateCustomerForm(customerData) {
        resetCustomerForm();
        $customerFormTitle.text(posStreamingParams.i18n?.edit_customer || 'Editar Cliente');
        $customerIdInput.val(customerData.id);
        $customerFirstNameInput.val(customerData.first_name || '');
        $customerLastNameInput.val(customerData.last_name || '');
        $customerEmailInput.val(customerData.email || '');
        if (iti && customerData.phone) { iti.setNumber(customerData.phone); }
        else { $customerPhoneInput.val(customerData.phone || ''); }
        $customerAvatarIdInput.val(customerData.avatar_id || '');
        $customerAvatarPreview.attr('src', customerData.avatar_url || posStreamingParams.default_avatar_url || '');
        if (customerData.avatar_id && customerData.avatar_url !== posStreamingParams.default_avatar_url) { $removeAvatarBtn.show(); }
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
            $selectedCustomerName.text(displayName || (posStreamingParams.i18n?.anonymous || 'Invitado'));
            $selectedCustomerAvatar.attr('src', customerData.avatar_url || posStreamingParams.default_avatar_url || '');
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
        isLoadingCustomerAction = true; showLoading(posStreamingParams.i18n?.loading_customer_data || 'Cargando datos...');
        getCustomerData(currentCustomerId)
            .done(customerData => {
                hideLoading(); populateCustomerForm(customerData);
                if (typeof tb_show !== 'undefined') {
                    tb_show(posStreamingParams.i18n?.edit_customer || 'Editar Cliente', '#TB_inline?width=600&height=350&inlineId=pos-customer-modal-content', null); // Ajustar width/height
                    setTimeout(initializeIntlTelInput, 150);
                } else { console.error('Thickbox (tb_show) no está definido.'); if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudo abrir el editor.', 'error'); }
            })
            .fail(error => {
                hideLoading(); console.error("Error fetching customer data:", error);
                const errorMsg = error?.responseJSON?.message || posStreamingParams.i18n?.error_general || 'Error al cargar datos.';
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
            showCustomerFormFeedback(posStreamingParams.i18n?.customer_required_fields + ' (Nombre)', true);
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
            meta_data: [{ key: 'pos_customer_avatar_id', value: $customerAvatarIdInput.val() || '' }]
        };
        const customerId = $customerIdInput.val(); const isEditing = !!customerId;
        console.log('Saving customer...', customerData, 'Is Editing:', isEditing);
        isLoadingCustomerAction = true; showLoading(posStreamingParams.i18n?.saving || 'Guardando...');
        $saveCustomerBtn.prop('disabled', true);
        saveCustomerData(customerData, customerId)
            .done(savedCustomer => {
                console.log('Save successful:', savedCustomer); hideLoading();
                if (typeof tb_remove !== 'undefined') tb_remove();
                updateSelectedCustomerDisplay(savedCustomer);
                if (typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: posStreamingParams.i18n?.customer_saved_success || 'Cliente guardado', timer: 1500, showConfirmButton: false }); }
                $customerSearchInput.val(''); $customerSearchResults.hide().empty();
            })
            .fail(error => {
                console.error('Save failed:', error); hideLoading();
                const errorMsg = error?.responseJSON?.message || posStreamingParams.i18n?.customer_saved_error || 'Error al guardar.';
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
        $selectedCustomerAvatar.attr('src', posStreamingParams.default_avatar_url || '');
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
            title: posStreamingParams.i18n?.select_avatar_title || 'Seleccionar Avatar',
            button: { text: posStreamingParams.i18n?.use_this_avatar || 'Usar imagen' },
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
        $customerAvatarPreview.attr('src', posStreamingParams.default_avatar_url || '');
        $customerAvatarIdInput.val(''); $removeAvatarBtn.hide(); console.log('Avatar removed');
    }

    function searchCustomers(searchTerm) {
        const url = `${posStreamingParams.rest_url}customers?search=${encodeURIComponent(searchTerm)}&per_page=10`;
        console.log(`API Call (Customer Search): GET ${url}`);
        return $.ajax({ url: url, method: 'GET', beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posStreamingParams.nonce) });
    }

    function getCustomerData(customerId) {
        const url = `${posStreamingParams.rest_url}customers/${customerId}`;
        console.log(`API Call (Get Customer): GET ${url}`);
        return $.ajax({ url: url, method: 'GET', beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posStreamingParams.nonce) });
    }

    function saveCustomerData(data, customerId = null) {
        const method = customerId ? 'PUT' : 'POST';
        const url = customerId ? `${posStreamingParams.rest_url}customers/${customerId}` : `${posStreamingParams.rest_url}customers`;
        console.log(`API Call (Save Customer): ${method} ${url}`, data);
        return $.ajax({
            url: url, method: method, beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posStreamingParams.nonce),
            contentType: 'application/json', data: JSON.stringify(data)
        });
    }

    function handleCustomerSearchInput() {
        const searchTerm = $customerSearchInput.val().trim(); clearTimeout(customerDebounceTimer);
        if (searchTerm.length < 2) { $customerSearchResults.hide().empty(); return; }
        customerDebounceTimer = setTimeout(() => {
            console.log(`Debounce: Buscando cliente "${searchTerm}"`);
            $customerSearchResults.html(`<li class="loading">${posStreamingParams.i18n?.searching || 'Buscando...'}</li>`).show();
            searchCustomers(searchTerm)
                .done(results => { renderCustomerSearchResults(results); })
                .fail(error => { console.error('Error buscando clientes:', error); $customerSearchResults.html(`<li class="error">${posStreamingParams.i18n?.search_error || 'Error al buscar.'}</li>`).show(); });
        }, DEBOUNCE_DELAY);
    }

    function renderCustomerSearchResults(results) {
        $customerSearchResults.empty();
        if (!Array.isArray(results) || results.length === 0) {
            $customerSearchResults.html(`<li class="no-results">${posStreamingParams.i18n?.no_customers_found || 'No se encontraron clientes.'}</li>`).show(); return;
        }
        results.forEach(customer => {
            const displayName = `${customer.first_name || ''} ${customer.last_name || ''}`.trim();
            const email = customer.email || ''; const phone = customer.phone || '';
            const avatarUrl = customer.avatar_url || posStreamingParams.default_avatar_url || '';
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

    // funciones pagos
    /**
     * Muestra u oculta los campos de suscripción según el tipo de venta seleccionado.
     */
    function handleSaleTypeChange() {
        const selectedType = $saleTypeSelect.val();
        if (selectedType === 'subscription') {
            $subscriptionFields.slideDown(); // Muestra con animación
        } else {
            $subscriptionFields.slideUp(); // Oculta con animación
        }
        console.log('Tipo de venta cambiado a:', selectedType);
    }

    /**
     * Maneja el clic en el botón "Completar Venta".
     * Recopila todos los datos y los envía a la API para crear el pedido.
     */
    function handleCompleteSale() {
        if (isLoadingCheckoutAction || completeSaleButton.prop('disabled')) {
            console.warn("Checkout en progreso o botón deshabilitado.");
            return;
        }

        // 1. Validaciones básicas
        if (cart.length === 0) {
            Swal.fire('Error', 'El carrito está vacío.', 'error');
            return;
        }
        if (!currentCustomerId) {
            Swal.fire('Error', 'No se ha seleccionado un cliente.', 'error');
            return;
        }
        const selectedPaymentMethod = $paymentMethodSelect.val();
        if (!selectedPaymentMethod) {
             Swal.fire('Error', 'Selecciona un método de pago.', 'error');
             return;
        }

        // 2. Recopilar datos del pedido
        const saleType = $saleTypeSelect.val();
        let subscriptionData = null;

        if (saleType === 'subscription') {
            const title = $subscriptionTitle.val().trim();
            const expiryDate = $subscriptionExpiryDate.val();
            const color = $subscriptionColor.val();

            // Validar campos de suscripción
            if (!title || !expiryDate) {
                Swal.fire('Error', 'Por favor, completa el título y la fecha de vencimiento de la suscripción.', 'error');
                $subscriptionFields.find('input:invalid').first().focus(); // Enfocar primer campo inválido
                return;
            }
            subscriptionData = {
                title: title,
                expiry_date: expiryDate,
                color: color
            };
        }

        const orderData = {
            customer_id: currentCustomerId,
            payment_method: selectedPaymentMethod,
            payment_method_title: $paymentMethodSelect.find('option:selected').text(), // Título legible
            set_paid: true, // Marcar como pagado (ajustar según método de pago si es necesario)
            billing: {}, // Podríamos obtener datos de facturación del cliente si es necesario
            shipping: {}, // Probablemente no necesario para servicios digitales
            line_items: cart.map(item => ({
                product_id: item.product_id,
                variation_id: item.variation_id || 0, // 0 si no es variación
                quantity: item.quantity,
                // --- Manejo del Precio Personalizado (IMPORTANTE) ---
                // Opción A: Enviar precio personalizado como metadato
                // total: (item.original_price * item.quantity).toFixed(2), // Usar precio original para el total de WC
                // meta_data: [
                //     { key: '_pos_custom_price', value: item.current_price.toFixed(2) }
                // ]
                // Opción B: Enviar precio personalizado directamente (Requiere filtro PHP)
                 total: (item.current_price * item.quantity).toFixed(2), // ¡OJO! WC puede recalcular esto
                 price: item.current_price // Enviar precio unitario personalizado
                // ----------------------------------------------------
            })),
            meta_data: [ // Metadatos a nivel de pedido
                { key: '_pos_sale_type', value: saleType }
            ],
            coupon_lines: []
        };

        if (appliedCoupon) {
            orderData.coupon_lines.push({ code: appliedCoupon.code });
        }

        // Añadir metadatos de suscripción si aplica
        if (subscriptionData) {
            orderData.meta_data.push({ key: '_pos_subscription_title', value: subscriptionData.title });
            orderData.meta_data.push({ key: '_pos_subscription_expiry_date', value: subscriptionData.expiry_date });
            orderData.meta_data.push({ key: '_pos_subscription_color', value: subscriptionData.color });
        }

        // Añadir cupón si aplica (requiere lógica de applyCoupon)
        // const appliedCoupon = getCurrentAppliedCoupon(); // Necesitarías esta función
        // if (appliedCoupon) {
        //     orderData.coupon_lines = [{ code: appliedCoupon.code }];
        // }

        console.log("Datos del pedido a enviar:", orderData);

        // 3. Llamar a la API para crear el pedido
        isLoadingCheckoutAction = true;
        completeSaleButton.prop('disabled', true).text(posStreamingParams.i18n?.processing || 'Procesando...');
        showLoading(posStreamingParams.i18n?.creating_order || 'Creando pedido...');

        // --- Placeholder: Necesitas un endpoint API para crear el pedido ---
        createOrder(orderData)
            .done(response => {
                hideLoading();
                Swal.fire({
                    icon: 'success',
                    title: posStreamingParams.i18n?.order_created_success || '¡Pedido Creado!',
                    text: `Pedido #${response.id} creado correctamente.`, // Asumiendo que la API devuelve el pedido creado
                    showConfirmButton: true // O false y timer
                });
                // Limpiar estado después de éxito
                resetPOSState();
            })
            .fail(error => {
                hideLoading();
                console.error("Error creando pedido:", error);
                const errorMsg = error?.responseJSON?.message || posStreamingParams.i18n?.order_created_error || 'Error al crear el pedido.';
                Swal.fire('Error', errorMsg, 'error');
            })
            .always(() => {
                isLoadingCheckoutAction = false;
                completeSaleButton.text(posStreamingParams.i18n?.complete_sale || 'Completar Venta');
                // Habilitar/deshabilitar se maneja en updateCheckoutButtonState
                updateCheckoutButtonState();
            });
        // --- Fin Placeholder ---
    }

    /**
     * Carga las pasarelas de pago disponibles desde la API y puebla el select.
     */
    async function loadPaymentMethods() {
        const placeholderOption = `<option value="" disabled selected>${posStreamingParams.i18n?.loading_payment_methods || 'Cargando métodos...'}</option>`;
        $paymentMethodSelect.html(placeholderOption).prop('disabled', true); // Mostrar carga y deshabilitar

        // Asegurarse de que los parámetros necesarios están definidos
        if (typeof posStreamingParams === 'undefined' || !posStreamingParams.rest_url || !posStreamingParams.nonce) {
            console.error('loadPaymentMethods: posStreamingParams no está definido o incompleto.');
            $paymentMethodSelect.html(`<option value="" disabled selected>${posStreamingParams.i18n?.error_loading_payment_methods || 'Error config.'}</option>`);
            return;
        }

        try {
            const apiUrl = `${posStreamingParams.rest_url}payment-gateways`;
            console.log(`API Call (Payment Gateways): GET ${apiUrl}`);
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': posStreamingParams.nonce // Incluir Nonce
                }
            });

            // Verificar si la respuesta es OK (status 200-299)
            if (!response.ok) {
                    // Intentar obtener mensaje de error del cuerpo si es JSON
                    let errorMsg = `Error ${response.status}`;
                    try {
                        const errorData = await response.json();
                        errorMsg = errorData.message || errorMsg;
                    } catch(e) {
                        // El cuerpo no era JSON o hubo otro error al leerlo
                        console.warn("No se pudo parsear el cuerpo de la respuesta de error.");
                    }
                throw new Error(errorMsg);
            }

            const gateways = await response.json();

            if (Array.isArray(gateways) && gateways.length > 0) {
                $paymentMethodSelect.empty(); // Limpiar placeholder de carga
                // Añadir opción "Seleccionar..."
                $paymentMethodSelect.append(`<option value="" disabled selected>${posStreamingParams.i18n?.select_payment_method || '-- Selecciona Método --'}</option>`);
                // Añadir cada pasarela como opción
                gateways.forEach(gateway => {
                    // Escapar el título por seguridad, aunque debería venir limpio del backend
                    const escapedTitle = $('<div>').text(gateway.title).html();
                    $paymentMethodSelect.append(`<option value="${gateway.id}">${escapedTitle}</option>`);
                });
                $paymentMethodSelect.prop('disabled', false); // Habilitar select
                console.log('Métodos de pago cargados:', gateways);
            } else {
                // No se encontraron pasarelas activas
                $paymentMethodSelect.html(`<option value="" disabled selected>${posStreamingParams.i18n?.no_payment_methods || 'No hay métodos'}</option>`);
                console.warn('No se encontraron métodos de pago activos.');
            }

        } catch (error) {
            // Error durante la llamada fetch o procesamiento
            console.error('Error cargando métodos de pago:', error);
            $paymentMethodSelect.html(`<option value="" disabled selected>${posStreamingParams.i18n?.error_loading_payment_methods || 'Error al cargar'}</option>`);
        }
    }
    
    /**
     * Envía los datos del pedido a la API REST para crear un pedido WC.
     * @param {object} orderData - Datos del pedido.
     * @returns {jqXHR}
     */
    function createOrder(orderData) {
        // --- ¡¡¡IMPLEMENTACIÓN REAL NECESARIA EN pos-api.php y aquí!!! ---
        const url = `${posStreamingParams.rest_url}orders`; // Endpoint hipotético
        console.log(`API Call (Create Order): POST ${url}`, orderData);
        return $.ajax({
            url: url, method: 'POST',
            beforeSend: xhr => xhr.setRequestHeader('X-WP-Nonce', posStreamingParams.nonce),
            contentType: 'application/json',
            data: JSON.stringify(orderData)
        });
        // --- Fin Implementación Real ---
    }

    // --- **NUEVO:** Función para resetear estado después de venta ---
    function resetPOSState() {
        // Limpiar carrito
        cart = [];
        updateCartUI();
        calculateTotals();

        // Deseleccionar cliente
        handleDeselectCustomer();

        // Resetear campos de checkout
        $saleTypeSelect.val('direct'); // Volver a 'Directo'
        handleSaleTypeChange(); // Ocultar campos de suscripción
        $subscriptionTitle.val('');
        $subscriptionExpiryDate.val('');
        $subscriptionColor.val('#3a87ad'); // Resetear color
        $paymentMethodSelect.val(''); // Resetear método de pago
        $couponCodeInput.val(''); // Limpiar cupón
        $couponMessage.empty().hide(); // Limpiar mensaje cupón

        // Resetear cupón 
        appliedCoupon = null; // Limpiar estado del cupón
        $couponCodeInput.val('').prop('disabled', false); // Limpiar y habilitar input
        $couponMessage.empty().hide(); // Limpiar mensaje
        $applyCouponButton.show()

        // Opcional: Limpiar búsqueda de productos
        // productSearchInput.val('');
        // fetchProducts('', 1, true); // Volver a mostrar destacados

        console.log("Estado del POS reseteado después de la venta.");
    }


    // --- Event Listeners ---
    function bindEvents() {
        // Productos
        productSearchInput.on('input', function () {
            const searchTerm = $(this).val().trim(); clearTimeout(productDebounceTimer);
            productDebounceTimer = setTimeout(() => { const searchChanged = searchTerm !== currentSearchTerm; const shouldSearchFeatured = !searchTerm; if (searchChanged) { fetchProducts(searchTerm, 1, shouldSearchFeatured); } }, DEBOUNCE_DELAY);
        });
        productListContainer.on('click', 'button.add-simple-to-cart', handleAddSimpleClick);
        productListContainer.on('click', 'button.add-variation-to-cart', handleAddVariationClick);

        // Carrito
        cartItemsContainer.on('click', '.pos-cart-item-remove', handleRemoveCartItemClick);
        cartItemsContainer.on('input', '.pos-cart-item-price-input', handleCartPriceInputChange); // Listener para precio editable

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
        $(document).on('click', function(event) { // Ocultar resultados al hacer clic fuera
            if (!$(event.target).closest('.pos-customer-search').length) { $customerSearchResults.hide(); }
        });

        // --- NUEVO: Listeners para Cupón ---
        $applyCouponButton.on('click', handleApplyCoupon);
        // Listener delegado para el botón quitar cupón (ya que se añade dinámicamente)
        $couponMessage.on('click', '.pos-remove-coupon-button', handleRemoveCoupon);
        // Opcional: Aplicar cupón al presionar Enter en el input
        $couponCodeInput.on('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Evitar submit si estuviera en un form
                handleApplyCoupon();
            }
        });

        // Checkout
        $saleTypeSelect.on('change', handleSaleTypeChange); // <-- NUEVO Listener
        completeSaleButton.on('click', handleCompleteSale); // <-- NUEVO Listener (para placeholder)
        // Añadir listeners para $applyCouponButton y $paymentMethodSelect si es necesario

    }

    // --- Añadir esta NUEVA función a app.js (o modificar si ya existe un placeholder) ---
    
    /**
     * Inicializa la instancia de FullCalendar.
     */
    function initCalendar() {
        const calendarEl = document.getElementById('pos-calendar');
    
        if (!calendarEl) {
            console.error('Elemento del calendario #pos-calendar no encontrado.');
            return;
        }
    
        // Verificar si FullCalendar está disponible
        if (typeof FullCalendar === 'undefined') {
             console.error('Librería FullCalendar no cargada.');
             // Mostrar mensaje en el div del calendario
             calendarEl.innerHTML = '<p style="color:red;">Error: Librería del calendario no disponible.</p>';
             return;
        }
    
        // Asegurarse de que los parámetros necesarios están definidos
        if (typeof posStreamingParams === 'undefined' || !posStreamingParams.rest_url || !posStreamingParams.nonce) {
            console.error('initCalendar: posStreamingParams no está definido o incompleto.');
            calendarEl.innerHTML = '<p style="color:red;">Error de configuración para cargar eventos.</p>';
            return;
        }
    
    
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Vista inicial
            locale: 'es', // Idioma español
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek' // Vistas disponibles
            },
            buttonText: { // Textos botones en español
                 today:    'Hoy',
                 month:    'Mes',
                 week:     'Semana',
                 day:      'Día',
                 list:     'Lista'
            },
            navLinks: true, // Permite hacer clic en días/semanas para navegar
            editable: false, // No permitir arrastrar eventos (solo visualización)
            dayMaxEvents: true, // Permite "+ more" link cuando hay muchos eventos
            events: {
                url: `${posStreamingParams.rest_url}calendar-events?_wpnonce=${posStreamingParams.nonce}`,
                method: 'GET',
                // headers: { // Enviar nonce para autenticación
                //     'X-WP-Nonce': posStreamingParams.nonce
                // },
                failure: function(error) { // Manejo de errores al cargar eventos
                    console.error('Error cargando eventos del calendario:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'No se pudieron cargar los eventos del calendario.', 'error');
                    }
                    // Podrías mostrar un mensaje en el propio calendario
                    // calendarEl.innerHTML = '<p style="color:red;">Error al cargar eventos.</p>';
                },
                // Opcional: Cambiar color de texto si el fondo es oscuro
                // eventDataTransform: function(eventInfo) {
                //     // Lógica para determinar si el color de fondo es oscuro
                //     // y añadir eventInfo.textColor = '#ffffff';
                //     return eventInfo;
                // }
            },
            // Acción al hacer clic en un evento
            eventClick: function(info) {
                info.jsEvent.preventDefault(); // Prevenir comportamiento por defecto del navegador
    
                const eventData = info.event;
                const props = eventData.extendedProps;
    
                console.log('Evento clickeado:', eventData);
    
                if (props.order_url) {
                    // Abrir la URL de edición del pedido en una nueva pestaña
                    window.open(props.order_url, '_blank');
                } else if (props.order_id) {
                     // Fallback si no hay URL directa (construir manualmente)
                     // Necesitaríamos admin_url en posStreamingParams
                     if (posStreamingParams.admin_url) {
                          // Determinar si usar URL HPOS o tradicional (esto es simplificado)
                          const editUrl = `${posStreamingParams.admin_url}admin.php?page=wc-orders&action=edit&id=${props.order_id}`;
                          // const editUrl = `${posStreamingParams.admin_url}post.php?post=${props.order_id}&action=edit`; // Tradicional
                          window.open(editUrl, '_blank');
                     } else {
                          Swal.fire('Info', `Vencimiento: ${eventData.title}\nPedido ID: ${props.order_id}`, 'info');
                     }
                } else {
                     Swal.fire('Info', `Vencimiento: ${eventData.title}`, 'info');
                }
            },
            loading: function(isLoading) { // Indicador visual de carga
                const calendarWrapper = $(calendarEl).closest('.pos-section-content');
                if (isLoading) {
                    console.log('Calendario cargando eventos...');
                    calendarWrapper.addClass('loading-calendar'); // Añadir clase para mostrar spinner CSS
                } else {
                    console.log('Calendario terminó de cargar eventos.');
                    calendarWrapper.removeClass('loading-calendar'); // Quitar clase
                }
            }
        });
    
        calendar.render(); // Renderizar el calendario
        console.log('FullCalendar inicializado.');
    }
    
    

    // --- Inicialización ---
    function init() {
        console.log('POS Streaming App Initializing...');
        if (typeof posStreamingParams === 'undefined' || !posStreamingParams.rest_url || !posStreamingParams.nonce) {
            console.error('FATAL: posStreamingParams no disponible. Abortando.');
            $('body').prepend('<div class="notice notice-error"><p>Error crítico: Faltan parámetros de configuración del plugin POS Streaming.</p></div>');
            return;
        }
        console.log('posStreamingParams OK:', posStreamingParams);
        productSearchInput.attr('placeholder', posStreamingParams.i18n?.search_placeholder || 'Buscar producto...');
        $customerSearchInput.attr('placeholder', posStreamingParams.i18n?.search_customer_placeholder || 'Buscar cliente...');

        bindEvents(); // Vincular todos los eventos
        fetchProducts('', 1, true); // Cargar destacados
        updateCartUI(); 
        calculateTotals(); 
        handleDeselectCustomer();
        handleSaleTypeChange();
        loadPaymentMethods();
        initCalendar(); 
        console.log('POS Streaming App Initialized.');
    }

    // Ejecutar inicialización
    init();

}); // Fin jQuery ready
