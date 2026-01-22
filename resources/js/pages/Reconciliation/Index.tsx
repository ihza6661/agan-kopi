import { useState, useEffect } from 'react';
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
import { Banknote, QrCode, Clock, AlertTriangle, RefreshCw } from 'lucide-react';
import { formatMoney } from '@/lib/utils';

interface Props {
    currency: string;
}

interface PendingTransaction {
    id: number;
    invoice: string;
    total: number;
    created_at: string;
    cashier: string;
    age_minutes: number;
}

interface ReconciliationData {
    date: string;
    summary: {
        cash_total: number;
        qris_total: number;
        grand_total: number;
        cash_count: number;
        qris_count: number;
        pending_qris_count: number;
        canceled_qris_count: number;
    };
    pending_transactions: PendingTransaction[];
}

export default function ReconciliationIndex({ currency }: Props) {
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
    const [data, setData] = useState<ReconciliationData | null>(null);
    const [loading, setLoading] = useState(false);

    const fetchData = async () => {
        setLoading(true);
        try {
            const res = await fetch(`/rekonsiliasi-data?date=${date}`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            setData(json);
        } catch (error) {
            console.error('Failed to fetch reconciliation data:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [date]);

    return (
        <AppLayout title="Rekonsiliasi">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold">Rekonsiliasi Harian</h1>
                        <p className="text-muted-foreground">Ringkasan transaksi harian untuk rekonsiliasi kasir</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Label htmlFor="date" className="sr-only">Tanggal</Label>
                        <Input
                            id="date"
                            type="date"
                            value={date}
                            onChange={(e) => setDate(e.target.value)}
                            className="w-auto"
                        />
                        <Button variant="outline" size="icon" onClick={fetchData} disabled={loading}>
                            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                {data && (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Cash</CardTitle>
                                <Banknote className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-success">
                                    {formatMoney(data.summary.cash_total, currency)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {data.summary.cash_count} transaksi
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total QRIS</CardTitle>
                                <QrCode className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-primary">
                                    {formatMoney(data.summary.qris_total, currency)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {data.summary.qris_count} transaksi
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Keseluruhan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {formatMoney(data.summary.grand_total, currency)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {data.summary.cash_count + data.summary.qris_count} transaksi
                                </p>
                            </CardContent>
                        </Card>

                        <Card className={data.summary.pending_qris_count > 0 ? 'border-warning' : ''}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Status QRIS</CardTitle>
                                {data.summary.pending_qris_count > 0 && (
                                    <AlertTriangle className="h-4 w-4 text-warning" />
                                )}
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-baseline gap-2">
                                    <span className={`text-2xl font-bold ${data.summary.pending_qris_count > 0 ? 'text-warning' : ''}`}>
                                        {data.summary.pending_qris_count}
                                    </span>
                                    <span className="text-sm text-muted-foreground">pending</span>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {data.summary.canceled_qris_count} dibatalkan
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Pending Transactions Table */}
                {data && data.pending_transactions.length > 0 && (
                    <Card className="border-warning">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-warning">
                                <Clock className="h-5 w-5" />
                                Transaksi QRIS Menunggu Konfirmasi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. Transaksi</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.pending_transactions.map((trx) => (
                                        <TableRow key={trx.id}>
                                            <TableCell className="font-medium">{trx.invoice}</TableCell>
                                            <TableCell>{trx.cashier}</TableCell>
                                            <TableCell className="text-right">
                                                {formatMoney(trx.total, currency)}
                                            </TableCell>
                                            <TableCell>
                                                {new Date(trx.created_at).toLocaleTimeString('id-ID')}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={trx.age_minutes > 30 ? 'destructive' : 'warning'}>
                                                    {trx.age_minutes > 60
                                                        ? `${Math.floor(trx.age_minutes / 60)}j ${trx.age_minutes % 60}m`
                                                        : `${trx.age_minutes} menit`}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* No pending message */}
                {data && data.pending_transactions.length === 0 && data.summary.pending_qris_count === 0 && (
                    <Card>
                        <CardContent className="flex items-center justify-center py-8 text-muted-foreground">
                            <p>✅ Tidak ada transaksi QRIS yang menunggu konfirmasi</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
