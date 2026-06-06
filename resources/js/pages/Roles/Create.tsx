import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

interface CreateProps {
    available_permissions: Record<string, string>;
}

export default function Create({ available_permissions }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        description: string;
        permissions: string[];
    }>({
        name: '',
        description: '',
        permissions: [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/roles');
    };

    return (
        <>
            <Head title="Create Role" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Create Role</h1>
                    <Button variant="outline" asChild>
                        <Link href="/roles">Back</Link>
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
                                placeholder="Role Name"
                            />
                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="Role Description"
                            />
                            {errors.description && <p className="text-sm text-destructive">{errors.description}</p>}
                        </div>

                        <div className="space-y-3">
                            <Label>Permissions</Label>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-md border p-4">
                                {Object.entries(available_permissions).map(([key, label]) => (
                                    <div key={key} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={`permission-${key}`}
                                            checked={data.permissions.includes(key)}
                                            onCheckedChange={(checked) => {
                                                if (checked) {
                                                    setData('permissions', [...data.permissions, key]);
                                                } else {
                                                    setData('permissions', data.permissions.filter((p) => p !== key));
                                                }
                                            }}
                                        />
                                        <Label
                                            htmlFor={`permission-${key}`}
                                            className="text-sm font-normal cursor-pointer leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            {label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.permissions && <p className="text-sm text-destructive">{errors.permissions as string}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Save Role
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
            title: 'Roles',
            href: '/roles',
        },
        {
            title: 'Create',
            href: '/roles/create',
        },
    ],
};
