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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

interface Outlet {
    id: string;
    name: string;
}

interface CreateProps {
    outlets: Outlet[];
}

export default function Create({ outlets }: CreateProps) {
    const [openOutletDropdown, setOpenOutletDropdown] = useState(false);
    const [defaultDate] = useState(() => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().split('T')[0]);
    const { data, setData, post, processing, errors } = useForm({
        outlet_id: '',
        date: defaultDate,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/transactions');
    };

    return (
        <>
            <Head title="Create Transaction" />
            <div className="flex h-full max-w-2xl flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">New Transaction</h1>
                    <Button variant="outline" asChild>
                        <Link href="/transactions">Back</Link>
                    </Button>
                </div>

                <div className="rounded-md border p-6">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="outlet_id">Outlet</Label>
                            <Popover
                                open={openOutletDropdown}
                                onOpenChange={setOpenOutletDropdown}
                            >
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openOutletDropdown}
                                        aria-invalid={!!errors.outlet_id}
                                        className={cn(
                                            'w-full justify-between',
                                            errors.outlet_id &&
                                                'border-destructive ring-destructive/20 focus-visible:ring-destructive/20',
                                        )}
                                    >
                                        {data.outlet_id
                                            ? outlets.find(
                                                  (outlet) =>
                                                      outlet.id ===
                                                      data.outlet_id,
                                              )?.name
                                            : 'Select an outlet'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="w-full p-0"
                                    align="start"
                                >
                                    <Command>
                                        <CommandInput placeholder="Search outlet..." />
                                        <CommandList>
                                            <CommandEmpty>
                                                No outlet found.
                                            </CommandEmpty>
                                            <CommandGroup>
                                                {outlets.map((outlet) => (
                                                    <CommandItem
                                                        key={outlet.id}
                                                        value={outlet.name}
                                                        onSelect={() => {
                                                            setData(
                                                                'outlet_id',
                                                                outlet.id,
                                                            );
                                                            setOpenOutletDropdown(
                                                                false,
                                                            );
                                                        }}
                                                    >
                                                        <Check
                                                            className={cn(
                                                                'mr-2 h-4 w-4 flex-shrink-0',
                                                                data.outlet_id ===
                                                                    outlet.id
                                                                    ? 'opacity-100'
                                                                    : 'opacity-0',
                                                            )}
                                                        />
                                                        {outlet.name}
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.outlet_id && (
                                <p className="text-sm text-destructive">
                                    {errors.outlet_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="date">Date</Label>
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) =>
                                    setData('date', e.target.value)
                                }
                                onClick={(e) => {
                                    if (
                                        'showPicker' in
                                        HTMLInputElement.prototype
                                    ) {
                                        try {
                                            e.currentTarget.showPicker();
                                        } catch {
                                            // showPicker may be blocked by browser policy
                                        }
                                    }
                                }}
                                onFocus={(e) => {
                                    if (
                                        'showPicker' in
                                        HTMLInputElement.prototype
                                    ) {
                                        try {
                                            e.currentTarget.showPicker();
                                        } catch {
                                            // showPicker may be blocked by browser policy
                                        }
                                    }
                                }}
                                aria-invalid={!!errors.date}
                                className="relative w-full cursor-pointer [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-3 [&::-webkit-calendar-picker-indicator]:cursor-pointer"
                            />
                            {errors.date && (
                                <p className="text-sm text-destructive">
                                    {errors.date}
                                </p>
                            )}
                        </div>

                        <Button type="submit" disabled={processing}>
                            Create Draft
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
        { title: 'Create', href: '#' },
    ],
};
