import { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
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
    Package,
    Search,
    Plus,
    Pencil,
    Trash2,
    Loader2,
    ChevronLeft,
    ChevronRight,
    AlertTriangle,
} from 'lucide-react';
import { formatMoney } from '@/lib/utils';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    category_id: number | null;
    category: Category | null;
    created_at: string;
}

interface ProductsProps {
    currency: string;
}

interface PaginatedResponse {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function ProductsIndex({ currency }: ProductsProps) {
    const [products, setProducts] = useState<Product[]>([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const { toast } = useToast();
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });
    const [deleteModal, setDeleteModal] = useState<{ open: boolean; product: Product | null }>({
        open: false,
        product: null,
    });
    const [deleting, setDeleting] = useState(false);

    const fetchProducts = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.set('page', String(page));
            if (search) params.set('q', search);

            const res = await fetch(`/produk-data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data: PaginatedResponse = await res.json();

            setProducts(data.data || []);
            setPagination({
                currentPage: data.current_page,
                lastPage: data.last_page,
                perPage: data.per_page,
                total: data.total,
            });
        } catch {
            setProducts([]);
        } finally {
            setLoading(false);
        }
    }, [search]);

    useEffect(() => {
        const delay = setTimeout(() => fetchProducts(1), 300);
        return () => clearTimeout(delay);
    }, [fetchProducts]);

    const handleDelete = async () => {
        if (!deleteModal.product) return;

        setDeleting(true);
        try {
            const response = await fetch(`/produk/${deleteModal.product.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                toast({
                    variant: "destructive",
                    title: "Gagal menghapus produk",
                    description: result.message || 'Terjadi kesalahan saat menghapus produk.',
                });
                return;
            }

            toast({
                variant: "success",
                title: "Produk berhasil dihapus",
                description: `Produk "${deleteModal.product.name}" telah dihapus.`,
            });

            setDeleteModal({ open: false, product: null });
            fetchProducts(pagination.currentPage);
        } catch (error) {
            toast({
                variant: "destructive",
                title: "Gagal menghapus produk",
                description: 'Terjadi kesalahan. Silakan coba lagi.',
            });
        } finally {
            setDeleting(false);
        }
    };

    const getStockBadge = (stock: number) => {
        if (stock <= 0) {
            return <Badge variant="destructive">Habis</Badge>;
        } else if (stock <= 5) {
            return <Badge variant="warning">{stock}</Badge>;
        }
        return <Badge variant="success">{stock}</Badge>;
    };

    return (
        <AppLayout title="Produk">
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap gap-4 justify-between items-start">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Package className="h-6 w-6" />
                            Produk
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola daftar produk untuk kasir.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/produk/create">
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Produk
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardContent className="p-4">
                        <div className="relative max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama produk atau SKU..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Products Table */}
                <Card>
                    <CardContent className="p-0">
                        <ScrollArea className="h-[500px]">
                            {loading ? (
                                <div className="flex items-center justify-center h-full py-12">
                                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    <span className="ml-2 text-muted-foreground">Memuat...</span>
                                </div>
                            ) : products.length === 0 ? (
                                <div className="text-center py-12 text-muted-foreground">
                                    Tidak ada produk ditemukan.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>SKU</TableHead>
                                            <TableHead>Nama Produk</TableHead>
                                            <TableHead>Kategori</TableHead>
                                            <TableHead className="text-right">Harga</TableHead>
                                            <TableHead className="text-center">Stok</TableHead>
                                            <TableHead className="text-right">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {products.map((product) => (
                                            <TableRow key={product.id}>
                                                <TableCell className="font-mono text-sm">
                                                    {product.sku}
                                                </TableCell>
                                                <TableCell className="font-medium max-w-xs break-words">
                                                    {product.name}
                                                </TableCell>
                                                <TableCell>
                                                    {product.category?.name || (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(product.price, currency)}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {getStockBadge(product.stock)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            asChild
                                                        >
                                                            <Link href={`/produk/${product.id}/edit`}>
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="h-8 w-8 text-destructive"
                                                            onClick={() => setDeleteModal({ open: true, product })}
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
                    {!loading && products.length > 0 && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 py-3 border-t">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
                                Menampilkan {((pagination.currentPage - 1) * pagination.perPage) + 1} -{' '}
                                {Math.min(pagination.currentPage * pagination.perPage, pagination.total)} dari{' '}
                                {pagination.total} produk
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchProducts(pagination.currentPage - 1)}
                                    disabled={pagination.currentPage <= 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Prev
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fetchProducts(pagination.currentPage + 1)}
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
                            Hapus Produk
                        </DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    {deleteModal.product && (
                        <div className="py-4">
                            <p className="font-medium">{deleteModal.product.name}</p>
                            <p className="text-sm text-muted-foreground">SKU: {deleteModal.product.sku}</p>
                        </div>
                    )}
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteModal({ open: false, product: null })}
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
