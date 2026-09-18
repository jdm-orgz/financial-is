import { Head, router } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { useState } from 'react';

interface DashboardProps {
    userRole: string;
    
    // Superadmin props
    totalUsers?: number;
    totalRoles?: number;
    totalOutlets?: number;
    totalChairs?: number;
    spgActiveCount?: number;
    spgInactiveCount?: number;
    supervisorActiveCount?: number;
    supervisorInactiveCount?: number;
    revenueYesterday?: number;
    revenueToday?: number;
    topOutletRevenue?: string | null;
    topOutletBrokenChair?: string | null;
    topChairRevenue?: string | null;
    topBrokenChair?: string | null;
    revenueChartData?: { date: string; total_revenue: number }[];
    filters?: { start_date: string; end_date: string };
    storage?: {
        total: number;
        used: number;
        left: number;
        media: number;
        system: number;
    };
    
    // Admin, Supervisor, SPG props
    totalTransaction?: number;
    transactionIncomplete?: number;
    transactionNeedResponse?: number;
    transactionDone?: number;
}

function formatBytes(bytes: number, decimals = 2) {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

export default function Dashboard({
    userRole,
    totalUsers,
    totalRoles,
    totalOutlets,
    totalChairs,
    spgActiveCount,
    spgInactiveCount,
    supervisorActiveCount,
    supervisorInactiveCount,
    revenueYesterday,
    revenueToday,
    topOutletRevenue,
    topOutletBrokenChair,
    topChairRevenue,
    topBrokenChair,
    revenueChartData = [],
    filters,
    storage,
    totalTransaction,
    transactionIncomplete,
    transactionNeedResponse,
    transactionDone,
}: DashboardProps) {
    const [startDate, setStartDate] = useState(filters?.start_date || '');
    const [endDate, setEndDate] = useState(filters?.end_date || '');
    const [isFiltering, setIsFiltering] = useState(false);
    
    const [tooltipPos, setTooltipPos] = useState({ x: 0 });
    const [hoveredType, setHoveredType] = useState<'system' | 'media' | null>(null);

    const handleStorageMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
        const rect = e.currentTarget.getBoundingClientRect();
        setTooltipPos({ x: e.clientX - rect.left });
    };

    const applyFilter = (start: string, end: string) => {
        router.get(dashboard(), { start_date: start, end_date: end }, { 
            preserveState: true, 
            preserveScroll: true,
            replace: true,
            only: ['revenueChartData', 'filters'],
            onStart: () => setIsFiltering(true),
            onFinish: () => setIsFiltering(false)
        });
    };

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                
                {userRole === 'super_admin' && (
                    <>
                        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                            <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Master Data
                                </h2>
                                <div className="grid flex-1 grid-cols-2 gap-4">
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Users
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {totalUsers}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Roles
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {totalRoles}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Outlets
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {totalOutlets}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Chairs
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {totalChairs}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    User Data
                                </h2>
                                <div className="grid flex-1 grid-cols-2 gap-4">
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Active SPG Account
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {spgActiveCount}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Inactive SPG Account
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {spgInactiveCount}
                                        </span>
                                    </div>
                                </div>
                                <div className="grid flex-1 grid-cols-2 gap-4">
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Active Supervisor Account
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {supervisorActiveCount}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Inactive Supervisor Account
                                        </span>
                                        <span className="text-2xl font-bold">
                                            {supervisorInactiveCount}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Storage Data
                                </h2>
                                {storage && (
                                    <div className="flex flex-col gap-2 mt-2">
                                        <div className="flex justify-between items-end mb-1">
                                            <span className="font-medium text-sm">Storage</span>
                                            <span className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                {formatBytes(storage.used)} of {formatBytes(storage.total)} used
                                            </span>
                                        </div>
                                        
                                        <div 
                                            className="relative w-full"
                                            onMouseMove={handleStorageMouseMove}
                                        >
                                            {hoveredType && (
                                                <div 
                                                    className="absolute bottom-full mb-2 -translate-x-1/2 z-50 pointer-events-none drop-shadow-xl"
                                                    style={{ left: tooltipPos.x }}
                                                >
                                                    <div className="bg-[#444444]/95 dark:bg-[#333333]/95 backdrop-blur-md text-white rounded-[1.25rem] py-2 px-4 border border-white/10 text-center flex flex-col items-center">
                                                        <span className="font-semibold text-[15px]">{hoveredType === 'system' ? 'System Data' : 'Media Data'}</span>
                                                        <span className="text-[13px] font-medium text-neutral-300">{formatBytes(hoveredType === 'system' ? storage.system : storage.media)}</span>
                                                    </div>
                                                    <div className="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3.5 h-3.5 bg-[#444444]/95 dark:bg-[#333333]/95 rotate-45 border-r border-b border-white/10 rounded-br-sm"></div>
                                                </div>
                                            )}
                                            <div className="flex h-6 w-full overflow-hidden rounded-md bg-neutral-200 dark:bg-neutral-800">
                                                <div 
                                                    className="bg-red-500 hover:brightness-110 transition-all cursor-default" 
                                                    style={{ width: `${(storage.system / storage.total) * 100}%` }}
                                                    onMouseEnter={() => setHoveredType('system')}
                                                    onMouseLeave={() => setHoveredType(null)}
                                                />
                                                <div 
                                                    className="bg-orange-400 border-l border-neutral-900/10 hover:brightness-110 transition-all cursor-default" 
                                                    style={{ width: `${(storage.media / storage.total) * 100}%` }}
                                                    onMouseEnter={() => setHoveredType('media')}
                                                    onMouseLeave={() => setHoveredType(null)}
                                                />
                                            </div>
                                        </div>
                                        
                                        <div className="flex justify-between items-center mt-2 text-xs">
                                            <div className="flex items-center gap-4">
                                                <div className="flex items-center gap-1.5">
                                                    <div className="w-3 h-3 rounded-sm bg-red-500"></div>
                                                    <span className="font-medium text-neutral-600 dark:text-neutral-300">System</span>
                                                </div>
                                                <div className="flex items-center gap-1.5">
                                                    <div className="w-3 h-3 rounded-sm bg-orange-400"></div>
                                                    <span className="font-medium text-neutral-600 dark:text-neutral-300">Media</span>
                                                </div>
                                            </div>
                                            <span className="font-medium text-neutral-700 dark:text-neutral-200">
                                                {formatBytes(storage.left)} left
                                            </span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                            <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Revenue Data
                                </h2>
                                <div className="grid flex-1 grid-cols-1 gap-4">
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Amount (Yesterday)
                                        </span>
                                        <span className="text-2xl font-bold">
                                            Rp {revenueYesterday?.toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Amount (Today)
                                        </span>
                                        <span className="text-2xl font-bold">
                                            Rp {revenueToday?.toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Outlet Data
                                </h2>
                                <div className="grid flex-1 grid-cols-1 gap-4">
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Highest Revenue (This Month)
                                        </span>
                                        <span className="text-lg font-bold">
                                            {topOutletRevenue ?? '-'}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Most Broken Chair (This Month)
                                        </span>
                                        <span className="text-lg font-bold">
                                            {topOutletBrokenChair ?? '-'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Chair Data
                                </h2>
                                <div className="grid flex-1 grid-cols-1 gap-4">
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Highest Revenue (This Month)
                                        </span>
                                        <span className="text-lg font-bold">
                                            {topChairRevenue ?? '-'}
                                        </span>
                                    </div>
                                    <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                        <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            Most Broken (This Month)
                                        </span>
                                        <span className="text-lg font-bold">
                                            {topBrokenChair ?? '-'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="relative flex flex-col gap-4 min-h-[50vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 md:min-h-min dark:border-sidebar-border">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Revenue Chart
                                </h2>
                                <div className="flex items-center gap-2">
                                    <input 
                                        type="date" 
                                        className="rounded-md border border-neutral-300 px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900 cursor-pointer" 
                                        value={startDate}
                                        onKeyDown={(e) => e.preventDefault()}
                                        onClick={(e) => e.currentTarget.showPicker()}
                                        onChange={(e) => setStartDate(e.target.value)}
                                    />
                                    <span className="text-sm text-neutral-500">to</span>
                                    <input 
                                        type="date" 
                                        className="rounded-md border border-neutral-300 px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-900 cursor-pointer" 
                                        value={endDate}
                                        onKeyDown={(e) => e.preventDefault()}
                                        onClick={(e) => e.currentTarget.showPicker()}
                                        onChange={(e) => setEndDate(e.target.value)}
                                    />
                                    <button 
                                        onClick={() => applyFilter(startDate, endDate)}
                                        className="rounded-md bg-neutral-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                                    >
                                        Filter
                                    </button>
                                </div>
                            </div>
                            <div className="flex-1 min-h-[400px] relative">
                                {isFiltering && (
                                    <div className="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/50 backdrop-blur-[2px] dark:bg-black/50">
                                        <div className="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium shadow-md dark:bg-neutral-800">
                                            <svg className="h-4 w-4 animate-spin text-neutral-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Loading...
                                        </div>
                                    </div>
                                )}
                                {revenueChartData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height="100%">
                                        <LineChart data={revenueChartData} margin={{ top: 20, right: 20, left: 20, bottom: 20 }}>
                                            <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
                                            <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                            <YAxis tick={{ fontSize: 12 }} tickFormatter={(value) => `Rp ${value.toLocaleString('id-ID')}`} width={120} />
                                            <Tooltip 
                                                formatter={(value: number) => [`Rp ${value.toLocaleString('id-ID')}`, 'Revenue']}
                                                labelStyle={{ color: 'black' }}
                                            />
                                            <Line type="monotone" dataKey="total_revenue" stroke="#3b82f6" strokeWidth={3} dot={{ r: 4 }} activeDot={{ r: 6 }} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="flex h-full items-center justify-center text-neutral-500">
                                        No revenue data for the selected range.
                                    </div>
                                )}
                            </div>
                        </div>
                    </>
                )}

                {userRole === 'admin' && (
                    <div className="grid auto-rows-min gap-4 md:grid-cols-2">
                        <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                            <h2 className="text-lg font-semibold tracking-tight">Revenue Data</h2>
                            <div className="grid flex-1 grid-cols-1 gap-4">
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                        Total Amount (Yesterday)
                                    </span>
                                    <span className="text-2xl font-bold">
                                        Rp {revenueYesterday?.toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                        Total Amount (Today)
                                    </span>
                                    <span className="text-2xl font-bold">
                                        Rp {revenueToday?.toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                            <h2 className="text-lg font-semibold tracking-tight">Transaction Data</h2>
                            <div className="grid flex-1 grid-cols-2 gap-4">
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Transaction</span>
                                    <span className="text-2xl font-bold">{totalTransaction}</span>
                                </div>
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Incomplete</span>
                                    <span className="text-2xl font-bold">{transactionIncomplete}</span>
                                </div>
                                <div className={`flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50 ${(transactionNeedResponse ?? 0) > 0 ? 'animate-pulse bg-yellow-100 dark:bg-yellow-900/20' : ''}`}>
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Need Response</span>
                                    <span className={`text-2xl font-bold ${(transactionNeedResponse ?? 0) > 0 ? 'text-yellow-600 dark:text-yellow-400' : ''}`}>{transactionNeedResponse}</span>
                                </div>
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Done</span>
                                    <span className="text-2xl font-bold">{transactionDone}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {(userRole === 'supervisor' || userRole === 'spg') && (
                    <div className="grid auto-rows-min gap-4 md:grid-cols-1">
                        <div className="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                            <h2 className="text-lg font-semibold tracking-tight">Transaction Data</h2>
                            <div className="grid flex-1 grid-cols-2 lg:grid-cols-4 gap-4">
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Transaction</span>
                                    <span className="text-2xl font-bold">{totalTransaction}</span>
                                </div>
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Incomplete</span>
                                    <span className="text-2xl font-bold">{transactionIncomplete}</span>
                                </div>
                                <div className={`flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50 ${(transactionNeedResponse ?? 0) > 0 ? 'animate-pulse bg-yellow-100 dark:bg-yellow-900/20' : ''}`}>
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Need Response</span>
                                    <span className={`text-2xl font-bold ${(transactionNeedResponse ?? 0) > 0 ? 'text-yellow-600 dark:text-yellow-400' : ''}`}>{transactionNeedResponse}</span>
                                </div>
                                <div className="flex flex-col justify-center rounded-lg bg-neutral-100 p-4 dark:bg-neutral-800/50">
                                    <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">Done</span>
                                    <span className="text-2xl font-bold">{transactionDone}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
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
