import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { MultiSelectModal } from '@/components/ui/multi-select-modal';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface User {
    id: string;
    name: string;
    email: string;
    role?: {
        id: string;
        description: string;
    };
}

interface Outlet {
    id: string;
    name: string;
}

interface LinkedOutletUser {
    id: string;
    user_id: string;
    outlet_ids: string[];
    is_active: string;
}

interface EditProps {
    linkedOutletUser: LinkedOutletUser;
    users: User[];
    outlets: Outlet[];
}

export default function Edit({ linkedOutletUser, users, outlets }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        user_id: linkedOutletUser.user_id,
        outlet_ids: linkedOutletUser.outlet_ids || [],
        is_active: linkedOutletUser.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/linked-outlet-users/${linkedOutletUser.id}`);
    };

    return (
        <>
            <Head title="Edit Linked Outlet User" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Edit Link</h1>
                    <Button variant="outline" asChild>
                        <Link href="/linked-outlet-users">Back</Link>
                    </Button>
                </div>

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="user_id">User</Label>
                            <Select
                                value={data.user_id}
                                onValueChange={(value) => setData('user_id', value)}
                                disabled={true}
                            >
                                <SelectTrigger id="user_id" className="w-full">
                                    <SelectValue placeholder="Select a user" />
                                </SelectTrigger>
                                <SelectContent>
                                    {users.map((user) => (
                                        <SelectItem key={user.id} value={user.id}>
                                            {user.name} ({user.email})
                                            {user.role?.description && (
                                                <> - <strong>{user.role.description}</strong></>
                                            )}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.user_id && <p className="text-sm text-destructive">{errors.user_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="outlet_ids">Outlets</Label>
                            <MultiSelectModal
                                options={outlets}
                                selected={data.outlet_ids}
                                onChange={(selected) => setData('outlet_ids', selected)}
                                placeholder="Select outlets"
                                searchPlaceholder="Search outlets..."
                                emptyText="No outlets found."
                            />
                            {errors.outlet_ids && <p className="text-sm text-destructive">{errors.outlet_ids}</p>}
                            {Object.keys(errors).filter(key => key.startsWith('outlet_ids.')).map(key => (
                                <p key={key} className="text-sm text-destructive">{(errors as any)[key]}</p>
                            ))}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Update Link
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
            title: 'Linked Outlet Users',
            href: '/linked-outlet-users',
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
