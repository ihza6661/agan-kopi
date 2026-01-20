import { create } from 'zustand';
import type { CartItem, Product } from '@/types/models';

interface CartState {
    items: CartItem[];
    note: string;
    suspendedFromId: number | null;
    discountPercent: number;
    taxPercent: number;
    
    // Actions
    addItem: (product: Product, qty?: number) => void;
    updateQty: (productId: number, qty: number) => void;
    removeItem: (productId: number) => void;
    clearCart: () => void;
    setNote: (note: string) => void;
    setSuspendedFromId: (id: number | null) => void;
    loadFromHold: (items: CartItem[], note: string, holdId: number) => void;
    setTaxDiscount: (tax: number, discount: number) => void;
    
    // Computed
    getSubtotal: () => number;
    getDiscountAmount: () => number;
    getTaxAmount: () => number;
    getTotal: () => number;
}

export const useCartStore = create<CartState>((set, get) => ({
    items: [],
    note: '',
    suspendedFromId: null,
    discountPercent: 0,
    taxPercent: 0,
    
    addItem: (product: Product, qty = 1) => {
        set((state) => {
            const existingIndex = state.items.findIndex((item) => item.product_id === product.id);
            
            if (existingIndex >= 0) {
                // Update existing item
                const newItems = [...state.items];
                const newQty = Math.min(newItems[existingIndex].qty + qty, product.stock);
                newItems[existingIndex] = { ...newItems[existingIndex], qty: newQty };
                return { items: newItems };
            } else {
                // Add new item
                const newItem: CartItem = {
                    product_id: product.id,
                    name: product.name,
                    price: product.price,
                    qty: Math.min(qty, product.stock),
                    stock: product.stock,
                };
                return { items: [...state.items, newItem] };
            }
        });
    },
    
    updateQty: (productId: number, qty: number) => {
        set((state) => {
            const newItems = state.items.map((item) => {
                if (item.product_id === productId) {
                    return { ...item, qty: Math.min(Math.max(1, qty), item.stock) };
                }
                return item;
            });
            return { items: newItems };
        });
    },
    
    removeItem: (productId: number) => {
        set((state) => ({
            items: state.items.filter((item) => item.product_id !== productId),
        }));
    },
    
    clearCart: () => {
        set({ items: [], note: '', suspendedFromId: null });
    },
    
    setNote: (note: string) => {
        set({ note });
    },
    
    setSuspendedFromId: (id: number | null) => {
        set({ suspendedFromId: id });
    },
    
    loadFromHold: (items: CartItem[], note: string, holdId: number) => {
        set({ items, note, suspendedFromId: holdId });
    },
    
    setTaxDiscount: (tax: number, discount: number) => {
        set({ taxPercent: tax, discountPercent: discount });
    },
    
    getSubtotal: () => {
        const { items } = get();
        return items.reduce((sum, item) => sum + item.price * item.qty, 0);
    },
    
    getDiscountAmount: () => {
        const { discountPercent } = get();
        return get().getSubtotal() * (discountPercent / 100);
    },
    
    getTaxAmount: () => {
        const { taxPercent } = get();
        const afterDiscount = get().getSubtotal() - get().getDiscountAmount();
        return afterDiscount * (taxPercent / 100);
    },
    
    getTotal: () => {
        const subtotal = get().getSubtotal();
        const discount = get().getDiscountAmount();
        const tax = get().getTaxAmount();
        return subtotal - discount + tax;
    },
}));
