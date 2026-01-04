// pos.js
class POSSystem {
    constructor() {
        this.cart = [];
        this.subtotal = 0;
        this.tax = 0;
        this.discount = 0;
        this.total = 0;
        this.vatRate = 18; // Default VAT rate
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadCart();
    }
    
    setupEventListeners() {
        // Product search
        $('#productSearch').on('input', this.searchProducts.bind(this));
        
        // Quantity change
        $(document).on('click', '.quantity-btn', this.handleQuantityChange.bind(this));
        
        // Discount change
        $('#discountInput').on('input', this.updateDiscount.bind(this));
        
        // VAT toggle
        $('#vatCheckbox').on('change', this.toggleVAT.bind(this));
        
        // Process sale
        $('#processSale').on('click', this.processSale.bind(this));
    }
    
    searchProducts(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        // Filter products (in real app, this would be AJAX call)
        const filtered = this.allProducts.filter(product => 
            product.name.toLowerCase().includes(searchTerm) ||
            product.serial.toLowerCase().includes(searchTerm)
        );
        
        this.displayProducts(filtered);
    }
    
    addToCart(product) {
        // Check if product already in cart
        const existingItem = this.cart.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                discount: 0
            });
        }
        
        this.saveCart();
        this.updateCartDisplay();
        this.calculateTotals();
    }
    
    updateCartDisplay() {
        const cartTable = $('#cartTable tbody');
        cartTable.empty();
        
        this.cart.forEach(item => {
            const row = `
                <tr data-id="${item.id}">
                    <td>${item.name}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary quantity-btn minus" data-action="decrease">-</button>
                            <input type="number" class="form-control text-center quantity-input" 
                                   value="${item.quantity}" min="1" style="width: 60px;">
                            <button class="btn btn-outline-secondary quantity-btn plus" data-action="increase">+</button>
                        </div>
                    </td>
                    <td>$${item.price.toFixed(2)}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm discount-input" 
                               value="${item.discount}" min="0" max="${item.price * item.quantity}" 
                               style="width: 80px;" data-id="${item.id}">
                    </td>
                    <td>$${(item.price * item.quantity - item.discount).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            cartTable.append(row);
        });
    }
    
    calculateTotals() {
        this.subtotal = this.cart.reduce((sum, item) => {
            return sum + (item.price * item.quantity - item.discount);
        }, 0);
        
        this.tax = $('#vatCheckbox').is(':checked') ? this.subtotal * (this.vatRate / 100) : 0;
        
        const overallDiscount = parseFloat($('#discountInput').val()) || 0;
        this.discount = overallDiscount;
        
        this.total = this.subtotal + this.tax - this.discount;
        
        // Update display
        $('#subtotalAmount').text('$' + this.subtotal.toFixed(2));
        $('#taxAmount').text('$' + this.tax.toFixed(2));
        $('#discountAmount').text('$' + this.discount.toFixed(2));
        $('#totalAmount').text('$' + this.total.toFixed(2));
    }
    
    processSale() {
        const customerId = $('#customerSelect').val();
        const paymentMethod = $('#paymentMethod').val();
        
        if (this.cart.length === 0) {
            alert('Please add items to cart');
            return;
        }
        
        // Prepare data for AJAX call
        const saleData = {
            customer_id: customerId,
            payment_method: paymentMethod,
            cart: this.cart,
            subtotal: this.subtotal,
            tax: this.tax,
            discount: this.discount,
            total: this.total,
            vat_applied: $('#vatCheckbox').is(':checked')
        };
        
        // AJAX call to save sale
        $.ajax({
            url: 'api/process_sale.php',
            method: 'POST',
            data: JSON.stringify(saleData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Clear cart
                    this.cart = [];
                    this.saveCart();
                    this.updateCartDisplay();
                    this.calculateTotals();
                    
                    // Show success and print invoice
                    alert('Sale processed successfully!');
                    this.printInvoice(response.invoice_id);
                } else {
                    alert('Error: ' + response.message);
                }
            }.bind(this),
            error: function(xhr, status, error) {
                alert('Error processing sale: ' + error);
            }
        });
    }
    
    printInvoice(invoiceId) {
        // Open print window or thermal printer interface
        window.open('print_invoice.php?id=' + invoiceId, '_blank');
    }
}

// Initialize POS system when document is ready
$(document).ready(function() {
    window.posSystem = new POSSystem();
});