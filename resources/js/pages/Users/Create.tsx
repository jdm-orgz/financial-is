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
} from "@/components/ui/select";

interface Role {
    id: number;
    name: string;
    description: string;
}

interface Props {
    roles: Role[];
}

export default function Create({ roles }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        name: '',
        role_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/users');
    };

    return (
        <>
            <Head title="Create User" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 max-w-2xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Create User</h1>
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
                            {errors.role_id && <p className="text-sm text-destructive">{errors.role_id}</p>}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Create User
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
            title: 'Users',
            href: '/users',
        },
        {
            title: 'Create',
            href: '/users/create',
        },
    ],
};
