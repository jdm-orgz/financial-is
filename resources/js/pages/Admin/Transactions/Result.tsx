import { Head, Link, useForm, router } from '@inertiajs/react';
import { ChevronLeft, CheckCircle, XCircle } from 'lucide-react';
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

    const {
        data: rejectData,
        setData: setRejectData,
        post: postReject,
        processing: processingReject,
        errors: errorsReject,
    } = useForm({
        admin_notes: '',
    });

    const handleApprove = () => {
        if (confirm('Yakin ingin menyelesaikan transaksi ini? Status akan menjadi Selesai.')) {
            router.post(`/admin/transactions/${transaction.id}/approve`, {}, {
                preserveScroll: true,
            });
        }
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
                        <p className="text-sm text-muted-foreground">
                            {transaction.outlet.name} - {transaction.date}
                        </p>
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
                                            <TableHead>Kursi</TableHead>
                                            <TableHead className="text-right">Sistem (Kotor)</TableHead>
                                            <TableHead className="text-right text-red-600">Penggantian (-)</TableHead>
                                            <TableHead className="text-right font-semibold">Sistem (Bersih)</TableHead>
                                            <TableHead className="text-right">SPG (Setoran)</TableHead>
                                            <TableHead className="text-right font-bold">Selisih</TableHead>
                                            <TableHead className="text-center">Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {comparison.map((item) => (
                                            <TableRow key={item.chair_id}>
                                                <TableCell className="font-medium">{item.chair_name}</TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    Rp {item.system_amount.toLocaleString('id-ID')}
                                                </TableCell>
                                                <TableCell className="text-right text-red-600">
                                                    {item.replacement_total > 0 ? `-Rp ${item.replacement_total.toLocaleString('id-ID')}` : '-'}
                                                </TableCell>
                                                <TableCell className="text-right font-semibold">
                                                    Rp {item.system_adjusted.toLocaleString('id-ID')}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    Rp {item.spg_amount.toLocaleString('id-ID')}
                                                </TableCell>
                                                <TableCell className={`text-right font-bold ${item.variance > 0 ? 'text-red-600' : (item.variance < 0 ? 'text-yellow-600' : 'text-green-600')}`}>
                                                    {item.variance > 0 ? '+' : ''}Rp {item.variance.toLocaleString('id-ID')}
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
                                    <h3 className="font-semibold mb-2">Realisasi Pengganti (Detail)</h3>
                                    {transaction.replacement_realizations.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">Tidak ada penggantian.</p>
                                    ) : (
                                        <ul className="space-y-2 text-sm">
                                            {transaction.replacement_realizations.map(r => (
                                                <li key={r.id} className="flex justify-between border-b pb-1">
                                                    <span>{r.problem_chair.name} &rarr; {r.replacement_chair.name}</span>
                                                    <span className="text-red-600">-Rp {r.amount.toLocaleString('id-ID')}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                                <div>
                                    <h3 className="font-semibold mb-2">Bukti Transfer (SPG)</h3>
                                    <div className="flex gap-2 flex-wrap">
                                        {transaction.transfer_proofs.map(proof => (
                                            <a key={proof.id} href={`/storage/${proof.proof_image_path}`} target="_blank" rel="noreferrer">
                                                <img src={`/storage/${proof.proof_image_path}`} className="h-20 w-20 object-cover rounded border hover:opacity-80 transition" />
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

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
