import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Pencil,
    Trash2,
    Plus,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
} from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { DeleteModal } from '@/components/delete-modal';
import type { PaginationLink } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface Role {
    id: number;
    name: string;
    description: string;
    is_active: '0' | '1';
    created_at: string;
}

interface IndexProps {
    roles: {
        data: Role[];
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
    };
}

export default function Index({ roles, per_page, filters = {} }: IndexProps) {
    const { auth } = usePage<any>().props;
    const [roleToDelete, setRoleToDelete] = useState<number | null>(null);
    const [search, setSearch] = useState(filters.search || '');
    const initialRender = useRef(true);

    useEffect(() => {
        if (initialRender.current) {
            initialRender.current = false;

            return;
        }

        const timeoutId = setTimeout(() => {
            router.get(
                window.location.pathname,
                { ...filters, search, per_page },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search]);

    const handleDelete = () => {
        if (roleToDelete !== null) {
            router.delete(`/roles/${roleToDelete}`, {
                onFinish: () => setRoleToDelete(null),
            });
        }
    };

    const handleToggleStatus = (id: number) => {
        router.patch(
            `/roles/${id}/status`,
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    const handleSort = (field: string) => {
        const direction =
            filters.sort_by === field && filters.sort_direction === 'asc'
                ? 'desc'
                : 'asc';
        router.get(
            window.location.pathname,
            { ...filters, sort_by: field, sort_direction: direction, per_page },
            { preserveState: true, preserveScroll: true },
        );
    };

    const renderSortIcon = (field: string) => {
        if (filters.sort_by !== field) {
return <ArrowUpDown className="ml-2 h-4 w-4" />;
}

        return filters.sort_direction === 'asc' ? (
            <ArrowUp className="ml-2 h-4 w-4" />
        ) : (
            <ArrowDown className="ml-2 h-4 w-4" />
        );
    };

    return (
        <>
            <Head title="Roles" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Roles</h1>
                    <div className="flex items-center gap-4">
                        <Input
                            type="search"
                            placeholder="Search..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Button asChild>
                            <Link href="/roles/create">
                                <Plus className="mr-2 h-4 w-4" /> Add Role
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort('name')}
                                >
                                    <div className="flex items-center">
                                        Name {renderSortIcon('name')}
                                    </div>
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort('description')}
                                >
                                    <div className="flex items-center">
                                        Description{' '}
                                        {renderSortIcon('description')}
                                    </div>
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort('is_active')}
                                >
                                    <div className="flex items-center">
                                        Status {renderSortIcon('is_active')}
                                    </div>
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center"
                                    >
                                        No results.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                roles.data.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="font-medium">
                                            {role.name}
                                        </TableCell>
                                        <TableCell>
                                            {role.description}
                                        </TableCell>
                                        <TableCell>
                                            <span
                                                className={`rounded-full px-2 py-1 text-xs font-medium ${role.is_active === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}
                                            >
                                                {role.is_active === '1'
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-3">
                                                {(
                                                    (auth.user as any).role_name === 'super_admin' || 
                                                    (
                                                        !['super_admin', 'admin'].includes(role.name) && 
                                                        role.name !== (auth.user as any).role_name
                                                    )
                                                ) && (
                                                    <>
                                                        <Switch
                                                            checked={
                                                                role.is_active ===
                                                                '1'
                                                            }
                                                            onCheckedChange={() =>
                                                                handleToggleStatus(
                                                                    role.id,
                                                                )
                                                            }
                                                            aria-label="Toggle status"
                                                            title={
                                                                role.is_active ===
                                                                '1'
                                                                    ? 'Deactivate'
                                                                    : 'Activate'
                                                            }
                                                        />
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/roles/${role.id}/edit`}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                setRoleToDelete(
                                                                    role.id,
                                                                )
                                                            }
                                                            className="text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
                <div className="flex items-center justify-between">
                    <div className="ml-2 flex items-center gap-4 py-4 text-sm text-muted-foreground">
                        <div className="flex items-center space-x-2">
                            <span>Rows per page</span>
                            <Select
                                value={String(per_page)}
                                onValueChange={(value) => {
                                    router.get(
                                        window.location.pathname,
                                        { ...filters, per_page: value, search },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    );
                                }}
                            >
                                <SelectTrigger className="h-8 w-[70px]">
                                    <SelectValue
                                        placeholder={String(per_page)}
                                    />
                                </SelectTrigger>
                                <SelectContent side="top">
                                    {[10, 25, 50, 100].map((pageSize) => (
                                        <SelectItem
                                            key={pageSize}
                                            value={`${pageSize}`}
                                        >
                                            {pageSize}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <span>
                            Showing {roles.from || 0} to {roles.to || 0} of{' '}
                            {roles.total || 0} entries
                        </span>
                    </div>
                    <Pagination links={roles.links} />
                </div>
            </div>

            <DeleteModal
                isOpen={roleToDelete !== null}
                onOpenChange={(open) => !open && setRoleToDelete(null)}
                onConfirm={handleDelete}
                title="Delete Role"
                description="Are you sure you want to delete this role? This action cannot be undone."
            />
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
            title: 'Roles',
            href: '/roles',
        },
    ],
};
