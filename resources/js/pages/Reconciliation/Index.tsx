import { useState, useEffect } from 'react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Banknote, QrCode, Clock, AlertTriangle, RefreshCw, User } from 'lucide-react';
import { ResponsiveTable } from '@/components/ui/responsive-table';
import { formatMoney } from '@/lib/utils';

interface Props {
    currency: string;
}

interface ShiftOption {
    id: number;
    user_name: string;
    started_at: string;
    ended_at: string | null;
    is_active: boolean;
    total_sales: number;
}

interface ShiftInfo {
    id: number;
    user_name: string;
    started_at: string;
    ended_at: string | null;
    opening_cash: number;
    closing_cash: number | null;
    expected_cash: number;
    variance: number | null;
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
    shift_id: number | null;
    shift: ShiftInfo | null;
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
    const [shiftId, setShiftId] = useState<string>('all');
    const [shifts, setShifts] = useState<ShiftOption[]>([]);
    const [data, setData] = useState<ReconciliationData | null>(null);
    const [loading, setLoading] = useState(false);

    // Fetch available shifts for the selected date
    const fetchShifts = async () => {
        try {
            const res = await fetch(`/rekonsiliasi-shifts?date=${date}`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            setShifts(json.shifts || []);
            
            // Default to active shift if available
            if (json.active_shift_id) {
                setShiftId(String(json.active_shift_id));
            } else if (json.shifts?.length > 0) {
                setShiftId(String(json.shifts[0].id));
            } else {
                setShiftId('all');
            }
        } catch {
            setShifts([]);
        }
    };

    // Fetch reconciliation data
    const fetchData = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ date });
            if (shiftId !== 'all') {
                params.append('shift_id', shiftId);
            }
            const res = await fetch(`/rekonsiliasi-data?${params}`, {
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
        fetchShifts();
    }, [date]);

    useEffect(() => {
        fetchData();
    }, [date, shiftId]);

    return (
        <AppLayout title="Rekonsiliasi">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold">Rekonsiliasi</h1>
                        <p className="text-muted-foreground">Ringkasan transaksi per shift</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Label htmlFor="date" className="sr-only">Tanggal</Label>
                        <Input
                            id="date"
                            type="date"
                            value={date}
                            onChange={(e) => setDate(e.target.value)}
                            className="w-auto"
                        />
                        <Select value={shiftId} onValueChange={setShiftId}>
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Pilih Shift" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Shift</SelectItem>
                                {shifts.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.user_name} ({s.started_at}–{s.ended_at || 'aktif'})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button variant="outline" size="icon" onClick={fetchData} disabled={loading}>
                            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                        </Button>
                    </div>
                </div>

                {/* Shift Info Card (when specific shift selected) */}
                {data?.shift && (
                    <Card className="border-primary">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-sm">
                                <User className="h-4 w-4" />
                                Shift: {data.shift.user_name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground">Pembukaan</p>
                                    <p className="font-semibold">{formatMoney(data.shift.opening_cash, currency)}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">Penutupan</p>
                                    <p className="font-semibold">
                                        {data.shift.closing_cash !== null 
                                            ? formatMoney(data.shift.closing_cash, currency)
                                            : '(belum ditutup)'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">Seharusnya</p>
                                    <p className="font-semibold">{formatMoney(data.shift.expected_cash, currency)}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">Selisih</p>
                                    <p className={`font-semibold ${
                                        data.shift.variance !== null 
                                            ? (data.shift.variance < 0 ? 'text-destructive' : data.shift.variance > 0 ? 'text-success' : '')
                                            : ''
                                    }`}>
                                        {data.shift.variance !== null 
                                            ? formatMoney(data.shift.variance, currency)
                                            : '-'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

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
                            <ResponsiveTable>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="min-w-[150px]">No. Transaksi</TableHead>
                                            <TableHead className="min-w-[120px]">Kasir</TableHead>
                                            <TableHead className="min-w-[120px] text-right">Total</TableHead>
                                            <TableHead className="min-w-[100px]">Waktu</TableHead>
                                            <TableHead className="min-w-[120px]">Status</TableHead>
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
                            </ResponsiveTable>
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
