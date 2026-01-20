import { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
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
    Receipt,
    Search,
    Eye,
    Printer,
    Filter,
    Loader2,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { formatMoney } from '@/lib/utils';

interface Transaction {
    id: number;
    invoice_number: string;
    payment_method: string;
    status: string;
    total: number;
    created_at: string;
    user: {
        id: number;
        name: string;
    } | null;
}

interface TransactionsProps {
    currency: string;
    statuses: Array<{ value: string; name: string }>;
    methods: Array<{ value: string; name: string }>;
    filters: {
        q: string;
        status: string;
        method: string;
        from: string;
        to: string;
    };
}

interface PaginatedResponse {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function TransactionsIndex({
    currency,
    statuses,
    methods,
    filters: initialFilters,
}: TransactionsProps) {
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        q: initialFilters.q || '',
        status: initialFilters.status || '',
        method: initialFilters.method || '',
        from: initialFilters.from || '',
        to: initialFilters.to || '',
    });

    const fetchTransactions = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.set('page', String(page));
            if (filters.q) params.set('q', filters.q);
            if (filters.status && filters.status !== 'all') params.set('status', filters.status);
            if (filters.method && filters.method !== 'all') params.set('method', filters.method);
            if (filters.from) params.set('from', filters.from);
            if (filters.to) params.set('to', filters.to);

            const res = await fetch(`/transaksi/data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data: PaginatedResponse = await res.json();

            setTransactions(data.data || []);
            setPagination({
                currentPage: data.current_page,
                lastPage: data.last_page,
                perPage: data.per_page,
                total: data.total,
            });
        } catch {
            setTransactions([]);
        } finally {
            setLoading(false);
        }
    }, [filters]);

    useEffect(() => {
        fetchTransactions(1);
    }, [fetchTransactions]);

    const handleFilterChange = (key: string, value: string) => {
        setFilters((prev) => ({ ...prev, [key]: value }));
    };

    const handleSearch = () => {
        fetchTransactions(1);
    };

    const handleClearFilters = () => {
        setFilters({ q: '', status: '', method: '', from: '', to: '' });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid':
                return <Badge variant="success">LUNAS</Badge>;
            case 'pending':
                return <Badge variant="warning">PENDING</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">BATAL</Badge>;
            default:
                return <Badge variant="secondary">{status.toUpperCase()}</Badge>;
        }
    };

    const getMethodBadge = (method: string) => {
        return <Badge variant="outline">{method.toUpperCase()}</Badge>;
    };

    return (
        <AppLayout title="Transaksi">
            <div className="space-y-4">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold flex items-center gap-2">
                        <Receipt className="h-6 w-6" />
                        Transaksi
                    </h1>
                    <p className="text-muted-foreground">
                        Daftar semua transaksi yang telah dilakukan.
                    </p>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base flex items-center gap-2">
                            <Filter className="h-4 w-4" />
                            Filter
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                            <div>
                                <Label htmlFor="search">Cari</Label>
                                <div className="relative mt-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="No. Invoice, catatan..."
                                        value={filters.q}
                                        onChange={(e) => handleFilterChange('q', e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                        className="pl-9"
                                    />
                                </div>
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
                                                {s.name || s.value.toUpperCase()}
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
                                                {m.name || m.value.toUpperCase()}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
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
                            <div className="flex items-end gap-2">
                                <Button onClick={handleSearch} className="flex-1">
                                    <Search className="h-4 w-4 mr-2" />
                                    Cari
                                </Button>
                                <Button variant="outline" onClick={handleClearFilters}>
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Transactions Table */}
                <Card>
                    <CardContent className="p-0">
                        <ScrollArea className="h-[500px]">
                            {loading ? (
                                <div className="flex items-center justify-center h-full py-12">
                                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    <span className="ml-2 text-muted-foreground">Memuat...</span>
                                </div>
                            ) : transactions.length === 0 ? (
                                <div className="text-center py-12 text-muted-foreground">
                                    Tidak ada transaksi ditemukan.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>No. Invoice</TableHead>
                                            <TableHead>Tanggal</TableHead>
                                            <TableHead>Kasir</TableHead>
                                            <TableHead>Metode</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                            <TableHead className="text-right">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {transactions.map((trx) => (
                                            <TableRow key={trx.id}>
                                                <TableCell>
                                                    <Link
                                                        href={`/transaksi/${trx.id}`}
                                                        className="font-medium text-primary hover:underline"
                                                    >
                                                        {trx.invoice_number}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(trx.created_at).toLocaleString('id-ID', {
                                                        day: '2-digit',
                                                        month: '2-digit',
                                                        year: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </TableCell>
                                                <TableCell>{trx.user?.name || '-'}</TableCell>
                                                <TableCell>{getMethodBadge(trx.payment_method)}</TableCell>
                                                <TableCell>{getStatusBadge(trx.status)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatMoney(trx.total, currency)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            asChild
                                                        >
                                                            <Link href={`/transaksi/${trx.id}`}>
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            onClick={() => window.open(`/transaksi/${trx.id}/struk`, '_blank')}
                                                        >
                                                            <Printer className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </ScrollArea>
                    </CardContent>

                    {/* Pagination */}
                    {!loading && transactions.length > 0 && (
                        <div className="flex items-center justify-between px-4 py-3 border-t">
                            <div className="text-sm text-muted-foreground">
                                Menampilkan {((pagination.currentPage - 1) * pagination.perPage) + 1} -{' '}
                                {Math.min(pagination.currentPage * pagination.perPage, pagination.total)} dari{' '}
                                {pagination.total} transaksi
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchTransactions(pagination.currentPage - 1)}
                                    disabled={pagination.currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Prev
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchTransactions(pagination.currentPage + 1)}
                                    disabled={pagination.currentPage >= pagination.lastPage}
                                >
                                    Next
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
