import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
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
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Transaction } from '@/types/transaction';
import { useState, useEffect } from 'react';

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
        start_date?: string;
        end_date?: string;
        spg_username?: string;
        supervisor_username?: string;
    };
    per_page: number;
    statusOptions: { label: string; value: string }[];
    spgs: { id: string; name: string; username: string }[];
    supervisors: { id: string; name: string; username: string }[];
}

export default function Index({
    transactions,
    filters = {},
    per_page,
    statusOptions,
    spgs,
    supervisors,
}: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [debouncedSearch, setDebouncedSearch] = useState(search);
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');
    const [spgOpen, setSpgOpen] = useState(false);
    const [supervisorOpen, setSupervisorOpen] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(search);
        }, 500);
        return () => clearTimeout(timer);
    }, [search]);

    useEffect(() => {
        if (debouncedSearch !== (filters.search || '')) {
            router.get(
                window.location.pathname,
                { ...filters, search: debouncedSearch, per_page, start_date: startDate, end_date: endDate },
                { preserveState: true, preserveScroll: true },
            );
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedSearch, per_page]);

    const handleDateFilter = () => {
        router.get(
            window.location.pathname,
            { ...filters, search: debouncedSearch, per_page, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleStatusFilter = (value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, status: value === 'all' ? undefined : value, per_page, search: debouncedSearch, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSpgFilter = (value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, spg_username: value === 'all' ? undefined : value, per_page, search: debouncedSearch, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSupervisorFilter = (value: string) => {
        router.get(
            window.location.pathname,
            { ...filters, supervisor_username: value === 'all' ? undefined : value, per_page, search: debouncedSearch, start_date: startDate, end_date: endDate },
            { preserveState: true, preserveScroll: true },
        );
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'draft':
                return 'secondary';
            case 'approval':
                return 'outline';
            case 'correction':
                return 'destructive';
            case 'comparing':
                return 'secondary';
            case 'compared':
                return 'default';
            case 'done':
                return 'default';
            default:
                return 'outline';
        }
    };

    const getStatusLabel = (status: string) => {
        return statusOptions.find(opt => opt.value === status)?.label || status;
    };

    return (
        <>
            <Head title="Transactions (Admin)" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <h1 className="text-2xl font-bold">Transactions</h1>
                    <div className="flex flex-wrap items-center gap-4">
                        <Input
                            type="search"
                            placeholder="Search outlet..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full sm:w-auto"
                        />
                        <Popover open={spgOpen} onOpenChange={setSpgOpen}>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    aria-expanded={spgOpen}
                                    className="w-full sm:w-48 justify-between font-normal bg-background"
                                >
                                    {filters.spg_username
                                        ? spgs.find((spg) => spg.username === filters.spg_username)?.name
                                        : "Filter SPG"}
                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-full sm:w-48 p-0">
                                <Command>
                                    <CommandInput placeholder="Search SPG..." />
                                    <CommandList>
                                        <CommandEmpty>No SPG found.</CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem
                                                value="all"
                                                onSelect={() => {
                                                    handleSpgFilter('all');
                                                    setSpgOpen(false);
                                                }}
                                            >
                                                <Check
                                                    className={cn(
                                                        "mr-2 h-4 w-4",
                                                        (!filters.spg_username || filters.spg_username === 'all') ? "opacity-100" : "opacity-0"
                                                    )}
                                                />
                                                All SPG
                                            </CommandItem>
                                            {spgs.map((spg) => (
                                                <CommandItem
                                                    key={spg.username}
                                                    value={spg.name}
                                                    onSelect={() => {
                                                        handleSpgFilter(spg.username);
                                                        setSpgOpen(false);
                                                    }}
                                                >
                                                    <Check
                                                        className={cn(
                                                            "mr-2 h-4 w-4",
                                                            filters.spg_username === spg.username ? "opacity-100" : "opacity-0"
                                                        )}
                                                    />
                                                    {spg.name}
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <Popover open={supervisorOpen} onOpenChange={setSupervisorOpen}>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    aria-expanded={supervisorOpen}
                                    className="w-full sm:w-48 justify-between font-normal bg-background"
                                >
                                    {filters.supervisor_username
                                        ? supervisors.find((sv) => sv.username === filters.supervisor_username)?.name
                                        : "Filter Supervisor"}
                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-full sm:w-48 p-0">
                                <Command>
                                    <CommandInput placeholder="Search Supervisor..." />
                                    <CommandList>
                                        <CommandEmpty>No Supervisor found.</CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem
                                                value="all"
                                                onSelect={() => {
                                                    handleSupervisorFilter('all');
                                                    setSupervisorOpen(false);
                                                }}
                                            >
                                                <Check
                                                    className={cn(
                                                        "mr-2 h-4 w-4",
                                                        (!filters.supervisor_username || filters.supervisor_username === 'all') ? "opacity-100" : "opacity-0"
                                                    )}
                                                />
                                                All Supervisor
                                            </CommandItem>
                                            {supervisors.map((sv) => (
                                                <CommandItem
                                                    key={sv.username}
                                                    value={sv.name}
                                                    onSelect={() => {
                                                        handleSupervisorFilter(sv.username);
                                                        setSupervisorOpen(false);
                                                    }}
                                                >
                                                    <Check
                                                        className={cn(
                                                            "mr-2 h-4 w-4",
                                                            filters.supervisor_username === sv.username ? "opacity-100" : "opacity-0"
                                                        )}
                                                    />
                                                    {sv.name}
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <Select
                            value={filters.status || 'all'}
                            onValueChange={handleStatusFilter}
                        >
                            <SelectTrigger className="w-full sm:w-48">
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
                        <div className="flex flex-wrap items-center gap-2 border rounded-md px-2 py-1 w-full sm:w-auto">
                            <span className="text-sm text-muted-foreground whitespace-nowrap">From:</span>
                            <Input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                onClick={(e) => 'showPicker' in e.currentTarget && (e.currentTarget as HTMLInputElement).showPicker()}
                                onKeyDown={(e) => e.preventDefault()}
                                className="w-full sm:w-36 h-8 border-none focus-visible:ring-0 shadow-none px-1 cursor-pointer"
                            />
                            <span className="text-sm text-muted-foreground whitespace-nowrap">To:</span>
                            <Input
                                type="date"
                                value={endDate}
                                min={startDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                onClick={(e) => 'showPicker' in e.currentTarget && (e.currentTarget as HTMLInputElement).showPicker()}
                                onKeyDown={(e) => e.preventDefault()}
                                className="w-full sm:w-36 h-8 border-none focus-visible:ring-0 shadow-none px-1 cursor-pointer"
                            />
                            <Button size="sm" variant="secondary" onClick={handleDateFilter} className="h-7 px-2 w-full sm:w-auto">Apply</Button>
                        </div>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Outlet</TableHead>
                                <TableHead>SPG</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Time</TableHead>
                                <TableHead>Supervisor</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-24 text-center">
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
                                        <TableCell>{tx.created_at ? new Date(tx.created_at).toTimeString().substring(0, 8) : '-'}</TableCell>
                                        <TableCell>{tx.supervisor_actioned_by?.name || '-'}</TableCell>
                                        <TableCell>
                                            <Badge variant={getStatusBadgeVariant(tx.status)}>
                                                {getStatusLabel(tx.status)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {tx.status === 'comparing' || tx.status === 'compared' || tx.status === 'done' ? (
                                                <Button variant="ghost" size="icon" asChild>
                                                    {tx.status === 'comparing' ? (
                                                        <Link href={`/admin/transactions/${tx.id}/compare`} title="Input System Data">
                                                            <Pencil className="h-4 w-4 text-orange-600" />
                                                        </Link>
                                                    ) : (
                                                        <Link href={`/admin/transactions/${tx.id}/result`} title="View Results">
                                                            <Eye className="h-4 w-4 text-white" />
                                                        </Link>
                                                    )}
                                                </Button>
                                            ) : (
                                                <span className="text-sm text-muted-foreground font-medium">Incomplete</span>
                                            )}
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
                                        { ...filters, per_page: value, search: debouncedSearch, start_date: startDate, end_date: endDate },
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
        { title: 'Transactions', href: '/admin/transactions' },
    ],
};
