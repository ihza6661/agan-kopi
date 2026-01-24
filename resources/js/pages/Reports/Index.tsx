import { useState, useCallback } from 'react';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    BarChart3,
    Download,
    Search,
    Loader2,
    DollarSign,
    ShoppingCart,
    TrendingUp,
    Package,
    ArrowUp,
    ArrowDown,
} from 'lucide-react';
import { formatMoney, formatNumber } from '@/lib/utils';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from 'recharts';

interface Summary {
    total_sales: number;
    total_transactions: number;
    average_order_value: number;
    total_items_sold: number;
}

interface ProductStat {
    id: number;
    name: string;
    sku: string;
    qty: number;
    total: number;
}

interface PeriodSale {
    date: string;
    trx_count: number;
    items_qty: number;
    total: number;
}

interface ReportsProps {
    currency: string;
    summary: Summary;
    topProducts: ProductStat[];
    slowProducts: ProductStat[];
    periodSales: PeriodSale[];
    filters: {
        from: string;
        to: string;
        status: string;
        method: string;
        period: string;
    };
    methods: Array<{ value: string }>;
    statuses: Array<{ value: string }>;
}

export default function ReportsIndex({
    currency,
    summary: initialSummary,
    topProducts: initialTop,
    slowProducts: initialSlow,
    periodSales: initialPeriodSales,
    filters: initialFilters,
    methods,
    statuses,
}: ReportsProps) {
    const [summary, setSummary] = useState<Summary>(initialSummary ?? {
        total_sales: 0,
        total_transactions: 0,
        average_order_value: 0,
        total_items_sold: 0,
    });
    const [topProducts, setTopProducts] = useState<ProductStat[]>(initialTop ?? []);
    const [slowProducts, setSlowProducts] = useState<ProductStat[]>(initialSlow ?? []);
    const [periodSales, setPeriodSales] = useState<PeriodSale[]>(initialPeriodSales ?? []);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({
        from: initialFilters?.from || '',
        to: initialFilters?.to || '',
        status: initialFilters?.status || 'paid',
        method: initialFilters?.method || '',
        period: initialFilters?.period || 'daily',
    });

    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (filters.from) params.set('from', filters.from);
            if (filters.to) params.set('to', filters.to);
            if (filters.status && filters.status !== 'all') params.set('status', filters.status);
            if (filters.method && filters.method !== 'all') params.set('method', filters.method);
            params.set('period', filters.period);

            const res = await fetch(`/laporan-data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            setSummary(data.summary);
            setTopProducts(data.topProducts);
            setSlowProducts(data.slowProducts);
            setPeriodSales(data.periodSales);
        } catch {
            // Keep existing data on error
        } finally {
            setLoading(false);
        }
    }, [filters]);

    const handleFilterChange = (key: string, value: string) => {
        setFilters((prev) => ({ ...prev, [key]: value }));
    };

    const handleSearch = () => {
        fetchData();
    };

    const handleDownload = () => {
        const params = new URLSearchParams();
        if (filters.from) params.set('from', filters.from);
        if (filters.to) params.set('to', filters.to);
        if (filters.status && filters.status !== 'all') params.set('status', filters.status);
        if (filters.method && filters.method !== 'all') params.set('method', filters.method);
        params.set('period', filters.period);
        
        window.open(`/laporan/download?${params.toString()}`, '_blank');
    };

    return (
        <AppLayout title="Laporan">
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-4 justify-between items-start">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <BarChart3 className="h-6 w-6" />
                            Laporan Penjualan
                        </h1>
                        <p className="text-muted-foreground">
                            Analisis penjualan dan performa produk.
                        </p>
                    </div>
                    <Button onClick={handleDownload} variant="outline">
                        <Download className="h-4 w-4 mr-2" />
                        Export CSV
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4">
                        <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                            <div>
                                <Label htmlFor="from">Dari Tanggal</Label>
                                <Input
                                    id="from"
                                    type="date"
                                    value={filters.from}
                                    onChange={(e) => handleFilterChange('from', e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="to">Sampai Tanggal</Label>
                                <Input
                                    id="to"
                                    type="date"
                                    value={filters.to}
                                    onChange={(e) => handleFilterChange('to', e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label>Status</Label>
                                <Select
                                    value={filters.status}
                                    onValueChange={(value: string) => handleFilterChange('status', value)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua</SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.value.toUpperCase()}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Metode</Label>
                                <Select
                                    value={filters.method}
                                    onValueChange={(value: string) => handleFilterChange('method', value)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua</SelectItem>
                                        {methods.map((m) => (
                                            <SelectItem key={m.value} value={m.value}>
                                                {m.value.toUpperCase()}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Periode</Label>
                                <Select
                                    value={filters.period}
                                    onValueChange={(value: string) => handleFilterChange('period', value)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="daily">Harian</SelectItem>
                                        <SelectItem value="monthly">Bulanan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button onClick={handleSearch} className="w-full" disabled={loading}>
                                    {loading ? (
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    ) : (
                                        <Search className="h-4 w-4 mr-2" />
                                    )}
                                    Filter
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Penjualan</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatMoney(summary.total_sales, currency)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Transaksi</CardTitle>
                            <ShoppingCart className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatNumber(summary.total_transactions)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Rata-rata Transaksi</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatMoney(summary.average_order_value, currency)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Item Terjual</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatNumber(summary.total_items_sold)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Sales Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Grafik Penjualan ({filters.period === 'monthly' ? 'Bulanan' : 'Harian'})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={periodSales}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="date" className="text-xs" />
                                    <YAxis className="text-xs" tickFormatter={(v) => formatNumber(v)} />
                                    <Tooltip
                                        formatter={(value) => formatMoney(Number(value), currency)}
                                        labelFormatter={(label) => `Tanggal: ${label}`}
                                    />
                                    <Line 
                                        type="monotone" 
                                        dataKey="total" 
                                        stroke="#3b82f6" 
                                        strokeWidth={2} 
                                        dot={true}
                                        activeDot={{ r: 6 }}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                {/* Top & Slow Products */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Top Products */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base flex items-center gap-2">
                                <ArrowUp className="h-4 w-4 text-green-500" />
                                Produk Terlaris
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <ScrollArea className="h-[280px]">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Produk</TableHead>
                                            <TableHead className="text-center">Qty</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {topProducts.map((p, i) => (
                                            <TableRow key={p.id}>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="outline" className="w-6 h-6 p-0 justify-center shrink-0">
                                                            {i + 1}
                                                        </Badge>
                                                        <span className="font-medium truncate">{p.name}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">{formatNumber(p.qty)}</TableCell>
                                                <TableCell className="text-right whitespace-nowrap">
                                                    {formatMoney(p.total, currency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                        {topProducts.length === 0 && (
                                            <TableRow>
                                                <TableCell colSpan={3} className="text-center text-muted-foreground">
                                                    Tidak ada data.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </ScrollArea>
                        </CardContent>
                    </Card>

                    {/* Slow Products */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base flex items-center gap-2">
                                <ArrowDown className="h-4 w-4 text-orange-500" />
                                Produk Perputaran Lambat
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <ScrollArea className="h-[280px]">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Produk</TableHead>
                                            <TableHead className="text-center">Qty</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {slowProducts.map((p, i) => (
                                            <TableRow key={p.id}>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="secondary" className="w-6 h-6 p-0 justify-center shrink-0">
                                                            {i + 1}
                                                        </Badge>
                                                        <span className="font-medium truncate">{p.name}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">{formatNumber(p.qty)}</TableCell>
                                                <TableCell className="text-right whitespace-nowrap">
                                                    {formatMoney(p.total, currency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                        {slowProducts.length === 0 && (
                                            <TableRow>
                                                <TableCell colSpan={3} className="text-center text-muted-foreground">
                                                    Tidak ada data.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </ScrollArea>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
