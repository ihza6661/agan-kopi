import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    Settings,
    Store,
    Percent,
    Receipt,
    Loader2,
    Save,
    Eye,
} from 'lucide-react';

interface SettingsProps {
    store_name: string;
    currency: string;
    discount_percent: number;
    tax_percent: number;
    store_address: string;
    store_phone: string;
    receipt_format: string;
}

interface FormData {
    store_name: string;
    currency: string;
    discount_percent: string;
    tax_percent: string;
    store_address: string;
    store_phone: string;
    receipt_format: string;
    store_logo: File | null;
}

export default function SettingsIndex({
    store_name,
    currency,
    discount_percent,
    tax_percent,
    store_address,
    store_phone,
    receipt_format,
}: SettingsProps) {
    const [receiptPreview, setReceiptPreview] = useState<string[]>([]);
    const [loadingPreview, setLoadingPreview] = useState(false);

    const { data, setData, post, processing, errors } = useForm<FormData>({
        store_name: store_name || '',
        currency: currency || 'IDR',
        discount_percent: String(discount_percent || 0),
        tax_percent: String(tax_percent || 0),
        store_address: store_address || '',
        store_phone: store_phone || '',
        receipt_format: receipt_format || 'INV-{YYYY}{MM}{DD}-{SEQ:6}',
        store_logo: null,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/pengaturan', {
            forceFormData: true,
        });
    };

    const handlePreviewReceipt = async () => {
        setLoadingPreview(true);
        try {
            const res = await fetch(`/pengaturan/preview-receipt?format=${encodeURIComponent(data.receipt_format)}&count=5`);
            const json = await res.json();
            setReceiptPreview(json.examples || []);
        } catch {
            setReceiptPreview([]);
        } finally {
            setLoadingPreview(false);
        }
    };

    return (
        <AppLayout title="Pengaturan">
            <div className="space-y-4">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold flex items-center gap-2">
                        <Settings className="h-6 w-6" />
                        Pengaturan
                    </h1>
                    <p className="text-muted-foreground">
                        Konfigurasi toko dan preferensi sistem.
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="grid gap-4 md:grid-cols-1 lg:grid-cols-2">
                        {/* Store Settings */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Store className="h-4 w-4" />
                                    Informasi Toko
                                </CardTitle>
                                <CardDescription>
                                    Data toko yang tampil di struk dan laporan.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="store_name">Nama Toko *</Label>
                                    <Input
                                        id="store_name"
                                        placeholder="Nama toko Anda"
                                        value={data.store_name}
                                        onChange={(e) => setData('store_name', e.target.value)}
                                        className={errors.store_name ? 'border-red-500' : ''}
                                    />
                                    {errors.store_name && <p className="text-sm text-red-500">{errors.store_name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="store_address">Alamat</Label>
                                    <Input
                                        id="store_address"
                                        placeholder="Alamat toko"
                                        value={data.store_address}
                                        onChange={(e) => setData('store_address', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="store_phone">No. Telepon</Label>
                                    <Input
                                        id="store_phone"
                                        placeholder="+62812345678"
                                        value={data.store_phone}
                                        onChange={(e) => setData('store_phone', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="store_logo">Logo Toko (opsional)</Label>
                                    <Input
                                        id="store_logo"
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) => setData('store_logo', e.target.files?.[0] || null)}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Format: JPG, PNG. Maks 2MB.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* POS Settings */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Percent className="h-4 w-4" />
                                    Pengaturan POS
                                </CardTitle>
                                <CardDescription>
                                    Konfigurasi mata uang, diskon, dan pajak.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="currency">Mata Uang</Label>
                                    <Input
                                        id="currency"
                                        placeholder="IDR"
                                        value={data.currency}
                                        onChange={(e) => setData('currency', e.target.value.toUpperCase())}
                                        className={errors.currency ? 'border-red-500' : ''}
                                        maxLength={3}
                                    />
                                    {errors.currency && <p className="text-sm text-red-500">{errors.currency}</p>}
                                </div>

                                <div className="grid gap-4 grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="discount_percent">Diskon Global (%)</Label>
                                        <Input
                                            id="discount_percent"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            value={data.discount_percent}
                                            onChange={(e) => setData('discount_percent', e.target.value)}
                                            className={errors.discount_percent ? 'border-red-500' : ''}
                                        />
                                        {errors.discount_percent && <p className="text-sm text-red-500">{errors.discount_percent}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="tax_percent">Pajak (%)</Label>
                                        <Input
                                            id="tax_percent"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            value={data.tax_percent}
                                            onChange={(e) => setData('tax_percent', e.target.value)}
                                            className={errors.tax_percent ? 'border-red-500' : ''}
                                        />
                                        {errors.tax_percent && <p className="text-sm text-red-500">{errors.tax_percent}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Receipt Format */}
                        <Card className="lg:col-span-2">
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Receipt className="h-4 w-4" />
                                    Format Nomor Struk
                                </CardTitle>
                                <CardDescription>
                                    Gunakan placeholder: {'{YYYY}'} (tahun), {'{MM}'} (bulan), {'{DD}'} (hari), {'{SEQ:N}'} (nomor urut dengan N digit).
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="receipt_format">Format</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="receipt_format"
                                                placeholder="INV-{YYYY}{MM}{DD}-{SEQ:6}"
                                                value={data.receipt_format}
                                                onChange={(e) => setData('receipt_format', e.target.value)}
                                                className={errors.receipt_format ? 'border-red-500' : ''}
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={handlePreviewReceipt}
                                                disabled={loadingPreview}
                                            >
                                                {loadingPreview ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    <Eye className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                        {errors.receipt_format && <p className="text-sm text-red-500">{errors.receipt_format}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Preview</Label>
                                        <div className="bg-muted rounded-md p-3 font-mono text-sm min-h-[100px]">
                                            {receiptPreview.length > 0 ? (
                                                <ul className="space-y-1">
                                                    {receiptPreview.map((ex, i) => (
                                                        <li key={i}>{ex}</li>
                                                    ))}
                                                </ul>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Klik tombol preview untuk melihat contoh.
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Separator className="my-6" />

                    {/* Submit */}
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4 mr-2" />
                                    Simpan Pengaturan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
