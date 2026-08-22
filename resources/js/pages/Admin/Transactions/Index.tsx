import { Head, Link, router } from '@inertiajs/react';
import { Eye, Scale } from 'lucide-react';
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
import type { Transaction } from '@/types/transaction';

interface IndexProps {
    transactions: {
        data: Transaction[];
        links: PaginationLink[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        status?: string;
    };
    per_page: number;
}

export default function Index({
    transactions,
    filters = {},
    per_page,
}: IndexProps) {
    const handleStatusFilter = (value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, status: value === 'all' ? undefined : value, per_page },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Pending Comparisons (Admin)" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Pending Comparisons</h1>
                    <div className="flex items-center gap-4">
                        <Input
                            type="search"
                            placeholder="Search outlet..."
                        />
                        <Select
                            value={filters.status || 'comparing'}
                            onValueChange={handleStatusFilter}
                        >
                            <SelectTrigger className="w-64">
                                <SelectValue placeholder="Filter Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="comparing">Comparison Process</SelectItem>
                                <SelectItem value="compared">Already Compared</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Outlet</TableHead>
                                <TableHead>SPG</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-24 text-center">
                                        No transaction data found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                transactions.data.map((tx) => (
                                    <TableRow key={tx.id}>
                                        <TableCell className="font-medium">
                                            {tx.outlet.name}
                                        </TableCell>
                                        <TableCell>{tx.created_by.name}</TableCell>
                                        <TableCell>{tx.date}</TableCell>
                                        <TableCell>
                                            <Badge variant={tx.status === 'comparing' ? 'outline' : 'default'}>
                                                {tx.status === 'comparing' ? 'Comparison Process' : 'Ready to Review'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="icon" asChild>
                                                {tx.status === 'comparing' ? (
                                                    <Link href={`/admin/transactions/${tx.id}/compare`} title="Input System Data">
                                                        <Scale className="h-4 w-4" />
                                                    </Link>
                                                ) : (
                                                    <Link href={`/admin/transactions/${tx.id}/result`} title="View Results">
                                                        <Eye className="h-4 w-4 text-green-600" />
                                                    </Link>
                                                )}
                                            </Button>
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
                                        { ...filters, per_page: value },
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
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '#' },
        { title: 'Pending Comparisons', href: '/admin/transactions' },
    ],
};
