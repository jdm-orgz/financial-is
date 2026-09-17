import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ConfirmModal } from '@/components/confirm-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Transaction } from '@/types/transaction';

interface Chair {
    id: string;
    name: string;
}

interface CompareProps {
    transaction: Transaction;
    chairs: Chair[];
}

export default function Compare({ transaction, chairs }: CompareProps) {
    const { 
        data, 
        setData, 
        post, 
        processing,
    } = useForm({
        system_incomes: chairs.map(chair => {
            const existing = transaction.system_incomes?.find(si => si.chair?.name === chair.name);
            return {
                chair_id: chair.id,
                amount: existing ? String(Number(existing.amount)) : '0',
            };
        }),
    });

    useEffect(() => {
        setData('system_incomes', chairs.map(chair => {
            const existing = transaction.system_incomes?.find(si => si.chair?.name === chair.name);
            return {
                chair_id: chair.id,
                amount: existing ? String(Number(existing.amount)) : '0',
            };
        }));
    }, [transaction.system_incomes, chairs]);

    const [confirmState, setConfirmState] = useState<{
        isOpen: boolean;
        title: string;
        description: string;
        confirmText: string;
        confirmVariant: 'default' | 'destructive' | 'secondary' | 'outline';
        action: () => void;
    }>({
        isOpen: false,
        title: '',
        description: '',
        confirmText: '',
        confirmVariant: 'default',
        action: () => {},
    });

    const openConfirm = (title: string, description: string, confirmText: string, confirmVariant: any, action: () => void) => {
        setConfirmState({ isOpen: true, title, description, confirmText, confirmVariant, action });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        openConfirm(
            'Save & Compare',
            'Are you sure you want to save the system income data and proceed to comparison?',
            'Proceed',
            'default',
            () => {
                post(`/admin/transactions/${transaction.id}/system-incomes`);
            }
        );
    };

    return (
        <>
            <Head title="System Income Input" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Input System Data</h1>
                        <div className="text-sm text-muted-foreground mt-1 flex flex-col gap-0.5">
                            <p>Outlet: {transaction.outlet.name}</p>
                            <p>Date: {transaction.date.includes('T') ? transaction.date.substring(0, 10) : transaction.date}</p>
                            {transaction.date.includes('T') && (
                                <p>Time: {transaction.date.substring(11, 19)}</p>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/admin/transactions">
                                <ChevronLeft className="mr-2 h-4 w-4" /> Back
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>System Income Data</CardTitle>
                            <CardDescription>
                                Input the gross income from the system for each chair. 
                                Note: Replacement expenses will be automatically calculated.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {chairs.map((chair, index) => (
                                    <div key={chair.id} className="grid grid-cols-3 items-center gap-4 border-b pb-4 last:border-0">
                                        <Label className="col-span-1 text-base">{chair.name}</Label>
                                        <div className="col-span-2">
                                            <Input 
                                                type="number" 
                                                min="0"
                                                value={data.system_incomes[index].amount}
                                                onChange={(e) => {
                                                    const newIncomes = [...data.system_incomes];
                                                    let val = e.target.value;
                                                    
                                                    if (val !== '') {
                                                        val = String(Number(val));
                                                    }
                                                    
                                                    newIncomes[index].amount = val;
                                                    setData('system_incomes', newIncomes);
                                                }}
                                                onBlur={() => {
                                                    if (data.system_incomes[index].amount === '') {
                                                        const newIncomes = [...data.system_incomes];
                                                        newIncomes[index].amount = '0';
                                                        setData('system_incomes', newIncomes);
                                                    }
                                                }}
                                                required
                                            />
                                        </div>
                                    </div>
                                ))}
                                <div className="mt-6 flex justify-end">
                                    <Button type="submit" disabled={processing} className="w-full md:w-auto">
                                        <Save className="mr-2 h-4 w-4" /> Save & Compare
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmModal
                isOpen={confirmState.isOpen}
                onOpenChange={(open) => {
                    if (!open) setConfirmState(prev => ({ ...prev, isOpen: false }));
                }}
                onConfirm={() => {
                    setConfirmState(prev => ({ ...prev, isOpen: false }));
                    confirmState.action();
                }}
                title={confirmState.title}
                description={confirmState.description}
                confirmText={confirmState.confirmText}
                confirmVariant={confirmState.confirmVariant}
            />
        </>
    );
}

Compare.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '#' },
        { title: 'Pending Comparisons', href: '/admin/transactions' },
        { title: 'Input System Data', href: '#' },
    ],
};
