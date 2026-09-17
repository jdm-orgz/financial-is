import { Head, Link, router } from '@inertiajs/react';
import { Eye, ArrowUpDown, ArrowDown, ArrowUp } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
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

const statusVariantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    approval: 'default',
    comparing: 'outline',
    compared: 'outline',
    correction: 'destructive',
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
        sort_by?: string;
        sort_direction?: string;
        start_date?: string;
        end_date?: string;
    };
    per_page: number;
}

export default function Index({
    transactions,
    filters = {},
    per_page,
}: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');
    const prevSearch = useRef(search);

    useEffect(() => {
        if (search === prevSearch.current) {
            return;
        }

        prevSearch.current = search;
        const timeoutId = setTimeout(() => {
            router.get(
                window.location.pathname,
                { ...filters, search, per_page, start_date: startDate, end_date: endDate },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeoutId);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const handleDateFilter = () => {
        router.get(
            window.location.pathname,
            { ...filters, search, per_page, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleStatusFilter = (value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, status: value === 'all' ? undefined : value, search, per_page, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSort = (field: string) => {
        const direction = filters.sort_by === field && filters.sort_direction === 'asc' ? 'desc' : 'asc';
        router.get(
            window.location.pathname,
            { ...filters, sort_by: field, sort_direction: direction, per_page, search, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true }
        );
    };

    const renderSortIcon = (field: string) => {
        if (filters.sort_by !== field) return <ArrowUpDown className="ml-2 h-4 w-4" />;
        return filters.sort_direction === 'asc' ? <ArrowUp className="ml-2 h-4 w-4" /> : <ArrowDown className="ml-2 h-4 w-4" />;
    };

    return (
        <>
            <Head title="Transaction Approvals" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Transactions Pending Approval</h1>
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
                                <SelectItem value="approval">Pending Approval</SelectItem>
                                <SelectItem value="comparing">Comparing</SelectItem>
                                <SelectItem value="compared">Compared</SelectItem>
                                <SelectItem value="correction">Correction</SelectItem>
                                <SelectItem value="done">Done</SelectItem>
                            </SelectContent>
                        </Select>
                        <div className="flex items-center gap-2 border rounded-md px-2 py-1">
                            <span className="text-sm text-muted-foreground whitespace-nowrap">From:</span>
                            <Input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                onBlur={handleDateFilter}
                                className="w-36 h-8 border-none focus-visible:ring-0 shadow-none px-1"
                            />
                            <span className="text-sm text-muted-foreground whitespace-nowrap">To:</span>
                            <Input
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                onBlur={handleDateFilter}
                                className="w-36 h-8 border-none focus-visible:ring-0 shadow-none px-1"
                            />
                        </div>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('outlet')}>
                                    <div className="flex items-center">Outlet {renderSortIcon('outlet')}</div>
                                </TableHead>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('spg')}>
                                    <div className="flex items-center">SPG {renderSortIcon('spg')}</div>
                                </TableHead>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('date')}>
                                    <div className="flex items-center">Date {renderSortIcon('date')}</div>
                                </TableHead>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('time')}>
                                    <div className="flex items-center">Time {renderSortIcon('time')}</div>
                                </TableHead>
                                <TableHead className="cursor-pointer" onClick={() => handleSort('status')}>
                                    <div className="flex items-center">Status {renderSortIcon('status')}</div>
                                </TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="h-24 text-center">
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
                                        <TableCell>{tx.date.includes('T') ? tx.date.substring(0, 10) : tx.date}</TableCell>
                                        <TableCell>{tx.date.includes('T') ? tx.date.substring(11, 19) : '-'}</TableCell>
                                        <TableCell>
                                            <Badge variant={statusVariantMap[tx.status] || 'secondary'}>
                                                {statusLabelMap[tx.status] || tx.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="icon" asChild>
                                                <Link href={`/supervisor/transactions/${tx.id}`}>
                                                    <Eye className="h-4 w-4" />
                                                </Link>
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
                                        { ...filters, per_page: value, search, start_date: startDate, end_date: endDate },
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
        { title: 'Supervisor', href: '#' },
        { title: 'Transaction Approvals', href: '/supervisor/transactions' },
    ],
};
