import { Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import {
    Receipt,
    ArrowLeft,
    Printer,
    User,
    CreditCard,
    Calendar,
    FileText,
} from 'lucide-react';
import { formatMoney, formatNumber } from '@/lib/utils';

interface TransactionDetail {
    id: number;
    product_id: number;
    product_name: string;
    quantity: number;
    price: number;
    total: number;
    product: {
        id: number;
        name: string;
        sku: string;
    } | null;
}

interface Payment {
    id: number;
    transaction_id: number;
    method: string;
    amount: number;
    status: string;
    reference: string | null;
    created_at: string;
}

interface Transaction {
    id: number;
    invoice_number: string;
    payment_method: string;
    status: string;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total: number;
    paid_amount: number;
    change_amount: number;
    note: string | null;
    created_at: string;
    user: {
        id: number;
        name: string;
    } | null;
    details: TransactionDetail[];
    latest_payment: Payment | null;
}

interface TransactionShowProps {
    trx: Transaction;
    currency: string;
}

export default function TransactionShow({ trx, currency }: TransactionShowProps) {
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid':
                return <Badge variant="success" className="text-sm">LUNAS</Badge>;
            case 'pending':
                return <Badge variant="warning" className="text-sm">PENDING</Badge>;
            case 'cancelled':
                return <Badge variant="destructive" className="text-sm">BATAL</Badge>;
            default:
                return <Badge variant="secondary" className="text-sm">{status.toUpperCase()}</Badge>;
        }
    };

    return (
        <AppLayout title={`Transaksi ${trx.invoice_number}`}>
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-4 justify-between items-start">
                    <div>
                        <div className="flex items-center gap-2 mb-2">
                            <Button variant="ghost" size="icon" asChild>
                                <Link href="/transaksi">
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <h1 className="text-2xl font-bold flex items-center gap-2">
                                <Receipt className="h-6 w-6" />
                                {trx.invoice_number}
                            </h1>
                            {getStatusBadge(trx.status)}
                        </div>
                        <p className="text-muted-foreground">
                            Detail transaksi dan item yang dibeli.
                        </p>
                    </div>
                    <Button
                        onClick={() => window.open(`/transaksi/${trx.id}/struk?print=1`, '_blank')}
                    >
                        <Printer className="h-4 w-4 mr-2" />
                        Cetak Struk
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Transaction Info */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                Informasi Transaksi
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">No. Invoice</span>
                                <span className="font-medium">{trx.invoice_number}</span>
                            </div>
                            <Separator />
                            <div className="flex justify-between items-center">
                                <span className="text-muted-foreground flex items-center gap-1">
                                    <Calendar className="h-3 w-3" />
                                    Tanggal
                                </span>
                                <span>
                                    {new Date(trx.created_at).toLocaleString('id-ID', {
                                        day: '2-digit',
                                        month: 'long',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </span>
                            </div>
                            <Separator />
                            <div className="flex justify-between items-center">
                                <span className="text-muted-foreground flex items-center gap-1">
                                    <User className="h-3 w-3" />
                                    Kasir
                                </span>
                                <span>{trx.user?.name || '-'}</span>
                            </div>
                            <Separator />
                            <div className="flex justify-between items-center">
                                <span className="text-muted-foreground flex items-center gap-1">
                                    <CreditCard className="h-3 w-3" />
                                    Metode
                                </span>
                                <Badge variant="outline">{trx.payment_method.toUpperCase()}</Badge>
                            </div>
                            {trx.note && (
                                <>
                                    <Separator />
                                    <div>
                                        <span className="text-muted-foreground block mb-1">Catatan</span>
                                        <span className="text-sm break-words">{trx.note}</span>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Payment Summary */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base flex items-center gap-2">
                                <CreditCard className="h-4 w-4" />
                                Ringkasan Pembayaran
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span>{formatMoney(trx.subtotal, currency)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Diskon</span>
                                <span>-{formatMoney(trx.discount_amount, currency)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Pajak</span>
                                <span>{formatMoney(trx.tax_amount, currency)}</span>
                            </div>
                            <Separator />
                            <div className="flex justify-between font-bold text-base">
                                <span>Total</span>
                                <span>{formatMoney(trx.total, currency)}</span>
                            </div>
                            {trx.payment_method === 'cash' && (
                                <>
                                    <Separator />
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Dibayar</span>
                                        <span>{formatMoney(trx.paid_amount, currency)}</span>
                                    </div>
                                    <div className="flex justify-between text-green-600">
                                        <span>Kembalian</span>
                                        <span>{formatMoney(trx.change_amount, currency)}</span>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Payment Details (if QRIS) */}
                    {trx.latest_payment && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Detail Pembayaran</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Metode</span>
                                    <Badge variant="outline">
                                        {trx.latest_payment.method.toUpperCase()}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Status</span>
                                    <Badge
                                        variant={
                                            trx.latest_payment.status === 'success'
                                                ? 'success'
                                                : trx.latest_payment.status === 'pending'
                                                    ? 'warning'
                                                    : 'secondary'
                                        }
                                    >
                                        {trx.latest_payment.status.toUpperCase()}
                                    </Badge>
                                </div>
                                {trx.latest_payment.reference && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Referensi</span>
                                        <span className="font-mono text-xs">
                                            {trx.latest_payment.reference}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Jumlah</span>
                                    <span>{formatMoney(trx.latest_payment.amount, currency)}</span>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Items Table */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Item Transaksi ({trx.details.length} item)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader className="hidden sm:table-header-group">
                                    <TableRow>
                                        <TableHead className="w-12">#</TableHead>
                                        <TableHead>Produk</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead className="text-right">Harga</TableHead>
                                        <TableHead className="text-center">Qty</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {trx.details.map((detail, index) => (
                                        <TableRow 
                                            key={detail.id}
                                            className="flex flex-col sm:table-row border rounded-lg sm:border-0 mb-3 sm:mb-0 mx-3 sm:mx-0 p-4 sm:p-0"
                                        >
                                            <TableCell className="flex items-center gap-2 sm:table-cell pb-0 sm:pb-0 border-0 sm:w-12">
                                                <span className="text-muted-foreground text-xs sm:text-sm">{index + 1}.</span>
                                                <span className="font-semibold text-base sm:hidden">
                                                    {detail.product?.name || detail.product_name || `Produk #${detail.product_id}`}
                                                </span>
                                            </TableCell>
                                            <TableCell className="hidden sm:table-cell font-medium break-words border-0">
                                                {detail.product?.name || detail.product_name || `Produk #${detail.product_id}`}
                                            </TableCell>
                                            <TableCell className="flex flex-col sm:table-cell pb-1 sm:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground sm:hidden">SKU</span>
                                                <span className="text-sm text-muted-foreground">
                                                    {detail.product?.sku || '-'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="flex justify-between sm:table-cell sm:text-right pb-1 sm:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground sm:hidden">Harga</span>
                                                <span className="whitespace-nowrap">{formatMoney(detail.price, currency)}</span>
                                            </TableCell>
                                            <TableCell className="flex justify-between sm:table-cell sm:text-center pb-1 sm:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground sm:hidden">Qty</span>
                                                <span>{formatNumber(detail.quantity)}</span>
                                            </TableCell>
                                            <TableCell className="flex justify-between sm:table-cell sm:text-right pt-2 sm:pt-0 border-0 border-t sm:border-t-0">
                                                <span className="text-xs text-muted-foreground sm:hidden">Total</span>
                                                <span className="font-semibold sm:font-medium whitespace-nowrap">
                                                    {formatMoney(detail.total, currency)}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
