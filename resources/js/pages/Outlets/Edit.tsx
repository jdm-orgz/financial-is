import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Outlet {
    id: number;
    name: string;
    address: string;
}

interface EditProps {
    outlet: Outlet;
}

export default function Edit({ outlet }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: outlet.name || '',
        address: outlet.address || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/outlets/${outlet.id}`);
    };

    return (
        <>
            <Head title="Edit Outlet" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Edit Outlet</h1>
                    <Button variant="outline" asChild>
                        <Link href="/outlets">Back</Link>
                    </Button>
                </div>

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Outlet Name"
                            />
                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="address">Address</Label>
                            <Input
                                id="address"
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                placeholder="Outlet Address"
                            />
                            {errors.address && <p className="text-sm text-destructive">{errors.address}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Update Outlet
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

Edit.layout = {
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
            title: 'Edit',
            href: '#',
        },
    ],
};
