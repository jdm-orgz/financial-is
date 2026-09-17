import { Head, Link, useForm, router } from '@inertiajs/react';
import { ChevronLeft, Plus, Trash2, Upload, Send, PlayCircle, Image as ImageIcon, Pencil, X } from 'lucide-react';
import { useState, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Transaction } from '@/types/transaction';

interface Chair {
    id: string;
    name: string;
}

interface ShowProps {
    readonly transaction: Transaction;
    readonly chairs: Chair[];
}

const statusVariantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'secondary',
    approval: 'default',
    correction: 'destructive',
    comparing: 'outline',
    compared: 'outline',
    done: 'default',
};

const statusLabelMap: Record<string, string> = {
    draft: 'Draft',
    approval: 'Pending Approval',
    correction: 'Correction',
    comparing: 'Comparing',
    compared: 'Compared',
    done: 'Done',
};

export default function Show({ transaction, chairs }: ShowProps) {
    const isEditable = ['draft', 'correction'].includes(transaction.status);
    
    // Daily Incomes Form
    const { 
        data: dailyData, 
        setData: setDailyData, 
        post: postDaily, 
        processing: processingDaily,
    } = useForm({
        incomes: chairs.map(chair => {
            const existing = transaction.daily_incomes.find(di => di.chair?.name === chair.name);

            return {
                chair_id: chair.id,
                amount: existing ? String(Number(existing.amount)) : '0',
            };
        }),
    });

    useEffect(() => {
        setDailyData('incomes', chairs.map(chair => {
            const existing = transaction.daily_incomes.find(di => di.chair?.name === chair.name);
            return {
                chair_id: chair.id,
                amount: existing ? String(Number(existing.amount)) : '0',
            };
        }));
    }, [transaction.daily_incomes, chairs]);

    // Realization Form
    const [isRealizationOpen, setIsRealizationOpen] = useState(false);
    const {
        data: realData,
        setData: setRealData,
        post: postReal,
        processing: processingReal,
        errors: errorsReal,
        setError: setErrorReal,
        reset: resetReal,
        clearErrors: clearErrorsReal
    } = useForm({
        _method: 'post',
        problem_chair_id: '',
        replacement_chair_id: '',
        payment_method: '',
        amount: '0',
        proof_image: null as File | null,
        proof_video: null as File | null,
    });

    const [editingRealizationId, setEditingRealizationId] = useState<string | null>(null);
    const [existingProofImage, setExistingProofImage] = useState<string | null>(null);
    const [existingProofVideo, setExistingProofVideo] = useState<string | null>(null);

    const [openProblemDropdown, setOpenProblemDropdown] = useState(false);
    const [openReplacementDropdown, setOpenReplacementDropdown] = useState(false);
    const [openMethodDropdown, setOpenMethodDropdown] = useState(false);

    // Media Modal State
    const [mediaModalOpen, setMediaModalOpen] = useState(false);
    const [mediaModalUrl, setMediaModalUrl] = useState('');
    const [mediaModalType, setMediaModalType] = useState<'image' | 'video'>('video');

    // Transfer Proof Form
    const [isTransferOpen, setIsTransferOpen] = useState(false);
    const {
        setData: setTransferData,
        post: postTransfer,
        processing: processingTransfer,
        errors: errorsTransfer,
        reset: resetTransfer
    } = useForm({
        proof_image: null as File | null,
    });

    const handleSaveDaily = () => {
        postDaily(`/transactions/${transaction.id}/daily-incomes`, {
            preserveScroll: true,
        });
    };

    const handleSubmitRealization = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        clearErrorsReal();

        let hasError = false;

        if (realData.payment_method === 'qris' && !realData.proof_image && !existingProofImage) {
            setErrorReal('proof_image', 'Photo proof is required for QRIS payment method.');
            hasError = true;
        }

        if (!realData.proof_video && !existingProofVideo) {
            setErrorReal('proof_video', 'Video proof is required.');
            hasError = true;
        }

        if (hasError) return;

        const url = editingRealizationId 
            ? `/transactions/${transaction.id}/replacement-realizations/${editingRealizationId}`
            : `/transactions/${transaction.id}/replacement-realizations`;

        postReal(url, {
            onSuccess: () => {
                setIsRealizationOpen(false);
                setEditingRealizationId(null);
                resetReal();
            },
            preserveScroll: true,
        });
    };

    const handleAddClick = () => {
        resetReal();
        clearErrorsReal();
        setRealData('_method', 'post');
        setEditingRealizationId(null);
        setExistingProofImage(null);
        setExistingProofVideo(null);
        setIsRealizationOpen(true);
    };

    const handleDeleteProof = (type: 'image' | 'video') => {
        if (!editingRealizationId) return;
        if (confirm(`Are you sure you want to delete the existing ${type} proof?`)) {
            router.delete(`/transactions/${transaction.id}/replacement-realizations/${editingRealizationId}/proof/${type}`, {
                onSuccess: () => {
                    if (type === 'image') setExistingProofImage(null);
                    if (type === 'video') setExistingProofVideo(null);
                },
                preserveScroll: true
            });
        }
    };

    const handleEditClick = (real: any) => {
        clearErrorsReal();
        setEditingRealizationId(real.id);
        setExistingProofImage(real.proof_image_path || null);
        setExistingProofVideo(real.proof_video_path || null);
        
        // Find matching chair ID from name to bypass encryption mismatches
        const problemChairId = chairs.find(c => c.name === real.problem_chair?.name)?.id || '';
        const replacementChairId = chairs.find(c => c.name === real.replacement_chair?.name)?.id || '';

        setRealData({
            _method: 'put',
            problem_chair_id: problemChairId,
            replacement_chair_id: replacementChairId,
            payment_method: real.payment_method,
            amount: String(Number(real.amount)),
            proof_image: null,
            proof_video: null,
        });
        setIsRealizationOpen(true);
    };

    const handleDeleteRealization = (realizationId: string | number) => {
        if (confirm('Are you sure you want to delete this replacement realization?')) {
            router.delete(`/transactions/${transaction.id}/replacement-realizations/${realizationId}`, {
                preserveScroll: true,
            });
        }
    };

    const handleUploadTransfer = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        postTransfer(`/transactions/${transaction.id}/transfer-proofs`, {
            onSuccess: () => {
                setIsTransferOpen(false);
                resetTransfer();
            },
            preserveScroll: true,
        });
    };

    const handleDeleteTransfer = (proofId: string | number) => {
        if (confirm('Are you sure you want to delete this transfer proof?')) {
            router.delete(`/transactions/${transaction.id}/transfer-proofs/${proofId}`, {
                preserveScroll: true,
            });
        }
    };

    const handleSubmitTransaction = () => {
        if (confirm('Submit this transaction for supervisor approval? Ensure all data is correct.')) {
            router.post(`/transactions/${transaction.id}/submit`, {}, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title={`Transaction ${transaction.outlet.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Transaction Details</h1>
                        <div className="text-sm text-muted-foreground mt-1 flex flex-col gap-0.5">
                            <p>Outlet: {transaction.outlet.name}</p>
                            <p>Date: {transaction.date.includes('T') ? transaction.date.substring(0, 10) : transaction.date}</p>
                            {transaction.date.includes('T') && (
                                <p>Time: {transaction.date.substring(11, 19)} (WIB)</p>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <Badge variant={statusVariantMap[transaction.status] || 'secondary'} className="text-sm">
                            {statusLabelMap[transaction.status] || transaction.status}
                        </Badge>
                        <Button variant="outline" asChild>
                            <Link href="/transactions">
                                <ChevronLeft className="mr-2 h-4 w-4" /> Back
                            </Link>
                        </Button>
                        {isEditable && (
                            <Button onClick={handleSubmitTransaction}>
                                <Send className="mr-2 h-4 w-4" /> Submit for Approval
                            </Button>
                        )}
                    </div>
                </div>

                {transaction.status === 'correction' && transaction.supervisor_notes && (
                    <div className="rounded-md bg-red-50 p-4 border border-red-200">
                        <div className="flex">
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800">Catatan Revisi Supervisor</h3>
                                <div className="mt-2 text-sm text-red-700">
                                    <p>{transaction.supervisor_notes}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
                {transaction.status === 'correction' && transaction.admin_notes && (
                    <div className="rounded-md bg-red-50 p-4 border border-red-200">
                        <div className="flex">
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800">Catatan Revisi Admin</h3>
                                <div className="mt-2 text-sm text-red-700">
                                    <p>{transaction.admin_notes}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    {/* Daily Incomes Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Daily Incomes (Per Chair)</CardTitle>
                            <CardDescription>Input income for each chair</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {chairs.map((chair, index) => (
                                    <div key={chair.id} className="grid grid-cols-2 items-center gap-4">
                                        <Label>{chair.name}</Label>
                                        <Input 
                                            type="number" 
                                            min="0"
                                            value={dailyData.incomes[index].amount}
                                            onChange={(e) => {
                                                const newIncomes = [...dailyData.incomes];
                                                let val = e.target.value;
                                                
                                                if (val !== '') {
                                                    val = String(Number(val));
                                                }
                                                
                                                newIncomes[index].amount = val;
                                                setDailyData('incomes', newIncomes);
                                            }}
                                            onBlur={() => {
                                                if (dailyData.incomes[index].amount === '') {
                                                    const newIncomes = [...dailyData.incomes];
                                                    newIncomes[index].amount = '0';
                                                    setDailyData('incomes', newIncomes);
                                                }
                                            }}
                                            disabled={!isEditable}
                                        />
                                    </div>
                                ))}
                                {isEditable && (
                                    <Button onClick={handleSaveDaily} disabled={processingDaily} className="w-full mt-4">
                                        Save Incomes
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        {/* Replacement Realizations Section */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div>
                                    <CardTitle>Replacement Realizations</CardTitle>
                                    <CardDescription>Record problematic chairs that were replaced</CardDescription>
                                </div>
                                {isEditable && (
                                    <Button size="sm" onClick={handleAddClick}>
                                        <Plus className="mr-2 h-4 w-4" /> Add
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {transaction.replacement_realizations.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">No replacement realization yet.</p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Problem Chair</TableHead>
                                                <TableHead>Replacement Chair</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead>Method</TableHead>
                                                <TableHead className="text-right">Action</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {transaction.replacement_realizations.map((real) => (
                                                <TableRow key={real.id}>
                                                    <TableCell>{real.problem_chair.name}</TableCell>
                                                    <TableCell>{real.replacement_chair.name}</TableCell>
                                                    <TableCell>Rp {Number(real.amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</TableCell>
                                                    <TableCell className="uppercase">{real.payment_method}</TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-2">
                                                            {real.proof_image_path && (
                                                                <Button 
                                                                    variant="ghost" 
                                                                    size="icon" 
                                                                    className="h-6 w-6 text-blue-600"
                                                                    onClick={() => {
                                                                        setMediaModalUrl(`/storage/${real.proof_image_path}`);
                                                                        setMediaModalType('image');
                                                                        setMediaModalOpen(true);
                                                                    }}
                                                                >
                                                                    <ImageIcon className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {real.proof_video_path && (
                                                                <Button 
                                                                    variant="ghost" 
                                                                    size="icon" 
                                                                    className="h-6 w-6 text-blue-600"
                                                                    onClick={() => {
                                                                        setMediaModalUrl(`/storage/${real.proof_video_path}`);
                                                                        setMediaModalType('video');
                                                                        setMediaModalOpen(true);
                                                                    }}
                                                                >
                                                                    <PlayCircle className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {isEditable && (
                                                                <>
                                                                    <Button variant="ghost" size="icon" onClick={() => handleEditClick(real)} className="h-6 w-6 text-orange-600">
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button variant="ghost" size="icon" onClick={() => handleDeleteRealization(real.id)} className="h-6 w-6 text-destructive">
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>

                        {/* Transfer Proofs Section */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div>
                                    <CardTitle>Transfer Proofs</CardTitle>
                                    <CardDescription>Upload daily transfer proofs</CardDescription>
                                </div>
                                {isEditable && (
                                    <Button size="sm" onClick={() => setIsTransferOpen(true)}>
                                        <Upload className="mr-2 h-4 w-4" /> Upload
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {transaction.transfer_proofs.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">No transfer proof yet.</p>
                                ) : (
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        {transaction.transfer_proofs.map((proof) => (
                                            <div key={proof.id} className="relative group rounded-md border p-2">
                                                <img src={`/storage/${proof.proof_image_path}`} alt="Transfer Proof" className="w-full h-32 object-cover rounded" />
                                                {isEditable && (
                                                    <Button 
                                                        variant="destructive" 
                                                        size="icon" 
                                                        className="absolute top-1 right-1 h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                                                        onClick={() => handleDeleteTransfer(proof.id)}
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </Button>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Realization Modal */}
            <Dialog open={isRealizationOpen} onOpenChange={setIsRealizationOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingRealizationId ? 'Edit Replacement Realization' : 'Add Replacement Realization'}</DialogTitle>
                        <DialogDescription>Input data for the problematic chair and its replacement.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubmitRealization}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Problem Chair</Label>
                                <Popover open={openProblemDropdown} onOpenChange={setOpenProblemDropdown}>
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={openProblemDropdown}
                                            aria-invalid={!!errorsReal.problem_chair_id}
                                            className={cn('w-full justify-between font-normal', errorsReal.problem_chair_id && 'border-destructive ring-destructive/20 focus-visible:ring-destructive/20')}
                                        >
                                            {realData.problem_chair_id
                                                ? chairs.find((c) => c.id === realData.problem_chair_id)?.name
                                                : 'Select Chair'}
                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                                        <Command>
                                            <CommandInput placeholder="Search chair..." />
                                            <CommandList>
                                                <CommandEmpty>No chair found.</CommandEmpty>
                                                <CommandGroup>
                                                    {chairs.map((c) => (
                                                        <CommandItem
                                                            key={`p_${c.id}`}
                                                            value={c.name}
                                                            onSelect={() => {
                                                                setRealData('problem_chair_id', c.id);
                                                                setOpenProblemDropdown(false);
                                                            }}
                                                        >
                                                            <Check className={cn('mr-2 h-4 w-4 flex-shrink-0', realData.problem_chair_id === c.id ? 'opacity-100' : 'opacity-0')} />
                                                            {c.name}
                                                        </CommandItem>
                                                    ))}
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                                {errorsReal.problem_chair_id && <p className="text-xs text-destructive">{errorsReal.problem_chair_id}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Replacement Chair</Label>
                                <Popover open={openReplacementDropdown} onOpenChange={setOpenReplacementDropdown}>
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={openReplacementDropdown}
                                            aria-invalid={!!errorsReal.replacement_chair_id}
                                            className={cn('w-full justify-between font-normal', errorsReal.replacement_chair_id && 'border-destructive ring-destructive/20 focus-visible:ring-destructive/20')}
                                        >
                                            {realData.replacement_chair_id
                                                ? chairs.find((c) => c.id === realData.replacement_chair_id)?.name
                                                : 'Select Chair'}
                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                                        <Command>
                                            <CommandInput placeholder="Search chair..." />
                                            <CommandList>
                                                <CommandEmpty>No chair found.</CommandEmpty>
                                                <CommandGroup>
                                                    {chairs.map((c) => (
                                                        <CommandItem
                                                            key={`r_${c.id}`}
                                                            value={c.name}
                                                            onSelect={() => {
                                                                setRealData('replacement_chair_id', c.id);
                                                                setOpenReplacementDropdown(false);
                                                            }}
                                                        >
                                                            <Check className={cn('mr-2 h-4 w-4 flex-shrink-0', realData.replacement_chair_id === c.id ? 'opacity-100' : 'opacity-0')} />
                                                            {c.name}
                                                        </CommandItem>
                                                    ))}
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                                {errorsReal.replacement_chair_id && <p className="text-xs text-destructive">{errorsReal.replacement_chair_id}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Payment Method</Label>
                                <Popover open={openMethodDropdown} onOpenChange={setOpenMethodDropdown}>
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={openMethodDropdown}
                                            aria-invalid={!!errorsReal.payment_method}
                                            className={cn('w-full justify-between font-normal uppercase', errorsReal.payment_method && 'border-destructive ring-destructive/20 focus-visible:ring-destructive/20')}
                                        >
                                            {realData.payment_method
                                                ? realData.payment_method
                                                : <span className="normal-case">Select Method</span>}
                                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                                        <Command>
                                            <CommandList>
                                                <CommandGroup>
                                                    <CommandItem value="cash" onSelect={() => { setRealData('payment_method', 'cash'); setOpenMethodDropdown(false); }}>
                                                        <Check className={cn('mr-2 h-4 w-4 flex-shrink-0', realData.payment_method === 'cash' ? 'opacity-100' : 'opacity-0')} />
                                                        CASH
                                                    </CommandItem>
                                                    <CommandItem value="qris" onSelect={() => { setRealData('payment_method', 'qris'); setOpenMethodDropdown(false); }}>
                                                        <Check className={cn('mr-2 h-4 w-4 flex-shrink-0', realData.payment_method === 'qris' ? 'opacity-100' : 'opacity-0')} />
                                                        QRIS
                                                    </CommandItem>
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                                {errorsReal.payment_method && <p className="text-xs text-destructive">{errorsReal.payment_method}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Amount</Label>
                                <Input 
                                    type="number" 
                                    step="1" 
                                    min="1" 
                                    value={realData.amount} 
                                    aria-invalid={!!errorsReal.amount}
                                    className={cn(errorsReal.amount && 'border-destructive ring-destructive/20 focus-visible:ring-destructive/20')}
                                    onChange={(e) => {
                                        let val = e.target.value;
                                        if (val !== '') {
                                            val = String(Number(val));
                                        }
                                        setRealData('amount', val);
                                    }} 
                                    onBlur={() => {
                                        if (realData.amount === '') {
                                            setRealData('amount', '0');
                                        }
                                    }}
                                />
                                {errorsReal.amount && <p className="text-xs text-destructive">{errorsReal.amount}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Photo Proof (Required for QRIS)</Label>
                                {existingProofImage && (
                                    <div className="mb-2 relative inline-block group w-fit">
                                        <img src={`/storage/${existingProofImage}`} alt="Existing Photo" className="h-20 w-auto rounded border" />
                                        <Button 
                                            type="button"
                                            variant="destructive" 
                                            size="icon" 
                                            className="absolute -top-2 -right-2 h-6 w-6 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                            onClick={() => handleDeleteProof('image')}
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </div>
                                )}
                                <Input 
                                    type="file" 
                                    accept="image/*" 
                                    aria-invalid={!!errorsReal.proof_image}
                                    className={cn(errorsReal.proof_image && 'border-destructive ring-destructive/20 focus-visible:ring-destructive/20')}
                                    onChange={(e) => setRealData('proof_image', e.target.files ? e.target.files[0] : null)} 
                                />
                                {editingRealizationId && <p className="text-xs text-muted-foreground">Leave empty to keep existing photo.</p>}
                                {errorsReal.proof_image && <p className="text-xs text-destructive">{errorsReal.proof_image}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Video Proof (Required)</Label>
                                {existingProofVideo && (
                                    <div className="mb-2 relative inline-block group w-fit">
                                        <video src={`/storage/${existingProofVideo}`} className="h-20 w-auto rounded border" />
                                        <Button 
                                            type="button"
                                            variant="destructive" 
                                            size="icon" 
                                            className="absolute -top-2 -right-2 h-6 w-6 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                            onClick={() => handleDeleteProof('video')}
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </div>
                                )}
                                <Input 
                                    type="file" 
                                    accept="video/*" 
                                    aria-invalid={!!errorsReal.proof_video}
                                    className={cn(errorsReal.proof_video && 'border-destructive ring-destructive/20 focus-visible:ring-destructive/20')}
                                    onChange={(e) => setRealData('proof_video', e.target.files ? e.target.files[0] : null)} 
                                />
                                {editingRealizationId && <p className="text-xs text-muted-foreground">Leave empty to keep existing video.</p>}
                                {errorsReal.proof_video && <p className="text-xs text-destructive">{errorsReal.proof_video}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsRealizationOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processingReal}>Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Transfer Modal */}
            <Dialog open={isTransferOpen} onOpenChange={setIsTransferOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Upload Transfer Proof</DialogTitle>
                        <DialogDescription>Select an image file for the transfer proof.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleUploadTransfer}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Photo Proof</Label>
                                <Input type="file" accept="image/*" onChange={(e) => setTransferData('proof_image', e.target.files ? e.target.files[0] : null)} />
                                {errorsTransfer.proof_image && <p className="text-xs text-destructive">{errorsTransfer.proof_image}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsTransferOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processingTransfer}>Upload</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Media Player Modal */}
            <Dialog open={mediaModalOpen} onOpenChange={setMediaModalOpen}>
                <DialogContent className="sm:max-w-4xl p-0">
                    <DialogHeader className="p-4 pb-0">
                        <DialogTitle>{mediaModalType === 'video' ? 'Video Proof' : 'Photo Proof'}</DialogTitle>
                    </DialogHeader>
                    <div className="flex items-center justify-center p-4">
                        {mediaModalType === 'video' ? (
                            <video src={mediaModalUrl} controls className="w-full max-h-[80vh] rounded-md" />
                        ) : (
                            <img src={mediaModalUrl} alt="Proof" className="w-full max-h-[80vh] object-contain rounded-md" />
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
        { title: 'Detail', href: '#' },
    ],
};
