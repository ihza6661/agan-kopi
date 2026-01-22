import { useState, useEffect, type PropsWithChildren } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Sidebar, MobileSidebar } from '@/components/Sidebar';
import { Header } from '@/components/Header';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Toaster } from '@/components/ui/sonner';
import type { PageProps } from '@/types/index.d';
import { useThemeStore } from '@/stores/themeStore';

interface AppLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const { flash } = usePage<PageProps>().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const { theme } = useThemeStore();

    useEffect(() => {
        const root = window.document.documentElement;
        
        const applyTheme = (t: string) => {
            root.classList.remove('light', 'dark');
            if (t === 'system') {
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                root.classList.add(systemTheme);
            } else {
                root.classList.add(t);
            }
        };

        applyTheme(theme);

        if (theme === 'system') {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const handleChange = () => applyTheme('system');
            mediaQuery.addEventListener('change', handleChange);
            return () => mediaQuery.removeEventListener('change', handleChange);
        }
    }, [theme]);

    return (
        <TooltipProvider>
            <Head title={title} />
            
            <div className="min-h-screen bg-background">
                {/* Desktop Sidebar */}
                <Sidebar />

                {/* Mobile sidebar overlay */}
                {mobileMenuOpen && (
                    <div
                        className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                        onClick={() => setMobileMenuOpen(false)}
                    />
                )}

                {/* Mobile Sidebar */}
                <MobileSidebar 
                    isOpen={mobileMenuOpen} 
                    onClose={() => setMobileMenuOpen(false)} 
                />

                {/* Main content */}
                <div className="lg:pl-64">
                    <Header onMenuClick={() => setMobileMenuOpen(!mobileMenuOpen)} />

                    <main className="p-4 lg:p-6">
                        {/* Flash messages */}
                        {flash.success && (
                            <div className="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                                {flash.success}
                            </div>
                        )}
                        {flash.error && (
                            <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                                {flash.error}
                            </div>
                        )}

                        {children}
                    </main>
                </div>
            </div>
            <Toaster />
        </TooltipProvider>
    );
}

