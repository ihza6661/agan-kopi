import { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    FolderOpen,
    Search,
    Plus,
    Pencil,
    Trash2,
    Loader2,
    ChevronLeft,
    ChevronRight,
    AlertTriangle,
} from 'lucide-react';
import { toast } from 'sonner';
import { fetchWithCsrf } from '@/lib/csrf';

interface Category {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    products_count?: number;
}

interface PaginatedResponse {
    data: Category[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function CategoriesIndex() {
    const [categories, setCategories] = useState<Category[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });
    const [deleteModal, setDeleteModal] = useState<{ open: boolean; category: Category | null }>({
        open: false,
        category: null,
    });
    const [deleting, setDeleting] = useState(false);

    const fetchCategories = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.set('page', String(page));
            if (search) params.set('q', search);

            const res = await fetch(`/kategori-data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data: PaginatedResponse = await res.json();

            setCategories(data.data || []);
            setPagination({
                currentPage: data.current_page,
                lastPage: data.last_page,
                perPage: data.per_page,
                total: data.total,
            });
        } catch {
            setCategories([]);
        } finally {
            setLoading(false);
        }
    }, [search]);

    useEffect(() => {
        const delay = setTimeout(() => fetchCategories(1), 300);
        return () => clearTimeout(delay);
    }, [fetchCategories]);

    const handleDelete = async () => {
        if (!deleteModal.category) return;

        setDeleting(true);
        try {
            const res = await fetchWithCsrf(`/kategori/${deleteModal.category.id}`, {
                method: 'DELETE',
            });

            if (!res.ok) {
                const data = await res.json();
                throw new Error(data.message || 'Gagal menghapus kategori');
            }

            toast.success('Kategori berhasil dihapus');
            setDeleteModal({ open: false, category: null });
            fetchCategories(pagination.currentPage);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Gagal menghapus kategori');
        } finally {
            setDeleting(false);
        }
    };

    return (
        <AppLayout title="Kategori">
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-4 justify-between items-start">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <FolderOpen className="h-6 w-6" />
                            Kategori
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola kategori produk.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/kategori/create">
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Kategori
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardContent className="p-4">
                        <div className="relative max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama kategori..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Categories Table */}
                <Card>
                    <CardContent className="p-0">
                        <ScrollArea className="h-[500px]">
                            {loading ? (
                                <div className="flex items-center justify-center h-full py-12">
                                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    <span className="ml-2 text-muted-foreground">Memuat...</span>
                                </div>
                            ) : categories.length === 0 ? (
                                <div className="text-center py-12 text-muted-foreground">
                                    Tidak ada kategori ditemukan.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama</TableHead>
                                            <TableHead>Deskripsi</TableHead>
                                            <TableHead>Dibuat</TableHead>
                                            <TableHead className="text-right">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {categories.map((cat) => (
                                            <TableRow key={cat.id}>
                                                <TableCell className="font-medium">
                                                    {cat.name}
                                                </TableCell>
                                                <TableCell>
                                                    {cat.description || (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(cat.created_at).toLocaleDateString('id-ID')}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            asChild
                                                        >
                                                            <Link href={`/kategori/${cat.id}/edit`}>
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8 text-destructive"
                                                            onClick={() => setDeleteModal({ open: true, category: cat })}
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
                    {!loading && categories.length > 0 && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3 border-t">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
                                Menampilkan {((pagination.currentPage - 1) * pagination.perPage) + 1} -{' '}
                                {Math.min(pagination.currentPage * pagination.perPage, pagination.total)} dari{' '}
                                {pagination.total} kategori
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchCategories(pagination.currentPage - 1)}
                                    disabled={pagination.currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Prev
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchCategories(pagination.currentPage + 1)}
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
                            Hapus Kategori
                        </DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus kategori ini? Kategori yang masih memiliki produk tidak dapat dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    {deleteModal.category && (
                        <div className="py-4">
                            <p className="font-medium">{deleteModal.category.name}</p>
                            {deleteModal.category.description && (
                                <p className="text-sm text-muted-foreground">{deleteModal.category.description}</p>
                            )}
                        </div>
                    )}
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteModal({ open: false, category: null })}
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
