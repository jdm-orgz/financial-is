import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2, Plus, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { DeleteModal } from '@/components/delete-modal';
import type { PaginationLink } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';

interface Chair {
    id: number;
    name: string;
    is_active: '0' | '1';
    created_at: string;
}

interface Outlet {
    id: number;
    name: string;
}

interface IndexProps {
    chairs: {
        data: Chair[];
        links: PaginationLink[];
        from: number;
        to: number;
        total: number;
    };
    outlet: Outlet;
    per_page: number;
    filters: {
        search?: string;
        sort_by?: string;
        sort_direction?: string;
    };
}

export default function Index({ chairs, outlet, per_page, filters = {} }: IndexProps) {
    const [chairToDelete, setChairToDelete] = useState<number | null>(null);
    const [isBulkOpen, setIsBulkOpen] = useState(false);
    const [search, setSearch] = useState(filters.search || '');
    const prevSearch = useRef(search);

    const { data: bulkData, setData: setBulkData, post: postBulk, processing: processingBulk, errors: errorsBulk, reset: resetBulk } = useForm({
        chairs_count: 0,
    });

    useEffect(() => {
        if (search === prevSearch.current) {
            return;
        }
        prevSearch.current = search;

        const timeoutId = setTimeout(() => {
            router.get(window.location.pathname, { ...filters, search, per_page }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search]);

    const handleDelete = () => {
        if (chairToDelete !== null) {
            router.delete(`/outlets/${outlet.id}/chairs/${chairToDelete}`, {
                onFinish: () => setChairToDelete(null),
            });
        }
    };

    const handleToggleStatus = (id: number) => {
        router.patch(`/outlets/${outlet.id}/chairs/${id}/status`, {}, { preserveScroll: true, preserveState: true });
    };

    const handleBulkSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postBulk(`/outlets/${outlet.id}/chairs/bulk`, {
            onSuccess: () => {
                setIsBulkOpen(false);
                resetBulk();
            },
        });
    };

    const handleSort = (field: string) => {
        const direction = filters.sort_by === field && filters.sort_direction === 'asc' ? 'desc' : 'asc';
        router.get(window.location.pathname, { ...filters, sort_by: field, sort_direction: direction, per_page }, { preserveState: true, preserveScroll: true });
    };

    const renderSortIcon = (field: string) => {
        if (filters.sort_by !== field) {
            return <ArrowUpDown className="ml-2 h-4 w-4" />;
        }
        return filters.sort_direction === 'asc' ? <ArrowUp className="ml-2 h-4 w-4" /> : <ArrowDown className="ml-2 h-4 w-4" />;
    };

    return (
        <>
            <Head title={`${outlet.name} - Chairs`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">{outlet.name} - Chairs</h1>
                    <div className="flex items-center gap-4">
                        <Input
                            type="search"
                            placeholder="Search..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Button asChild>
                            <Link href={`/outlets/${outlet.id}/chairs/create`}>
                                <Plus className="mr-2 h-4 w-4" /> Add Chair
                            </Link>
                        </Button>
                        <Button onClick={() => setIsBulkOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" /> Bulk Add Chairs
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/outlets">Back</Link>
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
                                <TableHead className="cursor-pointer" onClick={() => handleSort('is_active')}>
                                    <div className="flex items-center">Status {renderSortIcon('is_active')}</div>
                                </TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {chairs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={3} className="h-24 text-center">
                                        No results.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                chairs.data.map((chair) => (
                                    <TableRow key={chair.id}>
                                        <TableCell className="font-medium">{chair.name}</TableCell>
                                        <TableCell>
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${chair.is_active === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                {chair.is_active === '1' ? 'Active' : 'Inactive'}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-3">
                                                <Switch 
                                                    checked={chair.is_active === '1'}
                                                    onCheckedChange={() => handleToggleStatus(chair.id)}
                                                    aria-label="Toggle status"
                                                    title={chair.is_active === '1' ? 'Deactivate' : 'Activate'}
                                                />
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/outlets/${outlet.id}/chairs/${chair.id}/edit`}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="icon" onClick={() => setChairToDelete(chair.id)} className="text-destructive">
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
                            Showing {chairs.from || 0} to {chairs.to || 0} of {chairs.total || 0} entries
                        </span>
                    </div>
                    <Pagination links={chairs.links} />
                </div>
            </div>

            <DeleteModal 
                isOpen={chairToDelete !== null} 
                onOpenChange={(open) => !open && setChairToDelete(null)} 
                onConfirm={handleDelete}
                title="Delete Chair"
                description="Are you sure you want to delete this chair? This action cannot be undone."
            />

            <Dialog open={isBulkOpen} onOpenChange={setIsBulkOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Bulk Add Chairs</DialogTitle>
                        <DialogDescription>
                            Generate multiple chairs for this outlet.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleBulkSubmit}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="chairs_count">Number of Chairs</Label>
                                <Input
                                    id="chairs_count"
                                    type="number"
                                    min="1"
                                    max="1000"
                                    value={bulkData.chairs_count}
                                    onChange={(e) => setBulkData('chairs_count', parseInt(e.target.value) || 0)}
                                    placeholder="Enter quantity"
                                />
                                {errorsBulk.chairs_count && (
                                    <p className="text-sm text-destructive">{errorsBulk.chairs_count}</p>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsBulkOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processingBulk}>
                                Generate
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

// NOTE: using a functional component property for breadcrumbs requires the router layout logic, we'll keep it simple for now

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
        {
            title: 'Chairs',
            href: '#',
        },
    ],
};
