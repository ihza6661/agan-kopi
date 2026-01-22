import { useState, FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Eye, EyeOff, LogIn, Loader2 } from 'lucide-react';

interface LoginProps {
    appStoreName: string;
}

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

export default function Login({ appStoreName }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);
    const pageProps = usePage().props as Record<string, unknown>;
    const flash = (pageProps.flash || {}) as { success?: string; error?: string };

    const { data, setData, post, processing, errors } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Masuk" />

            <div className="min-h-screen bg-muted flex items-center justify-center p-4">
                <Card className="w-full max-w-md shadow-lg border-0">
                    <CardHeader className="text-center pb-4">
                        <div className="flex items-center justify-center gap-3 mb-4">
                            <img
                                src="/assets/images/logo.jpg"
                                alt="Logo"
                                className="w-14 h-14 rounded-full"
                            />
                            <div className="text-left">
                                <div className="font-bold text-lg">{appStoreName}</div>
                                <div className="text-sm text-muted-foreground">Sistem Point of Sale</div>
                            </div>
                        </div>
                        <h1 className="text-xl font-semibold">Masuk ke Akun Anda</h1>
                        <p className="text-sm text-muted-foreground">
                            Gunakan email dan kata sandi yang terdaftar.
                        </p>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        {/* Flash messages */}
                        {flash.success && (
                            <div className="rounded-lg bg-green-50 border border-green-200 p-3 text-green-800 text-sm">
                                {flash.success}
                            </div>
                        )}
                        {flash.error && (
                            <div className="rounded-lg bg-red-50 border border-red-200 p-3 text-red-800 text-sm">
                                {flash.error}
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Email */}
                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="nama@contoh.com"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    autoComplete="email"
                                    className={errors.email ? 'border-red-500' : ''}
                                />
                                {errors.email && (
                                    <p className="text-sm text-red-500">{errors.email}</p>
                                )}
                            </div>

                            {/* Password */}
                            <div className="space-y-2">
                                <Label htmlFor="password">Kata Sandi</Label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        placeholder="••••••••"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        autoComplete="current-password"
                                        className={errors.password ? 'border-red-500 pr-10' : 'pr-10'}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                        aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="text-sm text-red-500">{errors.password}</p>
                                )}
                            </div>

                            {/* Remember me */}
                            <div className="flex items-center gap-2">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label htmlFor="remember" className="text-sm font-normal cursor-pointer">
                                    Ingat saya
                                </Label>
                            </div>

                            {/* Submit button */}
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        Memproses...
                                    </>
                                ) : (
                                    <>
                                        <LogIn className="h-4 w-4 mr-2" />
                                        Masuk
                                    </>
                                )}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
