import { useState, useEffect, useRef, useCallback } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    ShoppingCart,
    Search,
    Trash2,
    Plus,
    Minus,
    Pause,
    Inbox,
    CheckCircle2,
    Banknote,
    QrCode,
    Loader2,
    Printer,
} from 'lucide-react';
import { useCartStore } from '@/stores/cartStore';
import { formatMoney, formatNumber, parseMoneyToInt } from '@/lib/utils';
import type { Product } from '@/types/models';

declare global {
    interface Window {
        snap?: {
            embed: (token: string, options: {
                embedId: string;
                onSuccess?: () => void;
                onPending?: () => void;
                onError?: () => void;
                onClose?: () => void;
            }) => void;
        };
    }
}

interface HoldTransaction {
    id: number;
    invoice_number: string;
    note: string | null;
    total: number;
    created_at: string;
    items?: Array<{ product_id: number; qty: number; price: number }>;
}

interface CashierProps {
    currency: string;
    discount_percent: number;
    tax_percent: number;
    midtrans_client_key: string;
    midtrans_is_production: boolean;
}

export default function CashierIndex({
    currency,
    discount_percent,
    tax_percent,
    midtrans_client_key,
    midtrans_is_production,
}: CashierProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [products, setProducts] = useState<Product[]>([]);
    const [loading, setLoading] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState<'cash' | 'qris'>('cash');
    const [paidAmount, setPaidAmount] = useState('');
    const [processing, setProcessing] = useState(false);
    const [holdsOpen, setHoldsOpen] = useState(false);
    const [holds, setHolds] = useState<HoldTransaction[]>([]);
    const [successModal, setSuccessModal] = useState<{
        open: boolean;
        invoiceNumber?: string;
        transactionId?: number;
        method?: string;
    }>({ open: false });
    const [showSnapEmbed, setShowSnapEmbed] = useState(false);
    const searchInputRef = useRef<HTMLInputElement>(null);
    const searchTimeoutRef = useRef<number | null>(null);

    const {
        items,
        note,
        suspendedFromId,
        addItem,
        updateQty,
        removeItem,
        clearCart,
        setNote,
        setTaxDiscount,
        getSubtotal,
        getDiscountAmount,
        getTaxAmount,
        getTotal,
    } = useCartStore();

    // Set tax and discount on mount
    useEffect(() => {
        setTaxDiscount(tax_percent, discount_percent);
    }, [tax_percent, discount_percent, setTaxDiscount]);

    // Load Midtrans Snap script
    useEffect(() => {
        const scriptUrl = midtrans_is_production
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        
        if (!document.querySelector(`script[src="${scriptUrl}"]`)) {
            const script = document.createElement('script');
            script.src = scriptUrl;
            script.setAttribute('data-client-key', midtrans_client_key);
            document.body.appendChild(script);
        }
    }, [midtrans_client_key, midtrans_is_production]);

    // Search products
    const searchProducts = useCallback(async (query: string) => {
        setLoading(true);
        try {
            const res = await fetch(`/kasir/products?q=${encodeURIComponent(query)}&limit=20`);
            const data = await res.json();
            setProducts(data || []);
        } catch {
            setProducts([]);
        } finally {
            setLoading(false);
        }
    }, []);

    // Debounced search
    useEffect(() => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        searchTimeoutRef.current = window.setTimeout(() => {
            searchProducts(searchQuery);
        }, 300);
        
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, [searchQuery, searchProducts]);

    // Initial load
    useEffect(() => {
        searchProducts('');
    }, [searchProducts]);

    const handleAddToCart = (product: Product, qty = 1) => {
        if (product.stock <= 0) return;
        addItem(product, qty);
    };

    const handleCheckout = async () => {
        if (items.length === 0) return;

        const total = getTotal();
        const paidInt = parseMoneyToInt(paidAmount);

        if (paymentMethod === 'cash' && paidInt < total) {
            alert('Jumlah bayar kurang dari total.');
            return;
        }

        setProcessing(true);

        try {
            const payload = {
                payment_method: paymentMethod,
                items: items.map(({ product_id, qty }) => ({ product_id, qty })),
                paid_amount: paymentMethod === 'cash' ? paidInt : null,
                note,
                suspended_from_id: suspendedFromId,
            };

            const res = await fetch('/kasir/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.message || 'Checkout failed');
            }

            if (paymentMethod === 'cash') {
                // Cash payment success
                clearCart();
                setPaidAmount('');
                setSuccessModal({
                    open: true,
                    invoiceNumber: data.invoice,
                    transactionId: data.transaction_id,
                    method: 'cash',
                });
            } else {
                // QRIS payment - embed Snap
                const snapToken = data.snap_token;
                const trxId = data.transaction_id;

                if (snapToken && window.snap) {
                    setShowSnapEmbed(true);
                    
                    setTimeout(() => {
                        window.snap?.embed(snapToken, {
                            embedId: 'snapContainer',
                            onSuccess: () => {
                                clearCart();
                                setPaidAmount('');
                                setShowSnapEmbed(false);
                                router.visit(`/pembayaran/${trxId}/complete`);
                            },
                            onPending: () => {
                                // Start polling for status
                            },
                            onError: () => {
                                alert('Pembayaran gagal. Coba lagi.');
                                setShowSnapEmbed(false);
                            },
                            onClose: () => {
                                setShowSnapEmbed(false);
                            },
                        });
                    }, 100);
                }
            }
        } catch (error) {
            alert(error instanceof Error ? error.message : 'Terjadi kesalahan.');
        } finally {
            setProcessing(false);
        }
    };

    const handleHold = async () => {
        if (items.length === 0) return;

        setProcessing(true);
        try {
            const payload = {
                items: items.map(({ product_id, qty }) => ({ product_id, qty })),
                note,
                suspended_from_id: suspendedFromId,
            };

            const res = await fetch('/kasir/hold', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.message || 'Hold failed');
            }

            alert(`Transaksi ditunda: ${data.invoice}`);
            clearCart();
            setPaidAmount('');
        } catch (error) {
            alert(error instanceof Error ? error.message : 'Gagal menunda transaksi.');
        } finally {
            setProcessing(false);
        }
    };

    const loadHolds = async () => {
        try {
            const res = await fetch('/kasir/holds');
            const data = await res.json();
            setHolds(data || []);
        } catch {
            setHolds([]);
        }
    };

    const handleResumeHold = async (holdId: number) => {
        try {
            const res = await fetch(`/kasir/holds/${holdId}/resume`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            const data = await res.json();

            if (data.items) {
                // Fetch full product details
                const productsRes = await fetch('/kasir/products?limit=100');
                const allProducts: Product[] = await productsRes.json();

                const cartItems = data.items.map((it: { product_id: number; qty: number; price: number }) => {
                    const p = allProducts.find((x) => x.id === it.product_id);
                    return {
                        product_id: it.product_id,
                        name: p?.name || `Produk #${it.product_id}`,
                        price: it.price || p?.price || 0,
                        qty: Math.min(it.qty, p?.stock || it.qty),
                        stock: p?.stock || it.qty,
                    };
                });

                useCartStore.getState().loadFromHold(cartItems, data.note || '', holdId);
                setHoldsOpen(false);
            }
        } catch (error) {
            alert('Gagal memuat transaksi.');
        }
    };

    const handleDeleteHold = async (holdId: number) => {
        if (!confirm('Hapus transaksi tertunda ini?')) return;

        try {
            await fetch(`/kasir/holds/${holdId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            loadHolds();
        } catch {
            alert('Gagal menghapus.');
        }
    };

    const subtotal = getSubtotal();
    const discountAmount = getDiscountAmount();
    const taxAmount = getTaxAmount();
    const total = getTotal();
    const paidInt = parseMoneyToInt(paidAmount);
    const change = Math.max(0, paidInt - total);
    const canCheckout = items.length > 0 && (paymentMethod !== 'cash' || paidInt >= total);

    return (
        <AppLayout title="Kasir">
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-2 justify-between items-start">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <ShoppingCart className="h-6 w-6" />
                            Kasir
                        </h1>
                        <p className="text-muted-foreground">
                            Scan SKU atau cari produk, tambahkan ke keranjang, lalu proses pembayaran.
                        </p>
                    </div>
                    <div className="text-sm text-muted-foreground">
                        Diskon: {formatNumber(discount_percent)}% • Pajak: {formatNumber(tax_percent)}% • {currency}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Left: Products & Cart */}
                    <div className="lg:col-span-2 space-y-4">
                        {/* Search */}
                        <Card>
                            <CardContent className="p-4">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        ref={searchInputRef}
                                        type="search"
                                        placeholder="Scan SKU atau ketik nama produk..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-9"
                                    />
                                </div>

                                {/* Product list */}
                                <ScrollArea className="h-[250px] mt-4">
                                    {loading ? (
                                        <div className="flex items-center justify-center h-full text-muted-foreground">
                                            <Loader2 className="h-5 w-5 animate-spin mr-2" />
                                            Memuat...
                                        </div>
                                    ) : products.length === 0 ? (
                                        <div className="text-center text-muted-foreground py-8">
                                            Produk tidak ditemukan.
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            {products.map((product) => (
                                                <div
                                                    key={product.id}
                                                    className="flex items-center justify-between p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                                                >
                                                    <div className="flex-1 min-w-0">
                                                        <div className="font-medium truncate">{product.name}</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            SKU: {product.sku} • {formatMoney(product.price, currency)}
                                                            {product.stock <= 0 ? (
                                                                <Badge variant="secondary" className="ml-2">Habis</Badge>
                                                            ) : (
                                                                <Badge variant="success" className="ml-2">
                                                                    Stok: {product.stock}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleAddToCart(product)}
                                                        disabled={product.stock <= 0}
                                                    >
                                                        <Plus className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </ScrollArea>
                            </CardContent>
                        </Card>

                        {/* Cart */}
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center gap-2">
                                        <ShoppingCart className="h-5 w-5" />
                                        Keranjang
                                    </CardTitle>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setHoldsOpen(true);
                                                loadHolds();
                                            }}
                                        >
                                            <Inbox className="h-4 w-4 mr-1" />
                                            Tertunda
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleHold}
                                            disabled={items.length === 0 || processing}
                                        >
                                            <Pause className="h-4 w-4 mr-1" />
                                            Tunda
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={clearCart}
                                            disabled={items.length === 0}
                                        >
                                            <Trash2 className="h-4 w-4 mr-1" />
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {items.length === 0 ? (
                                    <div className="text-center text-muted-foreground py-8">
                                        Keranjang kosong.
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Produk</TableHead>
                                                <TableHead className="text-right">Harga</TableHead>
                                                <TableHead className="text-center" style={{ width: 140 }}>Qty</TableHead>
                                                <TableHead className="text-right">Total</TableHead>
                                                <TableHead className="w-10"></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {items.map((item) => (
                                                <TableRow key={item.product_id}>
                                                    <TableCell>
                                                        <div className="font-medium">{item.name}</div>
                                                        <div className="text-xs text-muted-foreground">
                                                            ID: {item.product_id}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatMoney(item.price, currency)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center justify-center gap-1">
                                                            <Button
                                                                variant="outline"
                                                                size="icon"
                                                                className="h-7 w-7"
                                                                onClick={() => updateQty(item.product_id, item.qty - 1)}
                                                                disabled={item.qty <= 1}
                                                            >
                                                                <Minus className="h-3 w-3" />
                                                            </Button>
                                                            <Input
                                                                type="number"
                                                                min={1}
                                                                max={item.stock}
                                                                value={item.qty}
                                                                onChange={(e) => updateQty(item.product_id, parseInt(e.target.value) || 1)}
                                                                className="h-7 w-14 text-center"
                                                            />
                                                            <Button
                                                                variant="outline"
                                                                size="icon"
                                                                className="h-7 w-7"
                                                                onClick={() => updateQty(item.product_id, item.qty + 1)}
                                                                disabled={item.qty >= item.stock}
                                                            >
                                                                <Plus className="h-3 w-3" />
                                                            </Button>
                                                        </div>
                                                        <div className="text-xs text-muted-foreground text-center mt-1">
                                                            Stok: {item.stock}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatMoney(item.price * item.qty, currency)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-destructive"
                                                            onClick={() => removeItem(item.product_id)}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right: Summary & Checkout */}
                    <div className="space-y-4">
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-lg">Ringkasan</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span>Subtotal</span>
                                        <span>{formatMoney(subtotal, currency)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Diskon ({formatNumber(discount_percent)}%)</span>
                                        <span>-{formatMoney(discountAmount, currency)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Pajak ({formatNumber(tax_percent)}%)</span>
                                        <span>{formatMoney(taxAmount, currency)}</span>
                                    </div>
                                    <Separator />
                                    <div className="flex justify-between font-bold text-base">
                                        <span>Total</span>
                                        <span>{formatMoney(total, currency)}</span>
                                    </div>
                                </div>

                                <Separator />

                                {/* Payment Method */}
                                <div>
                                    <Label>Metode Pembayaran</Label>
                                    <div className="flex gap-2 mt-2">
                                        <Button
                                            variant={paymentMethod === 'cash' ? 'default' : 'outline'}
                                            className="flex-1"
                                            onClick={() => setPaymentMethod('cash')}
                                        >
                                            <Banknote className="h-4 w-4 mr-2" />
                                            Tunai
                                        </Button>
                                        <Button
                                            variant={paymentMethod === 'qris' ? 'default' : 'outline'}
                                            className="flex-1"
                                            onClick={() => setPaymentMethod('qris')}
                                        >
                                            <QrCode className="h-4 w-4 mr-2" />
                                            QRIS
                                        </Button>
                                    </div>
                                </div>

                                {/* Cash payment input */}
                                {paymentMethod === 'cash' && (
                                    <div>
                                        <Label htmlFor="paid_amount">Jumlah Bayar ({currency})</Label>
                                        <Input
                                            id="paid_amount"
                                            type="text"
                                            inputMode="numeric"
                                            placeholder="Rp 0"
                                            value={paidAmount}
                                            onChange={(e) => {
                                                const raw = parseMoneyToInt(e.target.value);
                                                setPaidAmount(formatMoney(raw, currency));
                                            }}
                                            className="mt-1"
                                        />
                                        {paidInt > 0 && (
                                            <div className="mt-2 font-semibold text-success">
                                                Kembalian: {formatMoney(change, currency)}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Note */}
                                <div>
                                    <Label htmlFor="note">Catatan/Pelanggan</Label>
                                    <Input
                                        id="note"
                                        type="text"
                                        placeholder="Misal: Nama pelanggan / no. telp"
                                        value={note}
                                        onChange={(e) => setNote(e.target.value)}
                                        maxLength={255}
                                        className="mt-1"
                                    />
                                </div>

                                {/* QRIS Snap Embed */}
                                {showSnapEmbed && (
                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm flex items-center gap-2">
                                                <QrCode className="h-4 w-4" />
                                                Pembayaran QRIS
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div id="snapContainer"></div>
                                        </CardContent>
                                    </Card>
                                )}

                                {/* Checkout Button */}
                                <Button
                                    className="w-full"
                                    size="lg"
                                    variant="success"
                                    onClick={handleCheckout}
                                    disabled={!canCheckout || processing}
                                >
                                    {processing ? (
                                        <>
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            Memproses...
                                        </>
                                    ) : (
                                        <>
                                            <CheckCircle2 className="h-4 w-4 mr-2" />
                                            Proses Pembayaran
                                        </>
                                    )}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Holds Modal */}
            <Dialog open={holdsOpen} onOpenChange={setHoldsOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Inbox className="h-5 w-5" />
                            Transaksi Tertunda
                        </DialogTitle>
                    </DialogHeader>
                    <ScrollArea className="max-h-[400px]">
                        {holds.length === 0 ? (
                            <div className="text-center text-muted-foreground py-8">
                                Tidak ada transaksi tertunda.
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {holds.map((hold) => (
                                    <div
                                        key={hold.id}
                                        className="flex items-center justify-between p-3 border rounded-lg"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {hold.invoice_number}
                                                {suspendedFromId === hold.id && (
                                                    <Badge className="ml-2" variant="secondary">
                                                        Sedang dimuat
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                Catatan: {hold.note || '-'}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {new Date(hold.created_at).toLocaleString('id-ID')} •{' '}
                                                {formatMoney(hold.total, currency)}
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                onClick={() => handleResumeHold(hold.id)}
                                            >
                                                Muat
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleDeleteHold(hold.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </ScrollArea>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setHoldsOpen(false)}>
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Success Modal */}
            <Dialog open={successModal.open} onOpenChange={(open) => setSuccessModal({ ...successModal, open })}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <CheckCircle2 className="h-5 w-5 text-success" />
                            Pembayaran Berhasil
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-2">
                        <p>
                            Transaksi {successModal.method?.toUpperCase()} telah berhasil diproses.
                        </p>
                        <p className="text-sm text-muted-foreground">
                            No. Transaksi: <span className="font-semibold">{successModal.invoiceNumber}</span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Anda dapat mencetak struk untuk pelanggan.
                        </p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setSuccessModal({ open: false })}>
                            Tutup
                        </Button>
                        {successModal.transactionId && (
                            <Button
                                onClick={() => {
                                    window.open(`/transaksi/${successModal.transactionId}/struk?print=1`, '_blank');
                                }}
                            >
                                <Printer className="h-4 w-4 mr-2" />
                                Cetak Struk
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
