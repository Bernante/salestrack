/**
 * SalesTrack - POS Sales Cart Manager & Product Cards UI
 */
class SalesCartManager {
    constructor(productsData) {
        this.products = productsData || [];
        this.cart = [];
        this.selectedProduct = null;
        this.selectedVariant = null;
        this.init();
    }

    init() {
        this.cartTable = document.getElementById('cartTableBody');
        this.emptyRow = document.getElementById('emptyCartRow');
        this.totalEl = document.getElementById('totalAmountDisplay');
        this.paidInput = document.getElementById('amountPaidInput');
        this.changeEl = document.getElementById('changeAmountDisplay');
        this.completeBtn = document.getElementById('completeSaleBtn');
        this.form = document.getElementById('saleForm');
        this.cartInput = document.getElementById('cartItemsInput');
        this.cartBadge = document.getElementById('cartCountBadge');
        this.searchInput = document.getElementById('productSearchInput');

        // Modal Elements
        this.modal = document.getElementById('variantModal');
        this.modalContent = document.getElementById('variantModalContent');
        this.modalImg = document.getElementById('modalProductImg');
        this.modalName = document.getElementById('modalProductName');
        this.modalVariantList = document.getElementById('modalVariantList');
        this.modalQtyInput = document.getElementById('modalQtyInput');
        this.modalQtyMinus = document.getElementById('modalQtyMinus');
        this.modalQtyPlus = document.getElementById('modalQtyPlus');
        this.confirmAddBtn = document.getElementById('confirmAddToCartBtn');
        this.closeModalBtn = document.getElementById('closeVariantModalBtn');
        this.cancelModalBtn = document.getElementById('cancelVariantModalBtn');

        this.bindEvents();
    }

    bindEvents() {
        // Entire product card area is clickable
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                const pid = parseInt(card.dataset.id);
                this.openVariantModal(pid);
            });
        });

        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();
                document.querySelectorAll('.product-card').forEach(card => {
                    const name = card.dataset.name.toLowerCase();
                    card.style.display = name.includes(term) ? 'flex' : 'none';
                });
            });
        }

        if (this.closeModalBtn) this.closeModalBtn.addEventListener('click', () => this.closeVariantModal());
        if (this.cancelModalBtn) this.cancelModalBtn.addEventListener('click', () => this.closeVariantModal());
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) this.closeVariantModal();
            });
        }

        if (this.modalQtyMinus) {
            this.modalQtyMinus.addEventListener('click', () => {
                let current = parseInt(this.modalQtyInput.value) || 1;
                if (current > 1) this.modalQtyInput.value = current - 1;
            });
        }
        if (this.modalQtyPlus) {
            this.modalQtyPlus.addEventListener('click', () => {
                let current = parseInt(this.modalQtyInput.value) || 1;
                this.modalQtyInput.value = current + 1;
            });
        }

        // Enter key on quantity input submits item to cart
        if (this.modalQtyInput) {
            this.modalQtyInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.addSelectedToCart();
                }
            });
        }

        if (this.confirmAddBtn) {
            this.confirmAddBtn.addEventListener('click', () => this.addSelectedToCart());
        }

        if (this.paidInput) this.paidInput.addEventListener('input', () => this.calcChange());
        if (this.form) this.form.addEventListener('submit', (e) => this.submitSale(e));
    }

    openVariantModal(productId) {
        const prod = this.products.find(p => p.id === productId);
        if (!prod) return;

        this.selectedProduct = prod;
        this.selectedVariant = null;
        if (this.modalImg) this.modalImg.src = prod.image;
        if (this.modalName) this.modalName.textContent = prod.name;
        if (this.modalQtyInput) this.modalQtyInput.value = 1;
        if (this.modalVariantList) this.modalVariantList.innerHTML = '';

        if (!prod.variants || prod.variants.length === 0) {
            if (this.modalVariantList) {
                this.modalVariantList.innerHTML = '<p class="text-xs text-red-500 font-semibold p-2">No active variants available.</p>';
            }
            return;
        }

        prod.variants.forEach((v, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            const isSelected = (idx === 0);
            btn.className = `w-full px-4 py-3 border rounded-md text-left flex justify-between items-center transition cursor-pointer ${
                isSelected 
                    ? 'border-brand-500 bg-brand-50 font-bold text-brand-700 shadow-sm' 
                    : 'border-brand-200 hover:border-brand-300 font-semibold text-brand-700'
            }`;

            btn.innerHTML = `
                <span class="text-sm flex items-center gap-2">
                    ${isSelected ? '<span class="w-2.5 h-2.5 rounded-full bg-brand-500 inline-block"></span>' : '<span class="w-2.5 h-2.5 rounded-full border border-brand-200 inline-block"></span>'}
                    ${v.variant_name}
                </span>
                <span class="text-sm font-bold text-brand-500">₱${parseFloat(v.price).toFixed(2)}</span>
            `;

            btn.addEventListener('click', () => {
                this.modalVariantList.querySelectorAll('button').forEach(b => {
                    b.className = 'w-full px-4 py-3 border border-brand-200 hover:border-brand-300 rounded-md text-left flex justify-between items-center font-semibold text-brand-700 transition cursor-pointer';
                    const dot = b.querySelector('.bg-brand-500');
                    if (dot) {
                        dot.className = 'w-2.5 h-2.5 rounded-full border border-brand-200 inline-block';
                    }
                });
                btn.className = 'w-full px-4 py-3 border border-brand-500 bg-brand-50 rounded-md text-left flex justify-between items-center font-bold text-brand-700 transition cursor-pointer shadow-sm';
                const dot = btn.querySelector('.rounded-full');
                if (dot) {
                    dot.className = 'w-2.5 h-2.5 rounded-full bg-brand-500 inline-block';
                }
                this.selectedVariant = v;
            });

            this.modalVariantList.appendChild(btn);
            if (isSelected) this.selectedVariant = v;
        });

        if (this.modal) {
            this.modal.classList.remove('hidden');
            setTimeout(() => {
                if (this.modalContent) {
                    this.modalContent.classList.remove('scale-95', 'opacity-0');
                    this.modalContent.classList.add('scale-100', 'opacity-100');
                }
                if (this.modalQtyInput) {
                    this.modalQtyInput.focus();
                    this.modalQtyInput.select();
                }
            }, 50);
        }
    }

    closeVariantModal() {
        if (!this.modal) return;
        this.modalContent.classList.remove('scale-100', 'opacity-100');
        this.modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            this.modal.classList.add('hidden');
            this.selectedProduct = null;
            this.selectedVariant = null;
        }, 200);
    }

    addSelectedToCart() {
        if (!this.selectedProduct || !this.selectedVariant) {
            alert('Please select a variant.');
            return;
        }

        const qty = parseInt(this.modalQtyInput.value) || 1;
        if (qty <= 0) {
            alert('Quantity must be greater than zero.');
            return;
        }

        const vid = this.selectedVariant.id;
        const price = parseFloat(this.selectedVariant.price);
        const item = this.cart.find(i => i.vid === vid);

        if (item) {
            item.qty += qty;
            item.subtotal = item.qty * price;
        } else {
            this.cart.push({
                vid,
                pName: this.selectedProduct.name,
                vName: this.selectedVariant.variant_name,
                price,
                qty,
                subtotal: qty * price
            });
        }

        this.closeVariantModal();
        this.render();
    }

    removeFromCart(vid) {
        this.cart = this.cart.filter(i => i.vid !== vid);
        this.render();
    }

    render() {
        if (!this.cartTable) return;
        this.cartTable.innerHTML = '';

        const totalItemsCount = this.cart.reduce((sum, i) => sum + i.qty, 0);
        if (this.cartBadge) {
            this.cartBadge.textContent = `${totalItemsCount} item${totalItemsCount === 1 ? '' : 's'}`;
        }

        if (this.cart.length === 0) {
            this.cartTable.appendChild(this.emptyRow);
            this.totalEl.textContent = '₱0.00';
            this.calcChange();
            if (this.completeBtn) this.completeBtn.disabled = true;
            return;
        }

        let total = 0;
        this.cart.forEach(item => {
            total += item.subtotal;
            const tr = document.createElement('tr');
            tr.className = 'border-b border-brand-100 text-xs sm:text-sm hover:bg-brand-50 transition-colors';
            tr.innerHTML = `
                <td class="p-2 sm:p-2.5 font-bold text-brand-700">
                    <div>${item.pName}</div>
                    <div class="text-[11px] font-normal text-brand-300">${item.vName}</div>
                </td>
                <td class="p-2 sm:p-2.5 font-semibold text-brand-700">${item.qty}</td>
                <td class="p-2 sm:p-2.5 text-brand-300">₱${item.price.toFixed(2)}</td>
                <td class="p-2 sm:p-2.5 font-bold text-brand-500">₱${item.subtotal.toFixed(2)}</td>
                <td class="p-2 sm:p-2.5 text-right">
                    <button type="button" onclick="cartManager.removeFromCart(${item.vid})" class="w-6 h-6 rounded-full bg-red-50 hover:bg-red-100 text-red-500 font-bold flex items-center justify-center text-xs ml-auto transition-colors">&times;</button>
                </td>
            `;
            this.cartTable.appendChild(tr);
        });

        this.totalEl.textContent = `₱${total.toFixed(2)}`;
        this.calcChange();
        if (this.completeBtn) this.completeBtn.disabled = false;
    }

    calcChange() {
        const total = this.cart.reduce((sum, i) => sum + i.subtotal, 0);
        const paid = parseFloat(this.paidInput ? this.paidInput.value : 0) || 0;
        const change = paid - total;
        if (this.changeEl) {
            if (change < 0 || this.cart.length === 0) {
                this.changeEl.textContent = '₱0.00';
                this.changeEl.className = 'text-2xl font-bold text-brand-300 py-1.5 px-2 bg-white rounded-md border border-brand-200';
            } else {
                this.changeEl.textContent = `₱${change.toFixed(2)}`;
                this.changeEl.className = 'text-2xl font-bold text-green-600 py-1.5 px-2 bg-green-50 rounded-md border border-green-200';
            }
        }
    }

    submitSale(e) {
        if (this.cart.length === 0) {
            e.preventDefault();
            alert('Your cart is empty.');
            return;
        }

        const total = this.cart.reduce((sum, i) => sum + i.subtotal, 0);
        const paid = parseFloat(this.paidInput ? this.paidInput.value : 0) || 0;

        if (paid < total) {
            e.preventDefault();
            alert(`Amount paid is insufficient. Total is ₱${total.toFixed(2)}.`);
            return;
        }

        const payload = this.cart.map(i => ({ product_variant_id: i.vid, quantity: i.qty }));
        this.cartInput.value = JSON.stringify(payload);
    }
}
