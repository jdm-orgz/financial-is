import { Head, Link, useForm } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Label } from '@/components/ui/label';
import { MultiSelectModal } from '@/components/ui/multi-select-modal';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

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

export default function Create({
    users,
    outlets,
    assignedOutletsMap,
}: CreateProps) {
    const [openUserDropdown, setOpenUserDropdown] = useState(false);
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
            <div className="flex h-full max-w-2xl flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Create Link</h1>
                    <Button variant="outline" asChild>
                        <Link href="/linked-outlet-users">Back</Link>
                    </Button>
                </div>

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="user_id">User</Label>
                            <Popover
                                open={openUserDropdown}
                                onOpenChange={setOpenUserDropdown}
                            >
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openUserDropdown}
                                        className="w-full justify-between"
                                    >
                                        {data.user_id
                                            ? (() => {
                                                  const selectedUser =
                                                      users.find(
                                                          (user) =>
                                                              user.id ===
                                                              data.user_id,
                                                      );

                                                  return selectedUser ? (
                                                      <span className="truncate">
                                                          {selectedUser.name} (
                                                          {selectedUser.email})
                                                          {selectedUser.role
                                                              ?.description &&
                                                              ` - ${selectedUser.role.description}`}
                                                      </span>
                                                  ) : (
                                                      'Select a user'
                                                  );
                                              })()
                                            : 'Select a user'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="w-full p-0"
                                    align="start"
                                >
                                    <Command
                                        filter={(value, search) => {
                                            const searchable = value.split('___')[0];
                                            if (
                                                searchable
                                                    .toLowerCase()
                                                    .includes(
                                                        search.toLowerCase(),
                                                    )
                                            )
                                                return 1;
                                            return 0;
                                        }}
                                    >
                                        <CommandInput placeholder="Search user..." />
                                        <CommandList>
                                            <CommandEmpty>
                                                No user found.
                                            </CommandEmpty>
                                            <CommandGroup>
                                                {users.map((user) => (
                                                    <CommandItem
                                                        key={user.id}
                                                        value={`${user.name} ${user.email} ${user.role?.description || ''}___${user.id}`}
                                                        onSelect={() => {
                                                            setData((prev) => ({
                                                                ...prev,
                                                                user_id:
                                                                    user.id,
                                                                outlet_ids: [],
                                                            }));
                                                            setOpenUserDropdown(
                                                                false,
                                                            );
                                                        }}
                                                    >
                                                        <Check
                                                            className={cn(
                                                                'mr-2 h-4 w-4 flex-shrink-0',
                                                                data.user_id ===
                                                                    user.id
                                                                    ? 'opacity-100'
                                                                    : 'opacity-0',
                                                            )}
                                                        />
                                                        {user.name} (
                                                        {user.email})
                                                        {user.role
                                                            ?.description && (
                                                            <>
                                                                {' '}
                                                                -{' '}
                                                                <strong>
                                                                    {
                                                                        user
                                                                            .role
                                                                            .description
                                                                    }
                                                                </strong>
                                                            </>
                                                        )}
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.user_id && (
                                <p className="text-sm text-destructive">
                                    {errors.user_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="outlet_ids">Outlets</Label>
                            <MultiSelectModal
                                options={availableOutlets}
                                selected={data.outlet_ids}
                                onChange={(selected) =>
                                    setData('outlet_ids', selected)
                                }
                                placeholder={
                                    data.user_id
                                        ? 'Select outlets'
                                        : 'Select a user first'
                                }
                                searchPlaceholder="Search outlets..."
                                emptyText={
                                    data.user_id
                                        ? 'No available outlets found.'
                                        : 'Select a user to see available outlets.'
                                }
                                disabled={!data.user_id}
                            />
                            {errors.outlet_ids && (
                                <p className="text-sm text-destructive">
                                    {errors.outlet_ids}
                                </p>
                            )}
                            {/* Handle array validation errors if any */}
                            {Object.keys(errors)
                                .filter((key) => key.startsWith('outlet_ids.'))
                                .map((key) => (
                                    <p
                                        key={key}
                                        className="text-sm text-destructive"
                                    >
                                        {(errors as any)[key]}
                                    </p>
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
