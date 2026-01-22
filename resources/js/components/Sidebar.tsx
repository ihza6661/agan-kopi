import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    ShoppingCart,
    Package,
    FolderOpen,
    Users,
    Receipt,
    CreditCard,
    BarChart3,
    Settings,
    History,
    LogOut,
    X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import type { PageProps } from '@/types/index.d';

interface NavItem {
    title: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    roles?: ('admin' | 'cashier')[];
}

const navItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/',
        icon: LayoutDashboard,
    },
    {
        title: 'Kasir',
        href: '/kasir',
        icon: ShoppingCart,
        roles: ['admin', 'cashier'],
    },
    {
        title: 'Transaksi',
        href: '/transaksi',
        icon: Receipt,
        roles: ['admin', 'cashier'],
    },
    {
        title: 'Produk',
        href: '/produk',
        icon: Package,
        roles: ['admin'],
    },
    {
        title: 'Kategori',
        href: '/kategori',
        icon: FolderOpen,
        roles: ['admin'],
    },
    {
        title: 'Pengguna',
        href: '/pengguna',
        icon: Users,
        roles: ['admin'],
    },
    {
        title: 'Pembayaran',
        href: '/pembayaran',
        icon: CreditCard,
        roles: ['admin'],
    },
    {
        title: 'Laporan',
        href: '/laporan',
        icon: BarChart3,
        roles: ['admin'],
    },
    {
        title: 'Pengaturan',
        href: '/pengaturan',
        icon: Settings,
        roles: ['admin'],
    },
    {
        title: 'Log Aktivitas',
        href: '/log-aktivitas',
        icon: History,
        roles: ['admin'],
    },
];

export function Sidebar() {
    const { auth, appStoreName } = usePage<PageProps>().props;
    const userRole = auth.user?.role;

    const filteredNavItems = navItems.filter(
        (item) => !item.roles || item.roles.includes(userRole)
    );

    return (
        <aside className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 border-r bg-sidebar-background">
            <div className="flex h-16 items-center gap-2 px-6 border-b">
                <img
                    src="/assets/images/logo.jpg"
                    alt="Logo"
                    className="h-8 w-8"
                />
                <span className="font-semibold text-lg text-sidebar-foreground truncate">
                    {appStoreName}
                </span>
            </div>

            <ScrollArea className="flex-1 py-4">
                <nav className="space-y-1 px-3">
                    {filteredNavItems.map((item) => {
                        const Icon = item.icon;
                        const isActive = window.location.pathname === item.href;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                        : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                                )}
                            >
                                <Icon className="h-4 w-4" />
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>
            </ScrollArea>

            <Separator />

            <div className="p-4">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="flex items-center w-full justify-start gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors"
                >
                    <LogOut className="h-4 w-4" />
                    Keluar
                </Link>
            </div>
        </aside>
    );
}

interface MobileSidebarProps {
    isOpen: boolean;
    onClose: () => void;
}

export function MobileSidebar({ isOpen, onClose }: MobileSidebarProps) {
    const { auth, appStoreName } = usePage<PageProps>().props;
    const userRole = auth.user?.role;

    const filteredNavItems = navItems.filter(
        (item) => !item.roles || item.roles.includes(userRole)
    );

    return (
        <aside 
            className={cn(
                "fixed inset-y-0 left-0 z-50 w-64 bg-sidebar-background border-r transform transition-transform duration-300 ease-in-out lg:hidden",
                isOpen ? "translate-x-0" : "-translate-x-full"
            )}
        >
            <div className="flex h-16 items-center justify-between px-6 border-b">
                <div className="flex items-center gap-2">
                    <img
                        src="/assets/images/logo.jpg"
                        alt="Logo"
                        className="h-8 w-8"
                    />
                    <span className="font-semibold text-lg text-sidebar-foreground truncate">
                        {appStoreName}
                    </span>
                </div>
                <button
                    onClick={onClose}
                    className="p-1 rounded-md text-sidebar-foreground hover:bg-sidebar-accent"
                >
                    <X className="h-5 w-5" />
                </button>
            </div>

            <ScrollArea className="flex-1 py-4 h-[calc(100vh-8rem)]">
                <nav className="space-y-1 px-3">
                    {filteredNavItems.map((item) => {
                        const Icon = item.icon;
                        const isActive = window.location.pathname === item.href;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                        : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                                )}
                            >
                                <Icon className="h-4 w-4" />
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>
            </ScrollArea>

            <Separator />

            <div className="p-4">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="flex items-center w-full justify-start gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors"
                >
                    <LogOut className="h-4 w-4" />
                    Keluar
                </Link>
            </div>
        </aside>
    );
}

