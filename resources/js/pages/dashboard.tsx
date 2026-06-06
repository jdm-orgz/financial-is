import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';

interface DashboardProps {
    totalUsers: number;
    totalRoles: number;
    totalOutlets: number;
    totalChairs: number;
}

export default function Dashboard({ totalUsers, totalRoles, totalOutlets, totalChairs }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                        <h2 className="text-lg font-semibold tracking-tight">Master Data</h2>
                        <div className="grid grid-cols-2 gap-4 flex-1">
                            <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Users</span>
                                <span className="text-2xl font-bold">{totalUsers}</span>
                            </div>
                            <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Roles</span>
                                <span className="text-2xl font-bold">{totalRoles}</span>
                            </div>
                            <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Outlets</span>
                                <span className="text-2xl font-bold">{totalOutlets}</span>
                                {/* TODO: waiting for layer implementation */}
                            </div>
                            <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Chairs</span>
                                <span className="text-2xl font-bold">{totalChairs}</span>
                                {/* TODO: waiting for layer implementation */}
                            </div>
                        </div>
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
