import { useState, useEffect, useRef } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    QrCode,
    ArrowLeft,
    Eye,
    Printer,
    CheckCircle2,
    XCircle,
    Clock,
    Loader2,
} from 'lucide-react';
import { formatMoney } from '@/lib/utils';

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

interface PaymentShowProps {
    transaction: {
        id: number;
        invoice_number: string;
        payment_method: string;
        total: number;
        user: {
            id: number;
            name: string;
        } | null;
    };
    payment: {
        id: number;
        method: string;
        provider: string;
        status: string;
        amount: number;
        created_at: string;
        paid_at: string | null;
        snap_token: string | null;
        qr_url: string | null;
        qr_string: string | null;
    };
    currency: string;
    midtrans_client_key: string;
    midtrans_is_production: boolean;
}

export default function PaymentsShow({
    transaction,
    payment,
    currency,
    midtrans_client_key,
    midtrans_is_production,
}: PaymentShowProps) {
    const [status, setStatus] = useState(payment.status);
    const [statusMessage, setStatusMessage] = useState('');
    const [polling, setPolling] = useState(payment.status === 'pending');
    const pollIntervalRef = useRef<number | null>(null);

    // Load Midtrans Snap script
    useEffect(() => {
        if (payment.snap_token && payment.status === 'pending') {
            const scriptUrl = midtrans_is_production
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js';

            if (!document.querySelector(`script[src="${scriptUrl}"]`)) {
                const script = document.createElement('script');
                script.src = scriptUrl;
                script.setAttribute('data-client-key', midtrans_client_key);
                script.onload = () => {
                    if (window.snap && payment.snap_token) {
                        window.snap.embed(payment.snap_token, {
                            embedId: 'snapContainer',
                        });
                    }
                };
                document.body.appendChild(script);
            } else if (window.snap && payment.snap_token) {
                window.snap.embed(payment.snap_token, {
                    embedId: 'snapContainer',
                });
            }
        }
    }, [payment.snap_token, payment.status, midtrans_client_key, midtrans_is_production]);

    // Poll for status updates
    useEffect(() => {
        if (!polling) return;

        const poll = async () => {
            try {
                const res = await fetch(`/pembayaran/${transaction.id}/status`);
                const data = await res.json();
                const newStatus = (data.status || '').toLowerCase();

                setStatus(newStatus);

                if (newStatus === 'settlement') {
                    setStatusMessage('Pembayaran berhasil. Anda dapat mencetak struk atau kembali ke daftar pembayaran.');
                    setPolling(false);
                } else if (['expire', 'cancel', 'deny', 'failure'].includes(newStatus)) {
                    setStatusMessage(`Pembayaran tidak berhasil (${newStatus}).`);
                    setPolling(false);
                } else {
                    setStatusMessage('Menunggu pelanggan menyelesaikan pembayaran…');
                }
            } catch {
                // Ignore polling errors
            }
        };

        poll();
        pollIntervalRef.current = window.setInterval(poll, 3000);

        return () => {
            if (pollIntervalRef.current) {
                clearInterval(pollIntervalRef.current);
            }
        };
    }, [polling, transaction.id]);

    const getStatusBadge = () => {
        switch (status) {
            case 'settlement':
                return <Badge variant="success" className="text-lg px-3 py-1">BERHASIL</Badge>;
            case 'pending':
                return <Badge variant="warning" className="text-lg px-3 py-1">MENUNGGU</Badge>;
            case 'expire':
            case 'cancel':
            case 'deny':
            case 'failure':
                return <Badge variant="destructive" className="text-lg px-3 py-1">GAGAL</Badge>;
            default:
                return <Badge variant="secondary" className="text-lg px-3 py-1">{status.toUpperCase()}</Badge>;
        }
    };

    const getStatusIcon = () => {
        switch (status) {
            case 'settlement':
                return <CheckCircle2 className="h-12 w-12 text-green-500" />;
            case 'pending':
                return <Clock className="h-12 w-12 text-yellow-500" />;
            default:
                return <XCircle className="h-12 w-12 text-red-500" />;
        }
    };

    return (
        <AppLayout title="Pembayaran QRIS">
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-2 justify-between items-center">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/pembayaran">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2">
                                <QrCode className="h-6 w-6" />
                                Pembayaran QRIS
                            </h1>
                            <p className="text-muted-foreground">{transaction.invoice_number}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/transaksi/${transaction.id}`}>
                                <Eye className="h-4 w-4 mr-2" />
                                Detail Transaksi
                            </Link>
                        </Button>
                        <Button
                            onClick={() => window.open(`/transaksi/${transaction.id}/struk`, '_blank')}
                        >
                            <Printer className="h-4 w-4 mr-2" />
                            Cetak Struk
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* QR Code / Snap Section */}
                    <Card>
                        <CardContent className="p-6 text-center">
                            <p className="mb-4 text-lg">
                                Total: <strong>{formatMoney(transaction.total, currency)}</strong>
                            </p>

                            {payment.snap_token && payment.status === 'pending' ? (
                                <div>
                                    <div id="snapContainer" className="min-h-[400px]"></div>
                                    <p className="text-sm text-muted-foreground mt-2">
                                        Tampilkan QR ini ke pelanggan untuk dipindai.
                                    </p>
                                </div>
                            ) : payment.qr_url ? (
                                <div>
                                    <img
                                        src={payment.qr_url}
                                        alt="QRIS"
                                        className="mx-auto rounded-lg border max-w-[320px]"
                                    />
                                    <p className="text-sm text-muted-foreground mt-2">
                                        Tampilkan QR ini ke pelanggan untuk dipindai.
                                    </p>
                                </div>
                            ) : payment.qr_string ? (
                                <div>
                                    <img
                                        src={`https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(payment.qr_string)}`}
                                        alt="QRIS"
                                        className="mx-auto rounded-lg border max-w-[320px]"
                                    />
                                    <p className="text-sm text-muted-foreground mt-2">
                                        Tampilkan QR ini ke pelanggan untuk dipindai.
                                    </p>
                                </div>
                            ) : (
                                <div className="py-12 text-muted-foreground">
                                    QR belum tersedia.
                                </div>
                            )}

                            <div className="mt-4">
                                {getStatusBadge()}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Payment Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Ringkasan Pembayaran</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div className="text-muted-foreground">Invoice</div>
                                    <div className="font-semibold">{transaction.invoice_number}</div>
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Kasir</div>
                                    <div className="font-semibold">{transaction.user?.name || '-'}</div>
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Metode</div>
                                    <div className="uppercase">{transaction.payment_method}</div>
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Provider</div>
                                    <div className="uppercase">{payment.provider || '-'}</div>
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Status</div>
                                    {getStatusBadge()}
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Dibuat</div>
                                    <div>
                                        {new Date(payment.created_at).toLocaleString('id-ID', {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Dibayar</div>
                                    <div>
                                        {payment.paid_at
                                            ? new Date(payment.paid_at).toLocaleString('id-ID', {
                                                  day: '2-digit',
                                                  month: '2-digit',
                                                  year: 'numeric',
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                              })
                                            : '-'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-muted-foreground">Jumlah</div>
                                    <div className="font-semibold">{formatMoney(payment.amount, currency)}</div>
                                </div>
                            </div>

                            <Separator />

                            {/* Status Message */}
                            <div className="flex items-start gap-3 p-4 rounded-lg bg-muted">
                                {polling ? (
                                    <Loader2 className="h-5 w-5 animate-spin text-muted-foreground mt-0.5" />
                                ) : (
                                    getStatusIcon()
                                )}
                                <div className="flex-1">
                                    <p className="text-sm">
                                        {statusMessage || 'Menunggu pelanggan menyelesaikan pembayaran…'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
