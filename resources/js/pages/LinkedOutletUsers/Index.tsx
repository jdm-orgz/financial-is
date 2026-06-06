import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import type { PaginationLink } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { MultiSelectModal } from '@/components/ui/multi-select-modal';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface UserWithOutlets {
    id: string;
    username: string;
    name: string;
    email: string;
    role?: {
        id: string;
        description: string;
    };
    outlets: {
        id: string;
        name: string;
        pivot: {
            is_active: string;
        };
    }[];
}

interface IndexProps {
    linkedOutletUsers: {
        data: UserWithOutlets[];
        links: PaginationLink[];
        from: number;
        to: number;
        total: number;
    };
    per_page: number;
    filters: {
        search?: string;
        sort_by?: string;
        sort_direction?: string;
        roles?: string[];
    };
    roles: {
        id: string;
        name: string;
        description: string | null;
    }[];
}

export default function Index({ linkedOutletUsers, per_page, filters = {}, roles = [] }: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [roleNames, setRoleNames] = useState<string[]>(filters.roles || []);
    const initialRender = useRef(true);

    useEffect(() => {
        if (initialRender.current) {
            initialRender.current = false;

            return;
        }

        const timeoutId = setTimeout(() => {
            router.get(window.location.pathname, { ...filters, search, roles: roleNames, per_page }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search, roleNames]);

    const handleSort = (field: string) => {
        const direction = filters.sort_by === field && filters.sort_direction === 'asc' ? 'desc' : 'asc';
        router.get(window.location.pathname, { ...filters, sort_by: field, sort_direction: direction, per_page, search, roles: roleNames }, { preserveState: true, preserveScroll: true });
    };

    const renderSortIcon = (field: string) => {
        if (filters.sort_by !== field) {
return <ArrowUpDown className="ml-2 h-4 w-4" />;
}

        return filters.sort_direction === 'asc' ? <ArrowUp className="ml-2 h-4 w-4" /> : <ArrowDown className="ml-2 h-4 w-4" />;
    };

    return (
        <>
            <Head title="Linked Outlet Users" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Linked Outlet Users</h1>
                    <div className="flex items-center gap-4">
                        <div className="w-64">
                            <MultiSelectModal
                                options={roles.map(r => ({ id: r.name, name: r.description || r.name }))}
                                selected={roleNames}
                                onChange={(selected) => setRoleNames(selected)}
                                placeholder="Filter by Role"
                                searchPlaceholder="Search roles..."
                            />
                        </div>
                        <Input
                            type="search"
                            placeholder="Search user or outlet..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Button asChild>
                            <Link href="/linked-outlet-users/create">
                                <Plus className="mr-2 h-4 w-4" /> Add Link
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('user.name')}>
                                    <div className="flex items-center">User {renderSortIcon('user.name')}</div>
                                </TableHead>
                                <TableHead>
                                    <div className="flex items-center">Role</div>
                                </TableHead>
                                <TableHead>
                                    <div className="flex items-center">Outlet</div>
                                </TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {linkedOutletUsers.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center">
                                        No results.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                linkedOutletUsers.data.map((user) => (
                                    <TableRow 
                                        key={user.id}
                                        className="cursor-pointer hover:bg-muted/50"
                                        onClick={() => router.get(`/linked-outlet-users/${user.id}/edit`)}
                                    >
                                        <TableCell className="font-medium">
                                            {user.name}
                                            <div className="text-xs text-muted-foreground">{user.username} - {user.email}</div>
                                        </TableCell>
                                        <TableCell>
                                            {user.role?.description || '-'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.outlets && user.outlets.length > 0 ? (
                                                    user.outlets.map((outlet) => (
                                                        <span key={outlet.id} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-secondary text-secondary-foreground">
                                                            {outlet.name}
                                                        </span>
                                                    ))
                                                ) : (
                                                    <span className="text-muted-foreground text-xs italic">-</span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-3">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/linked-outlet-users/${user.id}/edit`}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
                <div className="flex items-center justify-between">
                    <div className="flex items-center text-sm text-muted-foreground ml-2 py-4 gap-4">
                        <div className="flex items-center space-x-2">
                            <span>Rows per page</span>
                            <Select
                                value={String(per_page)}
                                onValueChange={(value) => {
                                    router.get(window.location.pathname, { ...filters, per_page: value, search, roles: roleNames }, { preserveState: true, preserveScroll: true });
                                }}
                            >
                                <SelectTrigger className="h-8 w-[70px]">
                                    <SelectValue placeholder={String(per_page)} />
                                </SelectTrigger>
                                <SelectContent side="top">
                                    {[10, 25, 50, 100].map((pageSize) => (
                                        <SelectItem key={pageSize} value={`${pageSize}`}>
                                            {pageSize}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <span>
                            Showing {linkedOutletUsers.from || 0} to {linkedOutletUsers.to || 0} of {linkedOutletUsers.total || 0} entries
                        </span>
                    </div>
                    <Pagination links={linkedOutletUsers.links} />
                </div>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Master Data',
            href: '#',
        },
        {
            title: 'Linked Outlet Users',
            href: '/linked-outlet-users',
        },
    ],
};
