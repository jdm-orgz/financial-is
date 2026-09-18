import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    useEffect(() => {
        if (window.Echo) {
            const channel = window.Echo.private('app-updates');
            
            channel.listen('DataUpdated', () => {
                router.reload();
            });
            
            return () => {
                window.Echo.leave('app-updates');
            };
        }
    }, []);

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {children}
        </AppLayoutTemplate>
    );
}
