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
        this.cartCountBadge = document.getElementById("cartCountBadge");
        this.modal = document.getElementById("variantModal");
        this.modalVariantList = document.getElementById("modalVariantList");
        this.modalQtyInput = document.getElementById("modalQtyInput");
        this.confirmAddBtn = document.getElementById("confirmAddToCartBtn");
        this.paymentErrorBanner = document.getElementById("paymentErrorBanner");
        this.paymentErrorMessage = document.getElementById("paymentErrorMessage");
        this.variantValidationMessage = document.getElementById("variantValidationMessage");
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
        this.paidInput?.addEventListener("input", () => this.validatePayment());
        this.paidInput?.addEventListener("blur", () => this.validatePayment());
        this.form?.addEventListener("submit", (e) => this.submitSale(e));
        document.getElementById("closeVariantModalBtn")?.addEventListener("click", () => this.closeVariantModal());
        document.getElementById("cancelVariantModalBtn")?.addEventListener("click", () => this.closeVariantModal());
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
        this.modal?.addEventListener("click", (e) => {
            if (e.target === this.modal) this.closeVariantModal();
        });
        document.querySelectorAll(".quick-pay-btn").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const amount = parseFloat(btn.dataset.amount);
                this.paidInput.value = amount.toFixed(2);
                this.validatePayment();
                this.paidInput.focus();
            });
        });
    }

    openVariantModal(productId) {
        this.selectedProduct = this.products.find(p => p.id === productId);
        if (!this.selectedProduct) return;
        this.selectedVariant = null;
        this.modalQtyInput.value = "1";
        this.modalVariantList.innerHTML = "";
        this.clearVariantValidationError();
        const modalImg = document.getElementById("modalProductImg");
        const modalName = document.getElementById("modalProductName");
        if (modalImg) {
            modalImg.src = this.selectedProduct.image || "/assets/images/placeholder.png";
            modalImg.alt = this.selectedProduct.name;
            modalImg.onerror = () => { modalImg.src = "/assets/images/placeholder.png"; };
        }
        if (modalName) modalName.textContent = this.selectedProduct.name;
        this.selectedProduct.variants.forEach(v => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "w-full px-4 py-2 text-left rounded-md border border-brand-200 font-semibold text-sm text-brand-700 hover:bg-brand-50 transition-colors";
            const ul = {half_tray:"Half Tray",tray:"Tray",bundle:"Bundle"}[v.selling_unit]||"Piece";
            const pc = v.pieces_per_unit > 1 ? ` (${v.pieces_per_unit} pcs)` : "";
            btn.textContent = `${v.variant_name} - ₱${v.price.toFixed(2)}/${ul}${pc}`;
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                this.selectedVariant = v;
                this.clearVariantValidationError();
                document.querySelectorAll("#modalVariantList button").forEach(b => b.classList.remove("bg-brand-500", "text-white"));
                btn.classList.add("bg-brand-500", "text-white");
            });
            this.modalVariantList.appendChild(btn);
        });
        this.modal.classList.remove("hidden");
        setTimeout(() => {
            const content = document.getElementById("variantModalContent");
            if (content) {
                content.classList.remove("scale-95", "opacity-0");
                content.classList.add("scale-100", "opacity-100");
            }
        }, 10);
    }

    closeVariantModal() {
        const content = document.getElementById("variantModalContent");
        if (content) {
            content.classList.add("scale-95", "opacity-0");
            content.classList.remove("scale-100", "opacity-100");
            setTimeout(() => {
                this.modal.classList.add("hidden");
            }, 200);
        }
        this.clearVariantValidationError();
    }

    addToCart() {
        if (!this.selectedVariant) {
            this.showVariantValidationError();
            return;
        }
        this.clearVariantValidationError();
        const qty = parseInt(this.modalQtyInput.value) || 1;
        if (qty < 1) { alert("Quantity must be at least 1"); return; }
        const unit = this.selectedVariant.selling_unit || "piece";
        const piecesPerUnit = this.selectedVariant.pieces_per_unit || 1;
        const price = this.selectedVariant.price;
        let subtotal;
        if (unit === "bundle") {
            subtotal = Math.round(((qty / piecesPerUnit) * price) * 100) / 100;
        } else if (unit === "half_tray") {
            subtotal = Math.round(((qty / 15) * price) * 100) / 100;
        } else if (unit === "tray") {
            subtotal = Math.round(((qty / 30) * price) * 100) / 100;
        } else {
            subtotal = Math.round((qty * price) * 100) / 100;
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
            if (existing.unit === "bundle") {
                existing.subtotal = Math.round(((existing.qty / existing.pieces) * existing.price) * 100) / 100;
            } else if (existing.unit === "half_tray") {
                existing.subtotal = Math.round(((existing.qty / 15) * existing.price) * 100) / 100;
            } else if (existing.unit === "tray") {
                existing.subtotal = Math.round(((existing.qty / 30) * existing.price) * 100) / 100;
            } else {
                existing.subtotal = Math.round((existing.qty * existing.price) * 100) / 100;
            }
        } else {
            this.cart.push(item);
        }
        this.closeVariantModal();
        this.render();
    }

    showVariantValidationError() {
        if (this.variantValidationMessage) {
            this.variantValidationMessage.classList.remove("hidden");
        }
    }

    clearVariantValidationError() {
        if (this.variantValidationMessage) {
            this.variantValidationMessage.classList.add("hidden");
        }
    }

    removeFromCart(vid) {
        this.cart = this.cart.filter(i => i.vid !== vid);
        this.render();
    }

    render() {
        if (!this.cartTable) return;
        
        // Clear BOTH desktop table and mobile card container
        this.cartTable.innerHTML = "";
        const cartCardBody = document.getElementById("cartCardBody");
        if (cartCardBody) cartCardBody.innerHTML = "";
        
        // Handle empty cart case
        if (this.cart.length === 0) {
            // Show desktop empty state
            this.cartTable.appendChild(this.emptyRow);
            // Show mobile empty state if it exists
            const emptyCardEl = document.getElementById("emptyCartCardRow");
            if (emptyCardEl) emptyCardEl.style.display = "";
            
            this.totalEl.textContent = "₱0.00";
            this.calcChange();
            if (this.completeBtn) this.completeBtn.disabled = true;
            return;
        }
        
        // Hide mobile empty state when cart has items
        const emptyCardEl = document.getElementById("emptyCartCardRow");
        if (emptyCardEl) emptyCardEl.style.display = "none";
        
        // Build and render each cart item
        let total = 0;
        this.cart.forEach(item => {
            total += item.subtotal;
            const ul = {half_tray:"Half Tray",tray:"Tray",bundle:"Bundle"}[item.unit]||"Piece";
            const pc = item.pieces > 1 ? ` (` + item.pieces + ` pcs)` : "";
            
            // ========== DESKTOP TABLE ROW (unchanged logic) ==========
            const tr = document.createElement("tr");
            tr.className = "border-b border-brand-100 text-xs sm:text-sm hover:bg-brand-50";
            tr.innerHTML = `<td class="p-2 font-bold text-brand-700"><div>` + item.pName + `</div><div class="text-[11px] text-brand-300">` + item.vName + `</div></td><td class="p-2 font-semibold text-center">` + item.qty + `</td><td class="p-2 text-brand-300">₱` + item.price.toFixed(2) + `/` + ul + pc + `</td><td class="p-2 font-bold text-brand-500">₱` + item.subtotal.toFixed(2) + `</td><td class="p-2 text-right"><button type="button" onclick="cartManager.removeFromCart(` + item.vid + `)" class="w-6 h-6 rounded-full bg-brand-100 hover:bg-brand-200 text-brand-500 font-bold text-xs transition-colors">&times;</button></td>`;
            this.cartTable.appendChild(tr);
            
            // ========== MOBILE CARD (NEW) ==========
            // Only create mobile card if the container exists
            if (cartCardBody) {
                const card = document.createElement("div");
                card.className = "border border-brand-100 rounded-md p-3 space-y-2 hover:bg-brand-50 transition-colors";
                card.innerHTML = `<div class="flex items-start justify-between gap-2"><div class="flex-1"><div class="font-bold text-brand-700 text-sm">` + item.pName + `</div><div class="text-xs text-brand-300">` + item.vName + `</div><div class="text-xs text-brand-300 mt-1">` + item.qty + ` x ₱` + item.price.toFixed(2) + `</div></div><div class="text-right"><div class="font-bold text-brand-500 text-sm">₱` + item.subtotal.toFixed(2) + `</div></div></div><div class="pt-2 border-t border-brand-100"><button type="button" onclick="cartManager.removeFromCart(` + item.vid + `)" class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-sm text-sm font-semibold transition-colors">Remove</button></div>`;
                cartCardBody.appendChild(card);
            }
        });
        
        // Update totals and state (unchanged)
        this.totalEl.textContent = "₱" + total.toFixed(2);
        this.calcChange();
        if (this.completeBtn) this.completeBtn.disabled = false;
        
        // Update cart counter badge
        if (this.cartCountBadge) {
            const itemCount = this.cart.length;
            this.cartCountBadge.textContent = itemCount + " item" + (itemCount !== 1 ? "s" : "");
        }
    }

    calcChange() {
        const total = this.cart.reduce((s, i) => s + i.subtotal, 0);
        const paid = parseFloat(this.paidInput?.value || 0) || 0;
        const change = paid - total;
        if (this.changeEl) {
            if (this.cart.length === 0) {
                this.changeEl.textContent = "₱0.00";
                this.changeEl.className = "text-2xl font-bold text-brand-300 py-1.5 px-2 bg-white rounded-md border border-brand-200";
            } else if (change < 0) {
                this.changeEl.textContent = "₱0.00";
                this.changeEl.className = "text-2xl font-bold text-brand-300 py-1.5 px-2 bg-white rounded-md border border-brand-200";
            } else {
                this.changeEl.textContent = "₱" + change.toFixed(2);
                this.changeEl.className = "text-2xl font-bold text-brand-500 py-1.5 px-2 bg-brand-100 rounded-md border border-brand-200";
            }
        }
    }

    validatePayment() {
        const total = this.cart.reduce((s, i) => s + i.subtotal, 0);
        const paid = parseFloat(this.paidInput?.value || 0) || 0;
        const isShort = paid < total && this.cart.length > 0;
        
        if (isShort) {
            const shortAmount = total - paid;
            if (this.paymentErrorMessage) {
                this.paymentErrorMessage.textContent = `−₱${shortAmount.toFixed(2)} short`;
            }
            if (this.paymentErrorBanner) {
                this.paymentErrorBanner.classList.remove("hidden");
            }
            if (this.paidInput) {
                this.paidInput.classList.add("border-red-500", "focus:border-red-500", "focus:ring-red-200");
                this.paidInput.classList.remove("border-brand-200", "focus:border-brand-500", "focus:ring-brand-200");
            }
            if (this.completeBtn) {
                this.completeBtn.disabled = true;
            }
        } else {
            if (this.paymentErrorBanner) {
                this.paymentErrorBanner.classList.add("hidden");
            }
            if (this.paidInput) {
                this.paidInput.classList.remove("border-red-500", "focus:border-red-500", "focus:ring-red-200");
                this.paidInput.classList.add("border-brand-200", "focus:border-brand-500", "focus:ring-brand-200");
            }
            if (this.cart.length > 0 && this.completeBtn) {
                this.completeBtn.disabled = false;
            }
        }
        this.calcChange();
    }

    submitSale(e) {
        if (this.cart.length === 0) { e.preventDefault(); alert("Cart is empty"); return; }
        const total = this.cart.reduce((s, i) => s + i.subtotal, 0);
        const paid = parseFloat(this.paidInput?.value || 0) || 0;
        if (paid < total) { 
            e.preventDefault(); 
            this.validatePayment();
            return; 
        }
        this.cartInput.value = JSON.stringify(this.cart.map(i => ({ product_variant_id: i.vid, quantity_units: i.qty, selling_unit: i.unit, pieces_per_unit: i.pieces })));
    }
}