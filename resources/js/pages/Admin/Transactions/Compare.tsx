import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
        system_incomes: chairs.map(chair => ({
            chair_id: chair.id,
            amount: 0, // Admin inputs blindly, existing data is not shown by design
        })),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/admin/transactions/${transaction.id}/system-incomes`);
    };

    return (
        <>
            <Head title="System Income Input" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Input System Data</h1>
                        <p className="text-sm text-muted-foreground">
                            {transaction.outlet.name} - {transaction.date}
                        </p>
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
                                        <div className="col-span-2 relative">
                                            <span className="absolute left-3 top-2.5 text-muted-foreground">Rp</span>
                                            <Input 
                                                type="number" 
                                                className="pl-10"
                                                value={data.system_incomes[index].amount || ''}
                                                onChange={(e) => {
                                                    const newIncomes = [...data.system_incomes];
                                                    newIncomes[index].amount = parseInt(e.target.value) || 0;
                                                    setData('system_incomes', newIncomes);
                                                }}
                                                placeholder="0"
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
