import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

interface Role {
    id: number;
    name: string;
    description: string;
}

interface User {
    id: string;
    username: string;
    name: string;
    role_id: string;
}

interface Props {
    user: User;
    roles: Role[];
}

export default function Edit({ user, roles }: Props) {
    const { auth } = usePage<any>().props;
    const { data, setData, put, processing, errors } = useForm({
        username: user.username,
        name: user.name,
        role_id: user.role_id?.toString() || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/users/${user.id}`);
    };

    return (
        <>
            <Head title="Edit User" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Edit User</h1>
                    <Button variant="outline" asChild>
                        <Link href="/users">Back</Link>
                    </Button>
                </div>

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="username">Username</Label>
                            <Input
                                id="username"
                                type="text"
                                value={data.username}
                                onChange={(e) => setData('username', e.target.value)}
                                placeholder="johndoe"
                            />
                            {errors.username && <p className="text-sm text-destructive">{errors.username}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="John Doe"
                            />
                            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="role_id">Role</Label>
                            <Select 
                                value={data.role_id} 
                                onValueChange={(value) => setData('role_id', value)}
                                disabled={user.username === auth.user.username && (auth.user as any).role_name !== 'super_admin'}
                            >
                                <SelectTrigger id="role_id" className="w-full">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((role) => (
                                        <SelectItem key={role.id} value={role.id.toString()}>
                                            {role.description}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {user.username === auth.user.username && (auth.user as any).role_name !== 'super_admin' && (
                                <p className="text-[0.8rem] font-medium text-muted-foreground">
                                    You cannot update your own role.
                                </p>
                            )}
                            {errors.role_id && <p className="text-sm text-destructive">{errors.role_id}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Update User
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
            title: 'Users',
            href: '/users',
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
