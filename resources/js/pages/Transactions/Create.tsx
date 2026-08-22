import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Outlet {
    id: string;
    name: string;
}

interface CreateProps {
    outlets: Outlet[];
}

export default function Create({ outlets }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        outlet_id: '',
        date: new Date().toISOString().split('T')[0],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/transactions');
    };

    return (
        <>
            <Head title="Create Transaction" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">New Transaction</h1>
                </div>

                <div className="mx-auto mt-4 w-full max-w-xl">
                    <form onSubmit={submit}>
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="outlet_id">Outlet</Label>
                                <Select
                                    value={data.outlet_id}
                                    onValueChange={(val) => setData('outlet_id', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select an outlet" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {outlets.map((outlet) => (
                                            <SelectItem key={outlet.id} value={outlet.id}>
                                                {outlet.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.outlet_id && (
                                    <p className="text-sm text-destructive">{errors.outlet_id}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="date">Date</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={data.date}
                                    onChange={(e) => setData('date', e.target.value)}
                                />
                                {errors.date && (
                                    <p className="text-sm text-destructive">{errors.date}</p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2 mt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/transactions">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Create Draft
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
        { title: 'Create', href: '#' },
    ],
};
