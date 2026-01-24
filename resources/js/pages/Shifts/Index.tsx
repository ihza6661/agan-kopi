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
import { Clock, RefreshCw, User, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { formatMoney } from '@/lib/utils';

interface Props {
    currency: string;
}

interface ShiftData {
    id: number;
    user: string;
    started_at: string;
    ended_at: string | null;
    is_active: boolean;
    opening_cash: number;
    closing_cash: number | null;
    cash_total: number;
    qris_total: number;
    total_sales: number;
    transaction_count: number;
    expected_cash: number;
    variance: number | null;
}

interface PaginatedResponse {
    data: ShiftData[];
    current_page: number;
    last_page: number;
    total: number;
}

export default function ShiftsIndex({ currency }: Props) {
    const [fromDate, setFromDate] = useState(() => {
        const d = new Date();
        d.setDate(d.getDate() - 7);
        return d.toISOString().split('T')[0];
    });
    const [toDate, setToDate] = useState(new Date().toISOString().split('T')[0]);
    const [data, setData] = useState<PaginatedResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);

    const fetchData = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                from: fromDate,
                to: toDate,
                page: String(page),
                per_page: '20',
            });
            const res = await fetch(`/shift-data?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            setData(json);
        } catch (error) {
            console.error('Failed to fetch shift data:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [fromDate, toDate, page]);

    const getVarianceIcon = (variance: number | null) => {
        if (variance === null) return <Minus className="h-4 w-4 text-muted-foreground" />;
        if (variance > 0) return <TrendingUp className="h-4 w-4 text-success" />;
        if (variance < 0) return <TrendingDown className="h-4 w-4 text-destructive" />;
        return <Minus className="h-4 w-4 text-muted-foreground" />;
    };

    const getVarianceClass = (variance: number | null) => {
        if (variance === null) return 'text-muted-foreground';
        if (variance > 0) return 'text-success';
        if (variance < 0) return 'text-destructive';
        return '';
    };

    return (
        <AppLayout title="Riwayat Shift">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold">Riwayat Shift</h1>
                        <p className="text-muted-foreground">Audit shift kasir dengan variance</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Label htmlFor="from" className="sr-only">Dari</Label>
                        <Input
                            id="from"
                            type="date"
                            value={fromDate}
                            onChange={(e) => setFromDate(e.target.value)}
                            className="w-auto"
                        />
                        <span className="text-muted-foreground">–</span>
                        <Label htmlFor="to" className="sr-only">Sampai</Label>
                        <Input
                            id="to"
                            type="date"
                            value={toDate}
                            onChange={(e) => setToDate(e.target.value)}
                            className="w-auto"
                        />
                        <Button variant="outline" size="icon" onClick={fetchData} disabled={loading}>
                            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                        </Button>
                    </div>
                </div>

                {/* Shifts Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Clock className="h-5 w-5" />
                            Shift ({data?.total || 0})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader className="hidden lg:table-header-group">
                                    <TableRow>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead className="text-right">Pembukaan</TableHead>
                                        <TableHead className="text-right">Penutupan</TableHead>
                                        <TableHead className="text-right">Seharusnya</TableHead>
                                        <TableHead className="text-right">Variance</TableHead>
                                        <TableHead className="text-right">Penjualan</TableHead>
                                        <TableHead>Trx</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data?.data.map((shift) => (
                                        <TableRow 
                                            key={shift.id}
                                            className="flex flex-col lg:table-row border rounded-lg lg:border-0 mb-3 lg:mb-0 mx-3 lg:mx-0 p-4 lg:p-0"
                                        >
                                            <TableCell className="flex items-center justify-between lg:table-cell pb-1 lg:pb-0 border-0">
                                                <span className="font-semibold lg:font-medium">
                                                    {new Date(shift.started_at).toLocaleDateString('id-ID')}
                                                </span>
                                                {shift.ended_at  
                                                    ? null
                                                    : <Badge variant="default" className="lg:hidden">Aktif</Badge>}
                                            </TableCell>
                                            <TableCell className="flex items-center gap-2 lg:table-cell pb-1 lg:pb-0 border-0">
                                                <User className="h-4 w-4 text-muted-foreground" />
                                                {shift.user}
                                            </TableCell>
                                            <TableCell className="flex flex-col lg:table-cell pb-2 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Waktu</span>
                                                <span className="text-sm">
                                                    {new Date(shift.started_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                                                    {' – '}
                                                    {shift.ended_at 
                                                        ? new Date(shift.ended_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                                                        : <Badge variant="default" className="hidden lg:inline-flex">Aktif</Badge>}
                                                </span>
                                            </TableCell>
                                            <TableCell className="flex justify-between lg:table-cell lg:text-right pb-1 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Pembukaan</span>
                                                <span className="text-sm">{formatMoney(shift.opening_cash, currency)}</span>
                                            </TableCell>
                                            <TableCell className="flex justify-between lg:table-cell lg:text-right pb-1 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Penutupan</span>
                                                <span className="text-sm">
                                                    {shift.closing_cash !== null 
                                                        ? formatMoney(shift.closing_cash, currency)
                                                        : '-'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="flex justify-between lg:table-cell lg:text-right pb-1 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Seharusnya</span>
                                                <span className="text-sm">{formatMoney(shift.expected_cash, currency)}</span>
                                            </TableCell>
                                            <TableCell className="flex justify-between lg:table-cell lg:text-right pb-1 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Variance</span>
                                                <div className="flex items-center gap-1">
                                                    {getVarianceIcon(shift.variance)}
                                                    <span className={getVarianceClass(shift.variance)}>
                                                        {shift.variance !== null 
                                                            ? formatMoney(shift.variance, currency)
                                                            : '-'}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="flex justify-between lg:table-cell lg:text-right pb-1 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Penjualan</span>
                                                <span className="font-semibold">{formatMoney(shift.total_sales, currency)}</span>
                                            </TableCell>
                                            <TableCell className="flex justify-between lg:table-cell pb-0 lg:pb-0 border-0">
                                                <span className="text-xs text-muted-foreground lg:hidden">Transaksi</span>
                                                <span className="text-sm">{shift.transaction_count}</span>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {(!data || data.data.length === 0) && (
                                        <TableRow>
                                            <TableCell colSpan={9} className="text-center py-8 text-muted-foreground">
                                                Tidak ada shift dalam rentang tanggal ini
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {data && data.last_page > 1 && (
                            <div className="flex justify-center items-center gap-2 mt-4">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                    disabled={page <= 1}
                                >
                                    Sebelumnya
                                </Button>
                                <span className="text-sm text-muted-foreground">
                                    {page} / {data.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPage((p) => Math.min(data.last_page, p + 1))}
                                    disabled={page >= data.last_page}
                                >
                                    Selanjutnya
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
