import { Head, Link, useForm, router } from '@inertiajs/react';
import { ChevronLeft, CheckCircle, XCircle } from 'lucide-react';
import { useState, type ChangeEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import type { Transaction } from '@/types/transaction';

interface ShowProps {
    readonly transaction: Transaction;
}

export default function Show({ transaction }: ShowProps) {
    const [isRejectOpen, setIsRejectOpen] = useState(false);

    const {
        data: rejectData,
        setData: setRejectData,
        post: postReject,
        processing: processingReject,
        errors: errorsReject,
    } = useForm({
        supervisor_notes: '',
    });

    const handleApprove = () => {
        if (confirm('Yakin ingin menyetujui transaksi ini? Data akan diteruskan ke Admin.')) {
            router.post(`/supervisor/transactions/${transaction.id}/approve`, {}, {
                preserveScroll: true,
            });
        }
    };

    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        postReject(`/supervisor/transactions/${transaction.id}/reject`, {
            onSuccess: () => setIsRejectOpen(false),
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Transaction Approval" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Review Transaction</h1>
                        <p className="text-sm text-muted-foreground">
                            {transaction.outlet.name} - {transaction.date} (By: {transaction.created_by.name})
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/supervisor/transactions">
                                <ChevronLeft className="mr-2 h-4 w-4" /> Back
                            </Link>
                        </Button>
                        {transaction.status === 'approval' && (
                            <>
                                <Button variant="destructive" onClick={() => setIsRejectOpen(true)}>
                                    <XCircle className="mr-2 h-4 w-4" /> Reject (Correction)
                                </Button>
                                <Button onClick={handleApprove} className="bg-green-600 hover:bg-green-700 text-white">
                                    <CheckCircle className="mr-2 h-4 w-4" /> Approve
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    {/* Daily Incomes Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Daily Incomes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Chair</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transaction.daily_incomes.map((di) => (
                                        <TableRow key={di.id}>
                                            <TableCell>{di.chair.name}</TableCell>
                                            <TableCell className="text-right">Rp {di.amount.toLocaleString('id-ID')}</TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className="font-bold">
                                        <TableCell>Total Income</TableCell>
                                        <TableCell className="text-right">
                                            Rp {transaction.daily_incomes.reduce((sum, di) => sum + di.amount, 0).toLocaleString('id-ID')}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        {/* Replacement Realizations */}
                        <Card>
                            <CardHeader>
                            <CardTitle>Replacement Realizations</CardTitle>
                        </CardHeader>
                            <CardContent>
                                {transaction.replacement_realizations.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">No replacement realizations.</p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Problem</TableHead>
                                                <TableHead>Replacement</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead className="text-right">Proof</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {transaction.replacement_realizations.map((real) => (
                                                <TableRow key={real.id}>
                                                    <TableCell>{real.problem_chair.name}</TableCell>
                                                    <TableCell>{real.replacement_chair.name}</TableCell>
                                                    <TableCell>Rp {real.amount.toLocaleString('id-ID')}</TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-2 text-xs">
                                                            {real.proof_image_path && <a href={`/storage/${real.proof_image_path}`} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">Photo</a>}
                                                            {real.proof_video_path && <a href={`/storage/${real.proof_video_path}`} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">Video</a>}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            <TableRow className="font-bold">
                                                <TableCell colSpan={2}>Total Realization</TableCell>
                                                <TableCell colSpan={2}>
                                                    Rp {transaction.replacement_realizations.reduce((sum, r) => sum + r.amount, 0).toLocaleString('id-ID')}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>

                        {/* Transfer Proofs */}
                        <Card>
                            <CardHeader>
                            <CardTitle>Transfer Proofs</CardTitle>
                        </CardHeader>
                            <CardContent>
                                {transaction.transfer_proofs.length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">No transfer proofs.</p>
                                ) : (
                                    <div className="grid grid-cols-2 gap-4">
                                        {transaction.transfer_proofs.map((proof) => (
                                            <a key={proof.id} href={`/storage/${proof.proof_image_path}`} target="_blank" rel="noreferrer">
                                                <img src={`/storage/${proof.proof_image_path}`} alt="Transfer Proof" className="w-full h-32 object-cover rounded border hover:opacity-80 transition-opacity" />
                                            </a>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <Dialog open={isRejectOpen} onOpenChange={setIsRejectOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reject Transaction (Correction)</DialogTitle>
                        <DialogDescription>Provide notes on why this transaction is rejected back to the SPG.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleReject}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="supervisor_notes">Supervisor Notes</Label>
                                <Textarea 
                                    id="supervisor_notes" 
                                    value={rejectData.supervisor_notes} 
                                    onChange={(e: ChangeEvent<HTMLTextAreaElement>) => setRejectData('supervisor_notes', e.target.value)}
                                    placeholder="Example: The amount on chair A1 is incorrect."
                                    rows={4}
                                />
                                {errorsReject.supervisor_notes && <p className="text-xs text-destructive">{errorsReject.supervisor_notes}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsRejectOpen(false)}>Cancel</Button>
                            <Button type="submit" variant="destructive" disabled={processingReject}>Send Correction</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Supervisor', href: '#' },
        { title: 'Transaction Approvals', href: '/supervisor/transactions' },
        { title: 'Detail', href: '#' },
    ],
};
