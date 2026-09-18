import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

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
                router.reload({ preserveScroll: true, preserveState: true, showProgress: false });
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
