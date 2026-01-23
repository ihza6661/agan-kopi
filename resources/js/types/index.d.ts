import type { User, Notification } from './models';

export interface PageProps {
    [key: string]: unknown;
    csrf_token: string;
    auth: {
        user: User;
    };
    flash: {
        success?: string;
        error?: string;
    };
    notifications: Notification[];
    unreadNotificationsCount: number;
    appStoreName: string;
    appCurrency: string;
}

declare module '@inertiajs/react' {
    interface PageProps extends PageProps {}
}

