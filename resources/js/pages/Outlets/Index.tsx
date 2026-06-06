import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Pencil, Trash2, Plus, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { Pagination, PaginationLink } from '@/components/pagination';
import { DeleteModal } from '@/components/delete-modal';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';

interface Outlet {
    id: number;
    name: string;
    address: string;
    is_active: '0' | '1';
    created_at: string;
}

interface IndexProps {
    outlets: {
        data: Outlet[];
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

export default function Index({ outlets, per_page, filters = {} }: IndexProps) {
    const [outletToDelete, setOutletToDelete] = useState<number | null>(null);
    const [search, setSearch] = useState(filters.search || '');
    const initialRender = useRef(true);

    useEffect(() => {
        if (initialRender.current) {
            initialRender.current = false;
            return;
        }

        const timeoutId = setTimeout(() => {
            router.get(window.location.pathname, { ...filters, search, per_page }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search]);

    const handleDelete = () => {
        if (outletToDelete !== null) {
            router.delete(`/outlets/${outletToDelete}`, {
                onFinish: () => setOutletToDelete(null),
            });
        }
    };

    const handleToggleStatus = (id: number) => {
        router.patch(`/outlets/${id}/status`, {}, { preserveScroll: true, preserveState: true });
    };

    const handleSort = (field: string) => {
        const direction = filters.sort_by === field && filters.sort_direction === 'asc' ? 'desc' : 'asc';
        router.get(window.location.pathname, { ...filters, sort_by: field, sort_direction: direction, per_page }, { preserveState: true, preserveScroll: true });
    };

    const renderSortIcon = (field: string) => {
        if (filters.sort_by !== field) return <ArrowUpDown className="ml-2 h-4 w-4" />;
        return filters.sort_direction === 'asc' ? <ArrowUp className="ml-2 h-4 w-4" /> : <ArrowDown className="ml-2 h-4 w-4" />;
    };

    return (
        <>
            <Head title="Outlets" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Outlets</h1>
                    <div className="flex items-center gap-4">
                        <Input
                            type="search"
                            placeholder="Search..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Button asChild>
                            <Link href="/outlets/create">
                                <Plus className="mr-2 h-4 w-4" /> Add Outlet
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('name')}>
                                    <div className="flex items-center">Name {renderSortIcon('name')}</div>
                                </TableHead>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('address')}>
                                    <div className="flex items-center">Address {renderSortIcon('address')}</div>
                                </TableHead>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('is_active')}>
                                    <div className="flex items-center">Status {renderSortIcon('is_active')}</div>
                                </TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {outlets.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center">
                                        No results.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                outlets.data.map((outlet) => (
                                    <TableRow key={outlet.id}>
                                        <TableCell className="font-medium">{outlet.name}</TableCell>
                                        <TableCell>{outlet.address}</TableCell>
                                        <TableCell>
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${outlet.is_active === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                {outlet.is_active === '1' ? 'Active' : 'Inactive'}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-3">
                                                <Switch 
                                                    checked={outlet.is_active === '1'}
                                                    onCheckedChange={() => handleToggleStatus(outlet.id)}
                                                    aria-label="Toggle status"
                                                    title={outlet.is_active === '1' ? 'Deactivate' : 'Activate'}
                                                />
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/outlets/${outlet.id}/edit`}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="icon" onClick={() => setOutletToDelete(outlet.id)} className="text-destructive">
                                                    <Trash2 className="h-4 w-4" />
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
                                    router.get(window.location.pathname, { ...filters, per_page: value, search }, { preserveState: true, preserveScroll: true });
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
                            Showing {outlets.from || 0} to {outlets.to || 0} of {outlets.total || 0} entries
                        </span>
                    </div>
                    <Pagination links={outlets.links} />
                </div>
            </div>

            <DeleteModal 
                isOpen={outletToDelete !== null} 
                onOpenChange={(open) => !open && setOutletToDelete(null)} 
                onConfirm={handleDelete}
                title="Delete Outlet"
                description="Are you sure you want to delete this outlet? This action cannot be undone."
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
            title: 'Outlets',
            href: '/outlets',
        },
    ],
};
