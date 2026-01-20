import { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Users,
    Search,
    Plus,
    Pencil,
    Trash2,
    Loader2,
    ChevronLeft,
    ChevronRight,
    AlertTriangle,
    Shield,
    ShoppingCart,
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

interface PaginatedResponse {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function UsersIndex() {
    const [users, setUsers] = useState<User[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });
    const [deleteModal, setDeleteModal] = useState<{ open: boolean; user: User | null }>({
        open: false,
        user: null,
    });
    const [deleting, setDeleting] = useState(false);

    const fetchUsers = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.set('page', String(page));
            if (search) params.set('q', search);

            const res = await fetch(`/pengguna/data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data: PaginatedResponse = await res.json();

            setUsers(data.data || []);
            setPagination({
                currentPage: data.current_page,
                lastPage: data.last_page,
                perPage: data.per_page,
                total: data.total,
            });
        } catch {
            setUsers([]);
        } finally {
            setLoading(false);
        }
    }, [search]);

    useEffect(() => {
        const delay = setTimeout(() => fetchUsers(1), 300);
        return () => clearTimeout(delay);
    }, [fetchUsers]);

    const handleDelete = async () => {
        if (!deleteModal.user) return;

        setDeleting(true);
        try {
            const res = await fetch(`/pengguna/${deleteModal.user.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            
            if (!res.ok) {
                const data = await res.json();
                throw new Error(data.message || 'Gagal menghapus.');
            }
            
            setDeleteModal({ open: false, user: null });
            fetchUsers(pagination.currentPage);
        } catch (error) {
            alert(error instanceof Error ? error.message : 'Gagal menghapus pengguna.');
        } finally {
            setDeleting(false);
        }
    };

    const getRoleBadge = (role: string) => {
        if (role === 'admin') {
            return (
                <Badge variant="default" className="gap-1">
                    <Shield className="h-3 w-3" />
                    Admin
                </Badge>
            );
        }
        return (
            <Badge variant="secondary" className="gap-1">
                <ShoppingCart className="h-3 w-3" />
                Kasir
            </Badge>
        );
    };

    return (
        <AppLayout title="Pengguna">
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-4 justify-between items-start">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Users className="h-6 w-6" />
                            Pengguna
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola akun pengguna sistem.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/pengguna/create">
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Pengguna
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardContent className="p-4">
                        <div className="relative max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama atau email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardContent className="p-0">
                        <ScrollArea className="h-[500px]">
                            {loading ? (
                                <div className="flex items-center justify-center h-full py-12">
                                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    <span className="ml-2 text-muted-foreground">Memuat...</span>
                                </div>
                            ) : users.length === 0 ? (
                                <div className="text-center py-12 text-muted-foreground">
                                    Tidak ada pengguna ditemukan.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Bergabung</TableHead>
                                            <TableHead className="text-right">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {users.map((user) => (
                                            <TableRow key={user.id}>
                                                <TableCell className="font-medium">
                                                    {user.name}
                                                </TableCell>
                                                <TableCell>
                                                    {user.email}
                                                </TableCell>
                                                <TableCell>
                                                    {getRoleBadge(user.role)}
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(user.created_at).toLocaleDateString('id-ID')}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            asChild
                                                        >
                                                            <Link href={`/pengguna/${user.id}/edit`}>
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8 text-destructive"
                                                            onClick={() => setDeleteModal({ open: true, user })}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
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
                    {!loading && users.length > 0 && (
                        <div className="flex items-center justify-between px-4 py-3 border-t">
                            <div className="text-sm text-muted-foreground">
                                Menampilkan {((pagination.currentPage - 1) * pagination.perPage) + 1} -{' '}
                                {Math.min(pagination.currentPage * pagination.perPage, pagination.total)} dari{' '}
                                {pagination.total} pengguna
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchUsers(pagination.currentPage - 1)}
                                    disabled={pagination.currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Prev
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchUsers(pagination.currentPage + 1)}
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

            {/* Delete Confirmation Modal */}
            <Dialog open={deleteModal.open} onOpenChange={(open) => setDeleteModal({ ...deleteModal, open })}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-destructive" />
                            Hapus Pengguna
                        </DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    {deleteModal.user && (
                        <div className="py-4">
                            <p className="font-medium">{deleteModal.user.name}</p>
                            <p className="text-sm text-muted-foreground">{deleteModal.user.email}</p>
                        </div>
                    )}
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteModal({ open: false, user: null })}
                            disabled={deleting}
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={deleting}
                        >
                            {deleting ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Menghapus...
                                </>
                            ) : (
                                <>
                                    <Trash2 className="h-4 w-4 mr-2" />
                                    Hapus
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
