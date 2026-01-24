import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    ClipboardList,
    Loader2,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';

interface ActivityLog {
    id: number;
    user_name: string;
    activity: string;
    description: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

interface PaginatedResponse {
    data: ActivityLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function ActivityLogsIndex() {
    const [logs, setLogs] = useState<ActivityLog[]>([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });

    const fetchLogs = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.set('page', String(page));

            const res = await fetch(`/log-aktivitas-data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data: PaginatedResponse = await res.json();

            setLogs(data.data || []);
            setPagination({
                currentPage: data.current_page,
                lastPage: data.last_page,
                perPage: data.per_page,
                total: data.total,
            });
        } catch {
            setLogs([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchLogs(1);
    }, [fetchLogs]);

    const truncateText = (text: string | null, maxLength = 60) => {
        if (!text) return '-';
        return text.length > maxLength ? text.slice(0, maxLength) + '...' : text;
    };

    return (
        <AppLayout title="Log Aktivitas">
            <div className="space-y-4">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold flex items-center gap-2">
                        <ClipboardList className="h-6 w-6" />
                        Log Aktivitas
                    </h1>
                    <p className="text-muted-foreground">
                        Riwayat aktivitas pengguna di sistem.
                    </p>
                </div>

                {/* Logs Table */}
                <Card>
                    <CardContent className="p-0">
                        {loading ? (
                            <div className="flex items-center justify-center h-full py-12">
                                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                <span className="ml-2 text-muted-foreground">Memuat...</span>
                            </div>
                        ) : logs.length === 0 ? (
                            <div className="text-center py-12 text-muted-foreground">
                                Tidak ada log aktivitas.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader className="hidden sm:table-header-group">
                                        <TableRow>
                                            <TableHead>Waktu</TableHead>
                                            <TableHead>Pengguna</TableHead>
                                            <TableHead>Aktivitas</TableHead>
                                            <TableHead>IP</TableHead>
                                            <TableHead>User Agent</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {logs.map((log) => (
                                            <TableRow 
                                                key={log.id}
                                                className="flex flex-col sm:table-row border rounded-lg sm:border-0 mb-3 sm:mb-0 mx-3 sm:mx-0 p-4 sm:p-0"
                                            >
                                                <TableCell className="flex flex-col sm:table-cell pb-1 sm:pb-0 border-0">
                                                    <span className="text-xs text-muted-foreground sm:hidden mb-1">Waktu</span>
                                                    <span className="text-sm whitespace-nowrap">
                                                        {new Date(log.created_at).toLocaleString('id-ID', {
                                                            day: '2-digit',
                                                            month: '2-digit',
                                                            year: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="flex flex-col sm:table-cell pb-1 sm:pb-0 border-0">
                                                    <span className="text-xs text-muted-foreground sm:hidden mb-1">Pengguna</span>
                                                    <span className="text-sm">{log.user_name || '-'}</span>
                                                </TableCell>
                                                <TableCell className="flex flex-col sm:table-cell pb-1 sm:pb-0 border-0" title={log.description || ''}>
                                                    <span className="text-xs text-muted-foreground sm:hidden mb-1">Aktivitas</span>
                                                    <span className="text-sm break-words">{log.activity}</span>
                                                </TableCell>
                                                <TableCell className="flex flex-col sm:table-cell pb-1 sm:pb-0 border-0">
                                                    <span className="text-xs text-muted-foreground sm:hidden mb-1">IP Address</span>
                                                    <span className="text-sm whitespace-nowrap">{log.ip_address || '-'}</span>
                                                </TableCell>
                                                <TableCell className="flex flex-col sm:table-cell pb-0 sm:pb-0 border-0" title={log.user_agent || ''}>
                                                    <span className="text-xs text-muted-foreground sm:hidden mb-1">User Agent</span>
                                                    <span className="text-xs break-words">{truncateText(log.user_agent, 40)}</span>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>

                    {/* Pagination */}
                    {!loading && logs.length > 0 && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3 border-t">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
                                Menampilkan {((pagination.currentPage - 1) * pagination.perPage) + 1} -{' '}
                                {Math.min(pagination.currentPage * pagination.perPage, pagination.total)} dari{' '}
                                {pagination.total} log
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchLogs(pagination.currentPage - 1)}
                                    disabled={pagination.currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    <span className="hidden sm:inline ml-1">Prev</span>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchLogs(pagination.currentPage + 1)}
                                    disabled={pagination.currentPage >= pagination.lastPage}
                                >
                                    <span className="hidden sm:inline mr-1">Next</span>
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
