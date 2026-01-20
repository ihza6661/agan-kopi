import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatMoney(value: number, currency: string = 'IDR'): string {
    const prefix = currency === 'IDR' ? 'Rp ' : currency + ' ';
    return prefix + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value);
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat('id-ID').format(value);
}

export function parseMoneyToInt(str: string): number {
    const digits = str.replace(/[^0-9]/g, '');
    return Number(digits || 0);
}
