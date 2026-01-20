import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, FolderOpen, Loader2, Save } from 'lucide-react';

interface CategoryFormProps {
    category?: {
        id: number;
        name: string;
        description: string | null;
    };
}

interface FormData {
    name: string;
    description: string;
}

export default function CategoryForm({ category }: CategoryFormProps) {
    const isEdit = !!category;

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: category?.name || '',
        description: category?.description || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        
        if (isEdit) {
            put(`/kategori/${category.id}`);
        } else {
            post('/kategori');
        }
    };

    return (
        <AppLayout title={isEdit ? 'Edit Kategori' : 'Tambah Kategori'}>
            <div className="space-y-4">
                {/* Header */}
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/kategori">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <FolderOpen className="h-6 w-6" />
                            {isEdit ? 'Edit Kategori' : 'Tambah Kategori'}
                        </h1>
                        <p className="text-muted-foreground">
                            {isEdit ? 'Ubah detail kategori.' : 'Tambahkan kategori baru.'}
                        </p>
                    </div>
                </div>

                <Card className="max-w-xl">
                    <CardContent className="pt-6">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Nama Kategori *</Label>
                                <Input
                                    id="name"
                                    placeholder="Nama kategori"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className={errors.name ? 'border-red-500' : ''}
                                />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Deskripsi</Label>
                                <Input
                                    id="description"
                                    placeholder="Deskripsi kategori (opsional)"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
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
                                            {isEdit ? 'Simpan Perubahan' : 'Tambah Kategori'}
                                        </>
                                    )}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/kategori">Batal</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
