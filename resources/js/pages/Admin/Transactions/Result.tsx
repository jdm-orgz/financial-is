import { Head, Link, useForm, router } from '@inertiajs/react';
import { ChevronLeft, CheckCircle, XCircle, Image as ImageIcon, PlayCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { ConfirmModal } from '@/components/confirm-modal';
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
import type { Transaction, VarianceItem } from '@/types/transaction';

interface ResultProps {
    transaction: Transaction;
    comparison: VarianceItem[];
}

export default function Result({ transaction, comparison }: ResultProps) {
    const isCompared = transaction.status === 'compared';
    const [isRejectOpen, setIsRejectOpen] = useState(false);

    // Media Modal State
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
        admin_notes: '',
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
            'Yakin ingin menyelesaikan transaksi ini? Status akan menjadi Selesai.',
            'Approve & Complete',
            'default',
            () => {
                router.post(`/admin/transactions/${transaction.id}/approve`, {}, {
                    preserveScroll: true,
                });
            }
        );
    };

    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        postReject(`/admin/transactions/${transaction.id}/reject`, {
            onSuccess: () => setIsRejectOpen(false),
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Comparison Result" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Transaction Comparison Result</h1>
                        <div className="text-sm text-muted-foreground mt-1 flex flex-col gap-0.5">
                            <p>Outlet: {transaction.outlet.name}</p>
                            <p>Date: {transaction.date.includes('T') ? transaction.date.substring(0, 10) : transaction.date}</p>
                            {transaction.date.includes('T') && (
                                <p>Time: {transaction.date.substring(11, 19)}</p>
                            )}
                            <p>Creator: {transaction.created_by?.name || '-'}</p>
                            <p>Supervisor: {transaction.supervisor_actioned_by?.name || '-'}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/admin/transactions">
                                <ChevronLeft className="mr-2 h-4 w-4" /> Back
                            </Link>
                        </Button>
                        {isCompared && (
                            <>
                                <Button variant="destructive" onClick={() => setIsRejectOpen(true)}>
                                    <XCircle className="mr-2 h-4 w-4" /> Reject (Correction)
                                </Button>
                                <Button onClick={handleApprove} className="bg-green-600 hover:bg-green-700 text-white">
                                    <CheckCircle className="mr-2 h-4 w-4" /> Approve & Complete
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Income Comparison Table</CardTitle>
                            <CardDescription>
                                Comparison between system input (adjusted for replacements) and SPG input.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Chair</TableHead>
                                            <TableHead className="text-right">System (Gross)</TableHead>
                                            <TableHead className="text-right text-red-600">Replacement (-)</TableHead>
                                            <TableHead className="text-right font-semibold">System (Net)</TableHead>
                                            <TableHead className="text-right">SPG (Deposit)</TableHead>
                                            <TableHead className="text-right font-bold">Variance</TableHead>
                                            <TableHead className="text-center">Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {comparison.map((item) => (
                                            <TableRow key={item.chair_id}>
                                                <TableCell className="font-medium">{item.chair_name}</TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    Rp {item.system_amount.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </TableCell>
                                                <TableCell className="text-right text-red-600">
                                                    {item.replacement_total > 0 ? `-Rp ${item.replacement_total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '-'}
                                                </TableCell>
                                                <TableCell className="text-right font-semibold">
                                                    Rp {item.system_adjusted.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    Rp {item.spg_amount.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </TableCell>
                                                <TableCell className={`text-right font-bold ${item.variance > 0 ? 'text-red-600' : (item.variance < 0 ? 'text-yellow-600' : 'text-green-600')}`}>
                                                    {item.variance > 0 ? '+' : ''}Rp {item.variance.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {item.status === 'ok' ? (
                                                        <Badge variant="default" className="bg-green-100 text-green-800 hover:bg-green-100">MATCH</Badge>
                                                    ) : (
                                                        <Badge variant="destructive">VARIANCE</Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            <div className="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 className="font-semibold mb-2">Replacement Realization (Detail)</h3>
                                    {transaction.replacement_realizations.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">No replacements.</p>
                                    ) : (
                                        <div className="rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Broken Chair</TableHead>
                                                        <TableHead>Replacement Chair</TableHead>
                                                        <TableHead className="text-right">Amount</TableHead>
                                                        <TableHead className="text-right">Proof</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {transaction.replacement_realizations.map(r => (
                                                        <TableRow key={r.id}>
                                                            <TableCell>{r.problem_chair.name}</TableCell>
                                                            <TableCell>{r.replacement_chair.name}</TableCell>
                                                            <TableCell className="text-right text-red-600">
                                                                -Rp {r.amount.toLocaleString('id-ID')}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                <div className="flex justify-end gap-2">
                                                                    {r.proof_image_path && (
                                                                        <Button 
                                                                            variant="ghost" 
                                                                            size="icon" 
                                                                            className="h-6 w-6 text-blue-600"
                                                                            onClick={() => {
                                                                                setMediaModalUrl(`/storage/${r.proof_image_path}`);
                                                                                setMediaModalType('image');
                                                                                setMediaModalOpen(true);
                                                                            }}
                                                                        >
                                                                            <ImageIcon className="h-4 w-4" />
                                                                        </Button>
                                                                    )}
                                                                    {r.proof_video_path && (
                                                                        <Button 
                                                                            variant="ghost" 
                                                                            size="icon" 
                                                                            className="h-6 w-6 text-blue-600"
                                                                            onClick={() => {
                                                                                setMediaModalUrl(`/storage/${r.proof_video_path}`);
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
                                                </TableBody>
                                            </Table>
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <h3 className="font-semibold mb-2">Transfer Proof (SPG)</h3>
                                    {transaction.transfer_proofs.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">No transfer proofs.</p>
                                    ) : (
                                        <div className="grid grid-cols-2 gap-4">
                                            {transaction.transfer_proofs.map(proof => (
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
                                </div>
                            </div>
                        </CardContent>
                    </Card>
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
                        <DialogDescription>Provide a note on why this transaction is returned (e.g., missing deposit).</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleReject}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="admin_notes">Admin Notes</Label>
                                <Textarea 
                                    id="admin_notes" 
                                    value={rejectData.admin_notes} 
                                    onChange={(e) => setRejectData('admin_notes', e.target.value)}
                                    placeholder="Example: Variance of 10,000 on chair A1 deposit."
                                    rows={4}
                                />
                                {errorsReject.admin_notes && <p className="text-xs text-destructive">{errorsReject.admin_notes}</p>}
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
                    if (!open) setConfirmState(prev => ({ ...prev, isOpen: false }));
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

Result.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '#' },
        { title: 'Pending Comparisons', href: '/admin/transactions' },
        { title: 'Comparison Result', href: '#' },
    ],
};
