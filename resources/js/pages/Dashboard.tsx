import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DollarSign,
    Receipt,
    Package,
    AlertTriangle,
    TrendingUp,
    ShoppingBasket,
} from 'lucide-react';
import { formatMoney, formatNumber } from '@/lib/utils';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from 'recharts';

interface TopProduct {
    name: string;
    qty: number;
    total: number;
}

interface DashboardProps {
    currency: string;
    salesToday: number;
    trxToday: number;
    outOfStock: number;
    lowStock: number;
    chartLabels: string[];
    chartValues: number[];
    topToday: TopProduct[];
    sales7days: number;
    trx7days: number;
    items7days: number;
    aov7days: number;
    bestDay: { label: string; value: number } | null;
}

export default function Dashboard({
    currency,
    salesToday,
    trxToday,
    outOfStock,
    lowStock,
    chartLabels,
    chartValues,
    topToday,
    sales7days,
    trx7days,
    items7days,
    aov7days,
    bestDay,
}: DashboardProps) {
    // Prepare chart data
    const chartData = chartLabels.map((label, index) => ({
        name: label,
        value: chartValues[index] || 0,
    }));

    const stats = [
        {
            title: 'Penjualan Hari Ini',
            value: formatMoney(salesToday, currency),
            icon: DollarSign,
            color: 'text-green-600',
            bgColor: 'bg-green-100',
        },
        {
            title: 'Transaksi',
            value: formatNumber(trxToday),
            icon: Receipt,
            color: 'text-blue-600',
            bgColor: 'bg-blue-100',
        },
        {
            title: 'Produk Habis',
            value: formatNumber(outOfStock),
            icon: Package,
            color: 'text-amber-600',
            bgColor: 'bg-amber-100',
        },
        {
            title: 'Stok Rendah',
            value: formatNumber(lowStock),
            icon: AlertTriangle,
            color: 'text-red-600',
            bgColor: 'bg-red-100',
        },
        {
            title: 'Total 7 Hari',
            value: formatMoney(sales7days, currency),
            icon: TrendingUp,
            color: 'text-purple-600',
            bgColor: 'bg-purple-100',
        },
        {
            title: 'Rata-rata Order',
            value: formatMoney(aov7days, currency),
            icon: ShoppingBasket,
            color: 'text-slate-600',
            bgColor: 'bg-slate-100',
        },
    ];

    return (
        <AppLayout title="Dashboard">
            <div className="space-y-6">
                {/* Page Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground">
                        Ringkasan aktivitas kasir dan penjualan.
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {stats.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <Card key={stat.title}>
                                <CardContent className="flex items-center gap-4 p-6">
                                    <div className={`rounded-full p-3 ${stat.bgColor}`}>
                                        <Icon className={`h-5 w-5 ${stat.color}`} />
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">{stat.title}</p>
                                        <p className="text-xl font-bold">{stat.value}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Charts and Tables */}
                <div className="grid gap-6 lg:grid-cols-7">
                    {/* Sales Chart */}
                    <Card className="lg:col-span-4">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp className="h-5 w-5" />
                                Penjualan 7 Hari Terakhir
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={chartData}>
                                        <defs>
                                            <linearGradient id="colorValue" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3} />
                                                <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis
                                            dataKey="name"
                                            tick={{ fontSize: 12 }}
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <YAxis
                                            tick={{ fontSize: 12 }}
                                            tickLine={false}
                                            axisLine={false}
                                            tickFormatter={(value) => formatNumber(value)}
                                        />
                                        <Tooltip
                                            formatter={(value) => [formatMoney(Number(value) || 0, currency), 'Penjualan']}
                                            contentStyle={{
                                                backgroundColor: 'hsl(var(--popover))',
                                                border: '1px solid hsl(var(--border))',
                                                borderRadius: '8px',
                                            }}
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="value"
                                            stroke="#3b82f6"
                                            strokeWidth={2}
                                            fillOpacity={1}
                                            fill="url(#colorValue)"
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Top Products */}
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                ⭐ Top Produk Hari Ini
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topToday.length === 0 ? (
                                <p className="text-muted-foreground text-center py-8">
                                    Belum ada penjualan hari ini.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Produk</TableHead>
                                            <TableHead className="text-right">Qty</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {topToday.map((product, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{product.name}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatNumber(product.qty)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(product.total, currency)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Summary Stats */}
                <Card>
                    <CardContent className="flex flex-wrap gap-8 p-6">
                        <div>
                            <p className="text-sm text-muted-foreground">Item Terjual (7 hari)</p>
                            <p className="text-xl font-semibold">{formatNumber(items7days)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Transaksi (7 hari)</p>
                            <p className="text-xl font-semibold">{formatNumber(trx7days)}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Hari Terbaik</p>
                            <p className="text-xl font-semibold">
                                {bestDay ? `${bestDay.label} (${formatMoney(bestDay.value, currency)})` : '-'}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
