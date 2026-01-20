import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArrowLeft, Package, Loader2, Save } from 'lucide-react';

interface Category {
    id: number;
    name: string;
}

interface ProductFormProps {
    categories: Category[];
    product?: {
        id: number;
        name: string;
        sku: string;
        price: number;
        stock: number;
        category_id: number | null;
        description: string | null;
    };
}

interface FormData {
    name: string;
    sku: string;
    price: string;
    stock: string;
    category_id: string;
    description: string;
}

export default function ProductForm({ categories, product }: ProductFormProps) {
    const isEdit = !!product;

    const { data, setData, post, put, processing, errors, transform } = useForm<FormData>({
        name: product?.name || '',
        sku: product?.sku || '',
        price: product?.price?.toString() || '',
        stock: product?.stock?.toString() || '0',
        category_id: product?.category_id?.toString() || 'none',
        description: product?.description || '',
    });

    // Transform 'none' back to empty string before submission
    transform((formData) => ({
        ...formData,
        category_id: formData.category_id === 'none' ? '' : formData.category_id,
    }));

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        
        if (isEdit) {
            put(`/produk/${product.id}`);
        } else {
            post('/produk');
        }
    };

    return (
        <AppLayout title={isEdit ? 'Edit Produk' : 'Tambah Produk'}>
            <div className="space-y-4">
                {/* Header */}
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/produk">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Package className="h-6 w-6" />
                            {isEdit ? 'Edit Produk' : 'Tambah Produk'}
                        </h1>
                        <p className="text-muted-foreground">
                            {isEdit ? 'Ubah detail produk.' : 'Tambahkan produk baru ke daftar.'}
                        </p>
                    </div>
                </div>

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* SKU */}
                            <div className="space-y-2">
                                <Label htmlFor="sku">SKU *</Label>
                                <Input
                                    id="sku"
                                    placeholder="Contoh: PRD-001"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                    className={errors.sku ? 'border-red-500' : ''}
                                />
                                {errors.sku && <p className="text-sm text-red-500">{errors.sku}</p>}
                            </div>

                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Nama Produk *</Label>
                                <Input
                                    id="name"
                                    placeholder="Nama produk"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className={errors.name ? 'border-red-500' : ''}
                                />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>

                            {/* Category */}
                            <div className="space-y-2">
                                <Label>Kategori</Label>
                                <Select
                                    value={data.category_id}
                                    onValueChange={(value: string) => setData('category_id', value)}
                                >
                                    <SelectTrigger className={errors.category_id ? 'border-red-500' : ''}>
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Tanpa Kategori</SelectItem>
                                        {categories.map((cat) => (
                                            <SelectItem key={cat.id} value={String(cat.id)}>
                                                {cat.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.category_id && <p className="text-sm text-red-500">{errors.category_id}</p>}
                            </div>

                            {/* Price & Stock */}
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="price">Harga *</Label>
                                    <Input
                                        id="price"
                                        type="number"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                        className={errors.price ? 'border-red-500' : ''}
                                    />
                                    {errors.price && <p className="text-sm text-red-500">{errors.price}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="stock">Stok *</Label>
                                    <Input
                                        id="stock"
                                        type="number"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                        value={data.stock}
                                        onChange={(e) => setData('stock', e.target.value)}
                                        className={errors.stock ? 'border-red-500' : ''}
                                    />
                                    {errors.stock && <p className="text-sm text-red-500">{errors.stock}</p>}
                                </div>
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Deskripsi</Label>
                                <Input
                                    id="description"
                                    placeholder="Deskripsi produk (opsional)"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                            </div>

                            {/* Submit */}
                            <div className="flex gap-2 pt-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <>
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            Menyimpan...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="h-4 w-4 mr-2" />
                                            {isEdit ? 'Simpan Perubahan' : 'Tambah Produk'}
                                        </>
                                    )}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/produk">Batal</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
