import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        address: '',
        prefix: '',
        chairs_count: 0,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/outlets');
    };

    return (
        <>
            <Head title="Create Outlet" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Create Outlet</h1>
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

                        <div className="space-y-2">
                            <Label htmlFor="chairs_count">Number of Chairs to Generate</Label>
                            <Input
                                id="chairs_count"
                                type="number"
                                min="0"
                                value={data.chairs_count}
                                onChange={(e) => setData('chairs_count', parseInt(e.target.value) || 0)}
                                placeholder="Number of Chairs"
                            />
                            {errors.chairs_count && <p className="text-sm text-destructive">{errors.chairs_count}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="prefix">Chair Prefix</Label>
                            <Input
                                id="prefix"
                                value={data.prefix}
                                onChange={(e) => setData('prefix', e.target.value)}
                                placeholder="e.g. VIP, TBL, CHR"
                            />
                            {errors.prefix && <p className="text-sm text-destructive">{errors.prefix}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Save Outlet
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

Create.layout = {
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
            title: 'Create',
            href: '/outlets/create',
        },
    ],
};
