import { Head, Link, router } from '@inertiajs/react';
import { Plus, Eye, Trash2 } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { DeleteModal } from '@/components/delete-modal';
import type { PaginationLink } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Transaction, StatusOption } from '@/types/transaction';

const statusVariantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'secondary',
    approval: 'default',
    correction: 'destructive',
    comparing: 'outline',
    compared: 'outline',
    done: 'default',
};

const statusLabelMap: Record<string, string> = {
    draft: 'Draft',
    approval: 'Pending Approval',
    correction: 'Correction',
    comparing: 'Comparing',
    compared: 'Compared',
    done: 'Done',
};

interface IndexProps {
    transactions: {
        data: Transaction[];
        links: PaginationLink[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
    per_page: number;
    statusOptions: StatusOption[];
}

export default function Index({
    transactions,
    filters = {},
    per_page,
    statusOptions,
}: IndexProps) {
    const [transactionToDelete, setTransactionToDelete] = useState<number | null>(null);
    const [search, setSearch] = useState(filters.search || '');
    const prevSearch = useRef(search);

    useEffect(() => {
        if (search === prevSearch.current) {
            return;
        }
        prevSearch.current = search;
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
        if (transactionToDelete !== null) {
            router.delete(`/transactions/${transactionToDelete}`, {
                onFinish: () => setTransactionToDelete(null),
            });
        }
    };

    const handleStatusFilter = (value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, status: value === 'all' ? undefined : value, search, per_page },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Transactions" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Transactions</h1>
                    <div className="flex items-center gap-4">
                        <Input
                            type="search"
                            placeholder="Search outlet..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Select
                            value={filters.status || 'all'}
                            onValueChange={handleStatusFilter}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Filter Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                {statusOptions.map((opt) => (
                                    <SelectItem key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button asChild>
                            <Link href="/transactions/create">
                                <Plus className="mr-2 h-4 w-4" /> New Transaction
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Outlet</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center">
                                        No transaction data found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                transactions.data.map((tx) => (
                                    <TableRow key={tx.id}>
                                        <TableCell className="font-medium">
                                            {tx.outlet.name}
                                        </TableCell>
                                        <TableCell>{tx.date}</TableCell>
                                        <TableCell>
                                            <Badge variant={statusVariantMap[tx.status] || 'secondary'}>
                                                {statusLabelMap[tx.status] || tx.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/transactions/${tx.id}`}>
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {tx.status === 'draft' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => setTransactionToDelete(tx.id)}
                                                        className="text-destructive"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
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
                                        { preserveState: true, preserveScroll: true },
                                    );
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
                            Showing {transactions.from || 0} to {transactions.to || 0} of{' '}
                            {transactions.total || 0} entries
                        </span>
                    </div>
                    <Pagination links={transactions.links} />
                </div>
            </div>

            <DeleteModal
                isOpen={transactionToDelete !== null}
                onOpenChange={(open) => !open && setTransactionToDelete(null)}
                onConfirm={handleDelete}
                title="Delete Transaction"
                description="Are you sure you want to delete this transaction? This action cannot be undone."
            />
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
    ],
};
