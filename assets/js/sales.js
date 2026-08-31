class SalesCartManager {
    constructor(productsData) {
        this.products = productsData || [];
        this.cart = [];
        this.selectedProduct = null;
        this.selectedVariant = null;
        this.init();
    }
    init() {
        this.cartTable = document.getElementById("cartTableBody");
        this.emptyRow = document.getElementById("emptyCartRow");
        this.totalEl = document.getElementById("totalAmountDisplay");
        this.paidInput = document.getElementById("amountPaidInput");
        this.changeEl = document.getElementById("changeAmountDisplay");
        this.completeBtn = document.getElementById("completeSaleBtn");
        this.form = document.getElementById("saleForm");
        this.cartInput = document.getElementById("cartItemsInput");
        this.modal = document.getElementById("variantModal");
        this.modalVariantList = document.getElementById("modalVariantList");
        this.modalQtyInput = document.getElementById("modalQtyInput");
        this.confirmAddBtn = document.getElementById("confirmAddToCartBtn");
        this.bindEvents();
    }
    bindEvents() {
        document.querySelectorAll(".product-card").forEach(card => {
            card.addEventListener("click", (e) => {
                e.preventDefault();
                this.openVariantModal(parseInt(card.dataset.id));
            });
        });
        this.confirmAddBtn?.addEventListener("click", (e) => { e.preventDefault(); this.addToCart(); });
        this.paidInput?.addEventListener("input", () => this.calcChange());
        this.form?.addEventListener("submit", (e) => this.submitSale(e));
        document.getElementById("closeVariantModalBtn")?.addEventListener("click", () => this.closeVariantModal());
        document.getElementById("cancelVariantModalBtn")?.addEventListener("click", () => this.closeVariantModal());
        
        // Quantity controls
        document.getElementById("modalQtyMinus")?.addEventListener("click", (e) => {
            e.preventDefault();
            const input = this.modalQtyInput;
            let val = parseInt(input.value) || 1;
            if (val > 1) input.value = val - 1;
        });
        document.getElementById("modalQtyPlus")?.addEventListener("click", (e) => {
            e.preventDefault();
            const input = this.modalQtyInput;
            let val = parseInt(input.value) || 1;
            input.value = val + 1;
        });
        
        // Close modal when clicking on the backdrop (outside the modal content)
        this.modal?.addEventListener("click", (e) => {
            if (e.target === this.modal) {
                this.closeVariantModal();
            }
        });
    }
    openVariantModal(productId) {
        this.selectedProduct = this.products.find(p => p.id === productId);
        if (!this.selectedProduct) return;
        this.selectedVariant = null;
        this.modalQtyInput.value = "1";
        this.modalVariantList.innerHTML = "";
        
        // Set product image and name in modal
        const modalImg = document.getElementById("modalProductImg");
        const modalName = document.getElementById("modalProductName");
        if (modalImg) {
            modalImg.src = this.selectedProduct.image || "/assets/images/placeholder.png";
            modalImg.alt = this.selectedProduct.name;
            modalImg.onerror = () => {
                modalImg.src = "/assets/images/placeholder.png";
            };
        }
        if (modalName) {
            modalName.textContent = this.selectedProduct.name;
        }
        
        this.selectedProduct.variants.forEach(v => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "w-full px-4 py-2 text-left rounded-md border border-brand-200 font-semibold text-sm text-brand-700 variant-pill hover:bg-brand-50 transition-colors";
            const ul = {half_tray:'Half Tray',tray:'Tray',bundle:'Bundle'}[v.selling_unit]||'Piece';
            const pc = v.pieces_per_unit > 1 ? ` (${v.pieces_per_unit} pcs)` : '';
            btn.textContent = `${v.variant_name} - ₱${v.price.toFixed(2)} / ${ul}${pc}`;
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                document.querySelectorAll(".variant-pill").forEach(p => p.classList.remove("bg-brand-500", "text-white"));
                btn.classList.add("bg-brand-500", "text-white");
                this.selectedVariant = v;
            });
            this.modalVariantList.appendChild(btn);
        });
        this.modal.classList.remove("hidden");
        // Force reflow to trigger animation
        setTimeout(() => {
            const modalContent = document.getElementById("variantModalContent");
            if (modalContent) {
                modalContent.classList.remove("scale-95", "opacity-0");
                modalContent.classList.add("scale-100", "opacity-100");
            }
        }, 10);
    }
    closeVariantModal() {
        const modalContent = document.getElementById("variantModalContent");
        if (modalContent) {
            modalContent.classList.add("scale-95", "opacity-0");
            modalContent.classList.remove("scale-100", "opacity-100");
        }
        setTimeout(() => {
            this.modal.classList.add("hidden");
        }, 200);
    }
    addToCart() {
        if (!this.selectedVariant) { alert("Select variant"); return; }
        const qty = parseInt(this.modalQtyInput.value) || 1;
        if (qty < 1) { alert("Invalid qty"); return; }
        
        // Calculate subtotal based on selling unit
        const unit = this.selectedVariant.selling_unit || 'piece';
        const piecesPerUnit = this.selectedVariant.pieces_per_unit || 1;
        const price = this.selectedVariant.price;
        
        // For bundle units: qty is pieces entered, must divide by pieces_per_unit to get number of bundles
        // For other units (piece, half_tray, tray): qty is the quantity of that unit, price is per that unit
        let subtotal;
        if (unit === 'bundle') {
            subtotal = (qty / piecesPerUnit) * price;
        } else {
            subtotal = qty * price;
        }
        
        const item = {
            vid: this.selectedVariant.id,
            pName: this.selectedProduct.name,
            vName: this.selectedVariant.variant_name,
            price: price,
            unit: unit,
            pieces: piecesPerUnit,
            qty: qty,
            subtotal: subtotal
        };
        const existing = this.cart.find(i => i.vid === item.vid);
        if (existing) { 
            existing.qty += item.qty;
            // Recalculate subtotal for existing item
            if (existing.unit === 'bundle') {
                existing.subtotal = (existing.qty / existing.pieces) * existing.price;
            } else {
                existing.subtotal = existing.qty * existing.price;
            }
        }
        else { this.cart.push(item); }
        this.closeVariantModal();
        this.render();
    }
    removeFromCart(vid) {
        this.cart = this.cart.filter(i => i.vid !== vid);
        this.render();
    }

    render() {
        if (!this.cartTable) return;
        this.cartTable.innerHTML = "";
        if (this.cart.length === 0) {
            this.cartTable.appendChild(this.emptyRow);
            this.totalEl.textContent = "₱0.00";
            this.calcChange();
            if (this.completeBtn) this.completeBtn.disabled = true;
            return;
        }
        let total = 0;
        this.cart.forEach(item => {
            total += item.subtotal;
            const ul = {half_tray:'Half Tray',tray:'Tray',bundle:'Bundle'}[item.unit]||'Piece';
            const pc = item.pieces > 1 ? ` (${item.pieces} pcs)` : '';
            
            // Display quantity: for bundle show "X pieces", otherwise show quantity of unit
            let qtyDisplay;
            if (item.unit === 'bundle') {
                qtyDisplay = `${item.qty}`;
            } else {
                qtyDisplay = `${item.qty}`;
            }
            
            const tr = document.createElement("tr");
            tr.className = "border-b border-brand-100 text-xs sm:text-sm hover:bg-brand-50";
            tr.innerHTML = `<td class="p-2 font-bold text-brand-700"><div>${item.pName}</div><div class="text-[11px] text-brand-300">${item.vName}</div></td><td class="p-2 font-semibold text-center">${qtyDisplay}</td><td class="p-2 text-brand-300">₱${item.price.toFixed(2)}/${ul}${pc}</td><td class="p-2 font-bold text-brand-500">₱${item.subtotal.toFixed(2)}</td><td class="p-2 text-right"><button type="button" onclick="cartManager.removeFromCart(${item.vid})" class="w-6 h-6 rounded-full bg-red-50 text-red-500 font-bold text-xs">&times;</button></td>`;
            this.cartTable.appendChild(tr);
        });
        this.totalEl.textContent = `₱${total.toFixed(2)}`;
        this.calcChange();
        if (this.completeBtn) this.completeBtn.disabled = false;
    }
    calcChange() {
        const total = this.cart.reduce((s, i) => s + i.subtotal, 0);
        const paid = parseFloat(this.paidInput?.value || 0) || 0;
        const change = paid - total;
        if (this.changeEl) {
            this.changeEl.textContent = change < 0 || this.cart.length === 0 ? "₱0.00" : `₱${change.toFixed(2)}`;
            this.changeEl.className = change < 0 || this.cart.length === 0 ? "text-2xl font-bold text-brand-300 py-1.5 px-2 bg-white rounded-md border border-brand-200" : "text-2xl font-bold text-green-600 py-1.5 px-2 bg-green-50 rounded-md border border-green-200";
        }
    }
    submitSale(e) {
        if (this.cart.length === 0) { e.preventDefault(); alert("Cart is empty"); return; }
        const total = this.cart.reduce((s, i) => s + i.subtotal, 0);
        const paid = parseFloat(this.paidInput?.value || 0) || 0;
        if (paid < total) { e.preventDefault(); alert(`Insufficient. Total: ₱${total.toFixed(2)}`); return; }
        this.cartInput.value = JSON.stringify(this.cart.map(i => ({ product_variant_id: i.vid, quantity_units: i.qty, selling_unit: i.unit, pieces_per_unit: i.pieces })));
    }
}
