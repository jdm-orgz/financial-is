import { Head, Link, useForm, router } from '@inertiajs/react';
import { ChevronLeft, CheckCircle, XCircle, PlayCircle, Image as ImageIcon } from 'lucide-react';
import { useState  } from 'react';
import type {ChangeEvent} from 'react';
import { ConfirmModal } from '@/components/confirm-modal';
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

    const [mediaModalOpen, setMediaModalOpen] = useState(false);
    const [mediaModalUrl, setMediaModalUrl] = useState('');
    const [mediaModalType, setMediaModalType] = useState<'image' | 'video'>('video');

    const {
        data: rejectData,
        setData: setRejectData,
        post: postReject,
        processing: processingReject,
        errors: errorsReject,
    } = useForm({
        supervisor_notes: '',
    });

    const [confirmState, setConfirmState] = useState<{
        isOpen: boolean;
        title: string;
        description: string;
        confirmText: string;
        confirmVariant: 'default' | 'destructive' | 'secondary' | 'outline';
        action: () => void;
    }>({
        isOpen: false,
        title: '',
        description: '',
        confirmText: '',
        confirmVariant: 'default',
        action: () => {},
    });

    const openConfirm = (title: string, description: string, confirmText: string, confirmVariant: any, action: () => void) => {
        setConfirmState({ isOpen: true, title, description, confirmText, confirmVariant, action });
    };

    const handleApprove = () => {
        openConfirm(
            'Approve Transaction',
            'Yakin ingin menyetujui transaksi ini? Data akan diteruskan ke Admin.',
            'Approve',
            'default',
            () => {
                router.post(`/supervisor/transactions/${transaction.id}/approve`, {}, {
                    preserveScroll: true,
                });
            }
        );
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
                        <div className="text-sm text-muted-foreground mt-1 flex flex-col gap-0.5">
                            <p>Outlet: {transaction.outlet.name}</p>
                            <p>Date: {transaction.date.includes('T') ? transaction.date.substring(0, 10) : transaction.date}</p>
                            {transaction.date.includes('T') && (
                                <p>Time: {transaction.date.substring(11, 19)} (WIB)</p>
                            )}
                            <p>Creator: {transaction.created_by.name}</p>
                        </div>
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
                                            <TableCell className="text-right">Rp {Number(di.amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className="font-bold">
                                        <TableCell>Total Income</TableCell>
                                        <TableCell className="text-right">
                                            Rp {transaction.daily_incomes.reduce((sum, di) => sum + Number(di.amount), 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
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
                                                    <TableCell>Rp {Number(real.amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</TableCell>
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
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            <TableRow className="font-bold">
                                                <TableCell colSpan={2}>Total Realization</TableCell>
                                                <TableCell colSpan={2}>
                                                    Rp {transaction.replacement_realizations.reduce((sum, r) => sum + Number(r.amount), 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
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
                                            <div 
                                                key={proof.id} 
                                                className="cursor-pointer"
                                                onClick={() => {
                                                    setMediaModalUrl(`/storage/${proof.proof_image_path}`);
                                                    setMediaModalType('image');
                                                    setMediaModalOpen(true);
                                                }}
                                            >
                                                <img src={`/storage/${proof.proof_image_path}`} alt="Transfer Proof" className="w-full h-32 object-cover rounded border hover:opacity-80 transition-opacity" />
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

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

            <ConfirmModal
                isOpen={confirmState.isOpen}
                onOpenChange={(open) => {
                    if (!open) {
setConfirmState(prev => ({ ...prev, isOpen: false }));
}
                }}
                onConfirm={() => {
                    setConfirmState(prev => ({ ...prev, isOpen: false }));
                    confirmState.action();
                }}
                title={confirmState.title}
                description={confirmState.description}
                confirmText={confirmState.confirmText}
                confirmVariant={confirmState.confirmVariant}
            />
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
