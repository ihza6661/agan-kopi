export interface User {
    id: number;
    name: string;
    username: string;
    role: 'admin' | 'cashier';
    created_at: string;
    updated_at: string;
}

export interface Category {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Product {
    id: number;
    sku: string;
    name: string;
    price: number;
    stock: number;
    min_stock: number;
    category_id: number | null;
    category?: Category;
    image?: string;
    created_at: string;
    updated_at: string;
}

export interface TransactionDetail {
    id: number;
    transaction_id: number;
    product_id: number;
    product_name: string;
    quantity: number;
    price: number;
    total: number;
    product?: Product;
}

export interface Transaction {
    id: number;
    invoice_number: string;
    user_id: number;
    user?: User;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    paid_amount: number | null;
    change_amount: number | null;
    status: 'pending' | 'paid' | 'hold' | 'canceled';
    note: string | null;
    suspended_from_id: number | null;
    details?: TransactionDetail[];
    payment?: Payment;
    created_at: string;
    updated_at: string;
}

export interface Payment {
    id: number;
    transaction_id: number;
    transaction?: Transaction;
    method: 'cash' | 'qris';
    amount: number;
    paid_at: string | null;
    status: 'pending' | 'paid' | 'canceled';
    created_at: string;
    updated_at: string;
}

export interface ActivityLog {
    id: number;
    user_id: number;
    user?: User;
    action: string;
    description: string;
    created_at: string;
}

export interface Setting {
    key: string;
    value: string;
}

export interface Notification {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface CartItem {
    product_id: number;
    name: string;
    price: number;
    qty: number;
    stock: number;
}
