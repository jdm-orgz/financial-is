import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Chair {
    id: number;
    name: string;
}

interface Outlet {
    id: number;
    name: string;
}

interface EditProps {
    chair: Chair;
    outlet: Outlet;
}

export default function Edit({ chair, outlet }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: chair.name,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/outlets/${outlet.id}/chairs/${chair.id}`);
    };

    return (
        <>
            <Head title={`Edit Chair - ${outlet.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Edit Chair for {outlet.name}</h1>
                    <Button variant="outline" asChild>
                        <Link href={`/outlets/${outlet.id}/chairs`}>Back</Link>
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
                                placeholder="Chair Name"
                            />
                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Update Chair
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
            title: 'Chairs',
            href: '#',
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
