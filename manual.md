# 📖 Manual de Usuario - POS Base

¡Bienvenido a POS Base! Este manual te guiará paso a paso a través de las funcionalidades del sistema de Punto de Venta (POS) integrado en tu panel de WordPress.

---

## 1. ¿Qué es POS Base? 🤔

POS Base es tu centro de control para realizar ventas directamente desde WordPress. Funciona como una caja registradora digital, utilizando los productos y clientes que ya tienes en WooCommerce. Su diseño modular permite añadir funcionalidades extra (como gestión de perfiles de streaming o botones de WhatsApp) para adaptarse perfectamente a las necesidades de tu negocio.

**Beneficios Clave:**

*   **Eficiencia:** Realiza ventas rápidamente sin salir de WordPress.
*   **Integración:** Usa tus productos, clientes y métodos de pago de WooCommerce.
*   **Flexibilidad:** Se adapta a diferentes tipos de venta (directa, suscripción, crédito).
*   **Seguimiento:** Visualiza vencimientos en el calendario y revisa un historial detallado de ventas.
*   **Extensible:** Funcionalidades adicionales disponibles a través de módulos.

---

## 2. Acceso al Sistema 🔑

*   **Requisito:** WooCommerce debe estar instalado y activo.
*   **Instalación:** El plugin "POS Base" ya ha sido instalado por el administrador.
*   **Acceso:** En el menú lateral izquierdo de tu panel de WordPress, busca y haz clic en "**POS Base**". Esto te llevará a la interfaz principal del punto de venta.

---

## 3. Explorando la Interfaz Principal 🖥️

La interfaz de POS Base se organiza en tres pestañas principales:

*   **POS:** El corazón del sistema, donde crearás y finalizarás las ventas.
*   **Calendario:** Una vista mensual para seguir los vencimientos de las "Suscripciones Base".
*   **Ventas:** Un registro detallado de todas las transacciones realizadas a través del POS.

---

## 4. Realizar una Venta (Pestaña POS) 🛒

Esta es el área principal de trabajo. Sigue estos pasos para completar una venta:

### 4.1. Cliente: Identifica a tu Comprador 👤

*   **Buscar Cliente Existente:** Utiliza la barra "**Buscar Cliente...**" (en la columna derecha). Puedes buscar por nombre, apellido, email o teléfono. Haz clic en el resultado correcto para seleccionarlo.
*   **Añadir Nuevo Cliente:** Si es un cliente nuevo, haz clic en el botón `➕ Añadir Nuevo Cliente`. Rellena el formulario (Nombre, Apellido, Email, Teléfono, Nota opcional).
    *   *Avatar (Opcional):* Haz clic en "**Cambiar Imagen**" para asignarle una foto.
    *   Guarda haciendo clic en `💾 Guardar Cliente`. El nuevo cliente quedará seleccionado.
*   **Editar Cliente:** Con un cliente seleccionado, haz clic en `✏️ Editar` para modificar sus datos.
*   **Cambiar Cliente:** Haz clic en `🔄 Cambiar` para volver a buscar o seleccionar otro.
*   **Venta como Invitado:** Si no seleccionas ningún cliente, la venta se asociará a un "Invitado".

### 4.2. Productos: Añade Artículos al Carrito 🛍️

*   **Buscar Producto:** Usa la barra "**Buscar Producto...**" (en la columna izquierda). Puedes buscar por nombre o SKU.
*   **Productos Destacados:** Inicialmente, verás los productos marcados como "destacados" en WooCommerce.
*   **Añadir Producto Simple:** Haz clic en el botón `➕ Añadir` junto al producto.
*   **Añadir Producto Variable:** Si el producto tiene opciones (talla, color...), selecciona la variante deseada en el desplegable que aparece y luego haz clic en `➕ Añadir`.

### 4.3. Carrito: Revisa y Ajusta la Compra 📝

La sección "Carrito" (columna derecha) muestra los productos seleccionados:

*   **Cantidad:** Usa los botones `➕` / `➖` o escribe directamente en el campo numérico.
*   **Precio Unitario:** ¡Puedes modificarlo! Haz clic sobre el precio en el carrito, introduce el nuevo valor y presiona Enter (o haz clic fuera).
*   **Eliminar:** Haz clic en el icono de papelera (`🗑️`) para quitar un producto.
*   **Totales:** El subtotal y el total se recalculan al instante.

### 4.4. Pago y Finalización: Completa la Transacción 💳

En la sección "Pago" (debajo del carrito):

1.  **Tipo de Venta:** Elige la naturaleza de la venta:
    *   `Directo`: Venta estándar de productos.
    *   `Suscripción`: Para servicios o productos recurrentes (activa campos adicionales).
    *   `Crédito`: Si el pago se realizará después (el pedido queda "En Espera").
2.  **Detalles de Suscripción Base (Si Tipo = Suscripción):**
    *   `Título (Calendario)`: Nombre para identificar este vencimiento en el calendario (ej: "Plan Básico - Cliente X").
    *   `Fecha Vencimiento`: Selecciona cuándo expira esta suscripción base.
    *   `Color Evento`: Elige un color para el evento en el calendario.
3.  **⭐ Funcionalidad del Módulo Streaming (Si Tipo = Suscripción):**
    *   Si el módulo "Streaming" está activo, aparecerá un desplegable adicional: "**Perfil Asignado**".
    *   **Uso:** Haz clic en el desplegable y selecciona el perfil específico (ej: "Juan (Netflix Familiar)") que estás asignando con esta venta. Solo aparecerán los perfiles marcados como "disponibles".
    *   **Importancia:** Asegúrate de seleccionar el perfil correcto. Esto lo marcará como "asignado" y lo vinculará a este pedido.
4.  **Método de Pago:** Selecciona cómo te pagó el cliente (Efectivo, Transferencia, Tarjeta POS, etc.).
5.  **Cupón:** Ingresa un código de cupón de WooCommerce y haz clic en `✔️ Aplicar`. El descuento se reflejará. Para quitarlo, haz clic en `❌ Quitar cupón`.
6.  **Nota del Pedido:** Añade comentarios internos sobre la venta si es necesario.
7.  **Completar Venta:** Revisa que todo sea correcto y haz clic en el botón `✅ Completar Venta`. El pedido se creará en WooCommerce y la interfaz se limpiará para la siguiente venta.

---

## 5. Consultar el Calendario (Pestaña Calendario) 🗓️

*   Esta pestaña te ofrece una vista mensual.
*   Muestra automáticamente los eventos correspondientes a las **fechas de vencimiento** de las "Suscripciones Base" que creaste desde el POS.
*   Cada evento tiene el título y color que definiste, facilitando el seguimiento visual.
*   Puedes hacer clic en un evento para obtener un enlace rápido al pedido asociado.

---

## 6. Revisar Ventas Anteriores (Pestaña Ventas) 📊

*   Aquí encontrarás un historial completo y detallado de todas las ventas realizadas a través del POS.
*   **Tabla Interactiva:**
    *   **Buscar:** Filtra rápidamente por cualquier dato (Nº Pedido, Cliente, etc.).
    *   **Ordenar:** Haz clic en las cabeceras (Pedido #, Fecha, Cliente, Total, Tipo) para ordenar.
    *   **Paginación:** Navega fácilmente entre páginas si hay muchas ventas.
*   **Columnas Clave:**
    *   `Pedido #`: Enlace directo al pedido en WooCommerce.
    *   `Fecha`, `Cliente`, `Total`.
    *   `Tipo (POS)`: Directo, Suscripción o Crédito.
    *   `Notas`: Última nota del pedido.
    *   `Meta`: **Información adicional importante.**
        *   Para "Suscripción Base": Muestra Título, Vencimiento y Color.
        *   **Si Módulo Streaming activo:** Muestra el **nombre del Perfil Streaming asignado** a esa venta.

---

## 7. Módulos Activos y Funcionalidades Adicionales ✨

POS Base puede tener funcionalidades extra gracias a los módulos activados por tu administrador. Aquí te explicamos cómo interactuar con los módulos incluidos:

### 7.1. Módulo Streaming (Gestión de Perfiles) 🎬

*   **¿Cuándo lo usas?** Principalmente durante el **checkout en la pestaña POS**, si seleccionas `Tipo de Venta = Suscripción`.
*   **¿Qué hace?** Te permite asignar un "Perfil Streaming" específico (previamente creado por el administrador, ej: un perfil de Netflix) a la venta.
*   **¿Cómo lo usas?**
    1.  Selecciona `Tipo de Venta = Suscripción`.
    2.  Busca y selecciona el cliente.
    3.  Añade los productos/servicios al carrito.
    4.  En la sección "Pago", localiza el desplegable "**Perfil Asignado**".
    5.  Haz clic y elige el perfil correcto de la lista. Verás el nombre del perfil y, entre paréntesis, la cuenta a la que pertenece (ej: "Perfil Ana (Cuenta Familiar Spotify)"). Solo aparecerán los perfiles disponibles.
    6.  Completa el resto de los campos y finaliza la venta.
*   **Resultado:** El perfil elegido queda vinculado al pedido y marcado como "asignado", y podrás ver qué perfil se asignó en la columna "Meta" de la pestaña "Ventas".

### 7.2. Módulo WhatsApp (Botones de Contacto) 📱

*   **¿Qué hace?** Este módulo añade botones de contacto de WhatsApp en **diferentes partes del sitio web público** (no dentro de la interfaz del POS que tú usas). Su objetivo es facilitar que los *clientes* inicien una conversación.
*   **¿Dónde aparecen los botones (para los clientes)?**
    *   **Botón Flotante:** Un icono de WhatsApp visible en todas las páginas del sitio web.
    *   **Página de Producto:** Un botón "Consultar por WhatsApp" cerca de la descripción del producto.
    *   **Página de Checkout:** Un botón "Completar Pedido por WhatsApp" cerca del final del proceso de pago.
*   **¿Afecta tu trabajo en el POS?** Directamente no. No verás opciones de WhatsApp dentro de la interfaz del POS. Sin embargo, es útil saber que existen estos botones por si un cliente menciona haber contactado por esa vía. La configuración (número de teléfono, mensajes) la gestiona el administrador.

---

## 8. Soporte y Ayuda ❓

Si encuentras algún problema, tienes dudas sobre cómo usar una función específica, o ves opciones que no se describen aquí (probablemente de otros módulos), por favor, contacta al **administrador de tu sitio web**. Él/Ella podrá ayudarte o contactar al soporte técnico si es necesario.

---
