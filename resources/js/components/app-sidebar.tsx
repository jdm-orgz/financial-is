import { Link, usePage } from '@inertiajs/react';
import { BookOpen, FolderGit2, LayoutGrid, Database } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage<any>().props;
    const permissions = auth?.permissions || { master: false, configuration: false, transaction: false };

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (permissions.master) {
        mainNavItems.push({
            title: 'Master Data',
            href: '#',
            icon: Database,
            items: [
                { title: 'Roles', href: '/roles' },
                { title: 'Users', href: '/users' },
                { title: 'Outlets', href: '/outlets' },
            ],
        });
    }

    if (permissions.configuration) {
        mainNavItems.push({
            title: 'Configuration',
            href: '#',
            icon: Database,
            items: [
                { title: 'User\'s Outlets', href: '/linked-outlet-users' },
            ],
        });
    }

    if (permissions.transaction) {
        mainNavItems.push({
            title: 'Transaction',
            href: '#',
            icon: Database,
            items: [],
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
