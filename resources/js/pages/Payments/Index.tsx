import { useState, useEffect, useCallback } from "react";
import { Link } from "@inertiajs/react";
import AppLayout from "@/layouts/AppLayout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    CreditCard,
    Search,
    Eye,
    Printer,
    Filter,
    Loader2,
    ChevronLeft,
    ChevronRight,
} from "lucide-react";
import { formatMoney } from "@/lib/utils";

interface Payment {
    id: number;
    invoice: string;
    transaction_id: number;
    cashier: string;
    method: string;
    provider: string;
    status: string;
    amount: number;
    created_at: string;
    paid_at: string | null;
    is_qris_pending: boolean;
}

interface PaymentsProps {
    currency: string;
    statuses: Array<{ value: string }>;
    methods: Array<{ value: string }>;
    filters: {
        q: string;
        status: string;
        method: string;
        provider: string;
        from: string;
        to: string;
    };
}

interface PaginatedResponse {
    data: Payment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function PaymentsIndex({
    currency,
    statuses,
    methods,
    filters: initialFilters,
}: PaymentsProps) {
    const [payments, setPayments] = useState<Payment[]>([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        q: initialFilters.q || "",
        status: initialFilters.status || "",
        method: initialFilters.method || "",
        provider: initialFilters.provider || "",
        from: initialFilters.from || "",
        to: initialFilters.to || "",
    });

    const fetchPayments = useCallback(
        async (page = 1) => {
            setLoading(true);
            try {
                const params = new URLSearchParams();
                params.set("page", String(page));
                if (filters.q) params.set("q", filters.q);
                if (filters.status && filters.status !== "all")
                    params.set("status", filters.status);
                if (filters.method && filters.method !== "all")
                    params.set("method", filters.method);
                if (filters.provider) params.set("provider", filters.provider);
                if (filters.from) params.set("from", filters.from);
                if (filters.to) params.set("to", filters.to);

                const res = await fetch(
                    `/pembayaran-data?${params.toString()}`,
                    {
                        headers: { Accept: "application/json" },
                    },
                );
                const data: PaginatedResponse = await res.json();

                setPayments(data.data || []);
                setPagination({
                    currentPage: data.current_page,
                    lastPage: data.last_page,
                    perPage: data.per_page,
                    total: data.total,
                });
            } catch {
                setPayments([]);
            } finally {
                setLoading(false);
            }
        },
        [filters],
    );

    useEffect(() => {
        fetchPayments(1);
    }, [fetchPayments]);

    const handleFilterChange = (key: string, value: string) => {
        setFilters((prev) => ({ ...prev, [key]: value }));
    };

    const handleSearch = () => {
        fetchPayments(1);
    };

    const handleClearFilters = () => {
        setFilters({
            q: "",
            status: "",
            method: "",
            provider: "",
            from: "",
            to: "",
        });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case "settlement":
                return <Badge variant="success">SETTLEMENT</Badge>;
            case "pending":
                return <Badge variant="warning">PENDING</Badge>;
            case "expire":
            case "cancel":
            case "deny":
            case "failure":
                return (
                    <Badge variant="destructive">{status.toUpperCase()}</Badge>
                );
            default:
                return (
                    <Badge variant="secondary">{status.toUpperCase()}</Badge>
                );
        }
    };

    return (
        <AppLayout title="Pembayaran">
            <div className="space-y-4">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold flex items-center gap-2">
                        <CreditCard className="h-6 w-6" />
                        Pembayaran
                    </h1>
                    <p className="text-muted-foreground">
                        Daftar pembayaran dari transaksi.
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
                        <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
                            <div>
                                <Label htmlFor="search">Cari</Label>
                                <div className="relative mt-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="Invoice / Order ID"
                                        value={filters.q}
                                        onChange={(e) =>
                                            handleFilterChange(
                                                "q",
                                                e.target.value,
                                            )
                                        }
                                        onKeyDown={(e) =>
                                            e.key === "Enter" && handleSearch()
                                        }
                                        className="pl-9"
                                    />
                                </div>
                            </div>
                            <div>
                                <Label>Status</Label>
                                <Select
                                    value={filters.status}
                                    onValueChange={(value: string) =>
                                        handleFilterChange("status", value)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem
                                                key={s.value}
                                                value={s.value}
                                            >
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
                                    onValueChange={(value: string) =>
                                        handleFilterChange("method", value)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        {methods.map((m) => (
                                            <SelectItem
                                                key={m.value}
                                                value={m.value}
                                            >
                                                {m.value.toUpperCase()}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="provider">Provider</Label>
                                <Input
                                    id="provider"
                                    placeholder="midtrans"
                                    value={filters.provider}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            "provider",
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="from">Dari</Label>
                                <Input
                                    id="from"
                                    type="date"
                                    value={filters.from}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            "from",
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="to">Sampai</Label>
                                <Input
                                    id="to"
                                    type="date"
                                    value={filters.to}
                                    onChange={(e) =>
                                        handleFilterChange("to", e.target.value)
                                    }
                                    className="mt-1"
                                />
                            </div>
                            <div className="flex items-end gap-2">
                                <Button
                                    onClick={handleSearch}
                                    className="flex-1"
                                >
                                    <Search className="h-4 w-4 mr-2" />
                                    Cari
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={handleClearFilters}
                                >
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Payments Table */}
                <Card>
                    <CardContent className="p-0">
                        {loading ? (
                            <div className="flex items-center justify-center h-full py-12">
                                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                <span className="ml-2 text-muted-foreground">
                                    Memuat...
                                </span>
                            </div>
                        ) : payments.length === 0 ? (
                            <div className="text-center py-12 text-muted-foreground">
                                Tidak ada pembayaran ditemukan.
                            </div>
                        ) : (
                            <div className="overflow-hidden">
                                <Table>
                                    <TableHeader className="hidden md:table-header-group">
                                        <TableRow>
                                            <TableHead>Invoice</TableHead>
                                            <TableHead>Kasir</TableHead>
                                            <TableHead>Metode</TableHead>
                                            <TableHead>Provider</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">
                                                Jumlah
                                            </TableHead>
                                            <TableHead>Dibayar</TableHead>
                                            <TableHead className="text-right">
                                                Aksi
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {payments.map((pay) => (
                                            <TableRow
                                                key={pay.id}
                                                className="flex flex-col md:table-row border rounded-lg md:border-0 mb-3 md:mb-0 mx-3 md:mx-0 p-4 md:p-0"
                                            >
                                                <TableCell className="flex flex-col md:table-cell pb-0 md:pb-0 border-0">
                                                    <Link
                                                        href={`/transaksi/${pay.transaction_id}`}
                                                        className="text-lg font-semibold text-primary hover:underline md:text-base md:font-medium"
                                                    >
                                                        {pay.invoice}
                                                    </Link>
                                                    <span className="text-xs text-muted-foreground md:hidden mt-0.5">
                                                        {new Date(
                                                            pay.created_at,
                                                        ).toLocaleString(
                                                            "id-ID",
                                                            {
                                                                day: "2-digit",
                                                                month: "2-digit",
                                                                year: "numeric",
                                                                hour: "2-digit",
                                                                minute: "2-digit",
                                                            },
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="flex flex-col md:table-cell pb-1 md:pb-0 border-0">
                                                    <span className="text-xs text-muted-foreground md:hidden">
                                                        Kasir
                                                    </span>
                                                    <span className="text-sm">
                                                        {pay.cashier}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell border-0">
                                                    <Badge
                                                        variant="outline"
                                                        className="text-xs"
                                                    >
                                                        {pay.method.toUpperCase()}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell border-0 text-sm">
                                                    {pay.provider || "-"}
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell border-0">
                                                    {getStatusBadge(pay.status)}
                                                </TableCell>
                                                <TableCell className="flex items-center justify-between md:table-cell md:text-right pb-1 md:pb-0 border-0">
                                                    <div className="flex items-center gap-2 md:justify-end md:flex-row-reverse">
                                                        <span className="text-base font-semibold md:font-medium">
                                                            {formatMoney(
                                                                pay.amount,
                                                                currency,
                                                            )}
                                                        </span>
                                                        <div className="flex items-center gap-1.5 md:hidden">
                                                            <Badge
                                                                variant="outline"
                                                                className="text-xs"
                                                            >
                                                                {pay.method.toUpperCase()}
                                                            </Badge>
                                                            {getStatusBadge(
                                                                pay.status,
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2 md:hidden">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-9 w-9"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/transaksi/${pay.transaction_id}`}
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-9 w-9"
                                                            onClick={() =>
                                                                window.open(
                                                                    `/transaksi/${pay.transaction_id}/struk`,
                                                                    "_blank",
                                                                )
                                                            }
                                                        >
                                                            <Printer className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell border-0 text-sm">
                                                    {pay.paid_at
                                                        ? new Date(
                                                              pay.paid_at,
                                                          ).toLocaleString(
                                                              "id-ID",
                                                              {
                                                                  day: "2-digit",
                                                                  month: "2-digit",
                                                                  year: "numeric",
                                                                  hour: "2-digit",
                                                                  minute: "2-digit",
                                                              },
                                                          )
                                                        : "-"}
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell md:text-right border-0">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/transaksi/${pay.transaction_id}`}
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            onClick={() =>
                                                                window.open(
                                                                    `/transaksi/${pay.transaction_id}/struk`,
                                                                    "_blank",
                                                                )
                                                            }
                                                        >
                                                            <Printer className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>

                    {/* Pagination */}
                    {!loading && payments.length > 0 && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3 border-t">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
                                Menampilkan{" "}
                                {(pagination.currentPage - 1) *
                                    pagination.perPage +
                                    1}{" "}
                                -{" "}
                                {Math.min(
                                    pagination.currentPage * pagination.perPage,
                                    pagination.total,
                                )}{" "}
                                dari {pagination.total} pembayaran
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        fetchPayments(
                                            pagination.currentPage - 1,
                                        )
                                    }
                                    disabled={pagination.currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    <span className="hidden sm:inline ml-1">
                                        Prev
                                    </span>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        fetchPayments(
                                            pagination.currentPage + 1,
                                        )
                                    }
                                    disabled={
                                        pagination.currentPage >=
                                        pagination.lastPage
                                    }
                                >
                                    <span className="hidden sm:inline mr-1">
                                        Next
                                    </span>
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
