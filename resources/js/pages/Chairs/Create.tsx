import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Outlet {
    id: number;
    name: string;
}

interface CreateProps {
    outlet: Outlet;
}

export default function Create({ outlet }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/outlets/${outlet.id}/chairs`);
    };

    return (
        <>
            <Head title={`Create Chair - ${outlet.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Create Chair for {outlet.name}</h1>
                    <Button variant="outline" asChild>
                        <Link href={`/outlets/${outlet.id}/chairs`}>Back</Link>
                    </Button>
                </div>

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name (Optional)</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Leave blank to auto-generate"
                            />
                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Save Chair
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
            title: 'Chairs',
            href: '#', // The outlet id is needed, so dynamic breadcrumbs might be complex here.
        },
        {
            title: 'Create',
            href: '#',
        },
    ],
};
