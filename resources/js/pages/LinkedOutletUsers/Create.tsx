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

interface CreateProps {
    users: User[];
    outlets: Outlet[];
    assignedOutletsMap: Record<string, string[]>;
}

export default function Create({ users, outlets, assignedOutletsMap }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        outlet_ids: [] as string[],
    });

    // Determine available outlets for the selected user
    const availableOutlets = outlets.filter((outlet) => {
        if (!data.user_id) {
return true;
} // Show all if no user selected, or hide? The prompt says "show all outlets that not assigned yet to this user". It's better to show all if no user is selected, or we can enforce selecting user first.

        const userAssignedOutlets = assignedOutletsMap[data.user_id] || [];

        return !userAssignedOutlets.includes(outlet.id);
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/linked-outlet-users');
    };

    return (
        <>
            <Head title="Create Linked Outlet User" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Create Link</h1>
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
                                onValueChange={(value) => setData((prev) => ({ ...prev, user_id: value, outlet_ids: [] }))}
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
                                options={availableOutlets}
                                selected={data.outlet_ids}
                                onChange={(selected) => setData('outlet_ids', selected)}
                                placeholder={data.user_id ? "Select outlets" : "Select a user first"}
                                searchPlaceholder="Search outlets..."
                                emptyText={data.user_id ? "No available outlets found." : "Select a user to see available outlets."}
                                disabled={!data.user_id}
                            />
                            {errors.outlet_ids && <p className="text-sm text-destructive">{errors.outlet_ids}</p>}
                            {/* Handle array validation errors if any */}
                            {Object.keys(errors).filter(key => key.startsWith('outlet_ids.')).map(key => (
                                <p key={key} className="text-sm text-destructive">{(errors as any)[key]}</p>
                            ))}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Save Link
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
            title: 'Linked Outlet Users',
            href: '/linked-outlet-users',
        },
        {
            title: 'Create',
            href: '/linked-outlet-users/create',
        },
    ],
};
