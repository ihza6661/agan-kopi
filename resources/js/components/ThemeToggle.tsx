import { Moon, Sun, Monitor } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useThemeStore, type Theme } from '@/stores/themeStore';

export function ThemeToggle() {
    const { theme, setTheme } = useThemeStore();

    const themes: { value: Theme; label: string; icon: typeof Sun }[] = [
        { value: 'light', label: 'Terang', icon: Sun },
        { value: 'dark', label: 'Gelap', icon: Moon },
        { value: 'system', label: 'Sistem', icon: Monitor },
    ];

    const ActiveIcon = themes.find((t) => t.value === theme)?.icon || Monitor;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                    <ActiveIcon className="h-5 w-5" />
                    <span className="sr-only">Toggle tema</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
                {themes.map((t) => (
                    <DropdownMenuItem
                        key={t.value}
                        onClick={() => setTheme(t.value)}
                        className={theme === t.value ? 'bg-accent' : ''}
                    >
                        <t.icon className="mr-2 h-4 w-4" />
                        <span>{t.label}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
