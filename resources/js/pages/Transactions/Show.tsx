import { Head, Link, useForm, router } from '@inertiajs/react';
import { ChevronLeft, Plus, Trash2, Upload, Send } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
            const existing = transaction.daily_incomes.find(di => String(di.chair.id) === String(chair.id));

            return {
                chair_id: chair.id,
                amount: existing ? existing.amount : 0,
            };
        }),
    });

    // Realization Form
    const [isRealizationOpen, setIsRealizationOpen] = useState(false);
    const {
        data: realData,
        setData: setRealData,
        post: postReal,
        processing: processingReal,
        errors: errorsReal,
        reset: resetReal
    } = useForm({
        problem_chair_id: '',
        replacement_chair_id: '',
        payment_method: '',
        amount: 5000,
        proof_image: null as File | null,
        proof_video: null as File | null,
    });

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

    const handleAddRealization = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        postReal(`/transactions/${transaction.id}/replacement-realizations`, {
            onSuccess: () => {
                setIsRealizationOpen(false);
                resetReal();
            },
            preserveScroll: true,
        });
    };

    const handleDeleteRealization = (realizationId: string | number) => {
        if (confirm('Yakin ingin menghapus realisasi pengganti ini?')) {
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
        if (confirm('Yakin ingin menghapus bukti transfer ini?')) {
            router.delete(`/transactions/${transaction.id}/transfer-proofs/${proofId}`, {
                preserveScroll: true,
            });
        }
    };

    const handleSubmitTransaction = () => {
        if (confirm('Submit transaksi ini untuk persetujuan supervisor? Pastikan semua data sudah benar.')) {
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
                        <p className="text-sm text-muted-foreground">
                            {transaction.outlet.name} - {transaction.date}
                        </p>
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
                            <Button onClick={handleSubmitTransaction} className="bg-blue-600 hover:bg-blue-700 text-white">
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
                            <CardDescription>Input pendapatan masing-masing kursi</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {chairs.map((chair, index) => (
                                    <div key={chair.id} className="grid grid-cols-2 items-center gap-4">
                                        <Label>{chair.name}</Label>
                                        <Input 
                                            type="number" 
                                            value={dailyData.incomes[index].amount}
                                            onChange={(e) => {
                                                const newIncomes = [...dailyData.incomes];
                                                newIncomes[index].amount = Number.parseInt(e.target.value) || 0;
                                                setDailyData('incomes', newIncomes);
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
                                    <CardDescription>Catat kursi bermasalah yang diganti</CardDescription>
                                </div>
                                {isEditable && (
                                    <Button size="sm" onClick={() => setIsRealizationOpen(true)}>
                                        <Plus className="mr-2 h-4 w-4" /> Add
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {transaction.replacement_realizations.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">Belum ada realisasi pengganti.</p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Problem Chair</TableHead>
                                                <TableHead>Replacement Chair</TableHead>
                                                <TableHead>Jumlah</TableHead>
                                                <TableHead>Metode</TableHead>
                                                <TableHead className="text-right">Aksi</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {transaction.replacement_realizations.map((real) => (
                                                <TableRow key={real.id}>
                                                    <TableCell>{real.problem_chair.name}</TableCell>
                                                    <TableCell>{real.replacement_chair.name}</TableCell>
                                                    <TableCell>Rp {real.amount.toLocaleString('id-ID')}</TableCell>
                                                    <TableCell className="uppercase">{real.payment_method}</TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-2">
                                                            {real.proof_image_path && (
                                                                <a href={`/storage/${real.proof_image_path}`} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline text-xs">Foto</a>
                                                            )}
                                                            {real.proof_video_path && (
                                                                <a href={`/storage/${real.proof_video_path}`} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline text-xs">Video</a>
                                                            )}
                                                            {isEditable && (
                                                                <Button variant="ghost" size="icon" onClick={() => handleDeleteRealization(real.id)} className="h-6 w-6 text-destructive">
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
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
                                    <CardDescription>Upload bukti transfer harian</CardDescription>
                                </div>
                                {isEditable && (
                                    <Button size="sm" onClick={() => setIsTransferOpen(true)}>
                                        <Upload className="mr-2 h-4 w-4" /> Upload
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                {transaction.transfer_proofs.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">Belum ada bukti transfer.</p>
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
                        <DialogTitle>Add Replacement Realization</DialogTitle>
                        <DialogDescription>Input data for the problematic chair and its replacement.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleAddRealization}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Problem Chair</Label>
                                <Select value={realData.problem_chair_id} onValueChange={(v) => setRealData('problem_chair_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select Chair" /></SelectTrigger>
                                    <SelectContent>
                                        {chairs.map(c => <SelectItem key={`p_${c.id}`} value={c.id}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errorsReal.problem_chair_id && <p className="text-xs text-destructive">{errorsReal.problem_chair_id}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Replacement Chair</Label>
                                <Select value={realData.replacement_chair_id} onValueChange={(v) => setRealData('replacement_chair_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select Chair" /></SelectTrigger>
                                    <SelectContent>
                                        {chairs.map(c => <SelectItem key={`r_${c.id}`} value={c.id}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errorsReal.replacement_chair_id && <p className="text-xs text-destructive">{errorsReal.replacement_chair_id}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Payment Method</Label>
                                <Select value={realData.payment_method} onValueChange={(v) => setRealData('payment_method', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select Method" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cash">Cash</SelectItem>
                                        <SelectItem value="qris">QRIS</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errorsReal.payment_method && <p className="text-xs text-destructive">{errorsReal.payment_method}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Amount</Label>
                                <Input type="number" step="1" min="1" value={realData.amount} onChange={(e) => setRealData('amount', Number.parseInt(e.target.value) || 0)} />
                                {errorsReal.amount && <p className="text-xs text-destructive">{errorsReal.amount}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Photo Proof (Required for QRIS)</Label>
                                <Input type="file" accept="image/*" onChange={(e) => setRealData('proof_image', e.target.files ? e.target.files[0] : null)} />
                                {errorsReal.proof_image && <p className="text-xs text-destructive">{errorsReal.proof_image}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Video Proof (Required)</Label>
                                <Input type="file" accept="video/*" onChange={(e) => setRealData('proof_video', e.target.files ? e.target.files[0] : null)} />
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
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: '/transactions' },
        { title: 'Detail', href: '#' },
    ],
};
