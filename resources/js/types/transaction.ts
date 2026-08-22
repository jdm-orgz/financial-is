export type TransactionStatus =
    | 'draft'
    | 'approval'
    | 'correction'
    | 'comparing'
    | 'compared'
    | 'done';

export type PaymentMethod = 'cash' | 'qris';

export interface Transaction {
    id: number;
    outlet: { id: number; name: string };
    date: string;
    status: TransactionStatus;
    spg_notes: string | null;
    supervisor_notes: string | null;
    admin_notes: string | null;
    created_by: { id: number; name: string };
    supervisor_actioned_by: { id: number; name: string } | null;
    supervisor_actioned_at: string | null;
    admin_actioned_by: { id: number; name: string } | null;
    admin_actioned_at: string | null;
    daily_incomes: TransactionDailyIncome[];
    replacement_realizations: TransactionReplacementRealization[];
    transfer_proofs: TransactionTransferProof[];
    system_incomes: TransactionSystemIncome[];
}

export interface TransactionDailyIncome {
    id: number;
    chair: { id: number; name: string };
    amount: number;
}

export interface TransactionReplacementRealization {
    id: number;
    problem_chair: { id: number; name: string };
    replacement_chair: { id: number; name: string };
    payment_method: PaymentMethod;
    amount: number;
    proof_image_path: string | null;
    proof_video_path: string | null;
}

export interface TransactionTransferProof {
    id: number;
    proof_image_path: string;
}

export interface TransactionSystemIncome {
    id: number;
    chair: { id: number; name: string };
    amount: number;
}

export interface VarianceItem {
    chair_id: string;
    chair_name: string;
    system_amount: number;
    replacement_total: number;
    system_adjusted: number;
    spg_amount: number;
    variance: number;
    status: 'ok' | 'warning';
}

export interface StatusOption {
    label: string;
    value: string;
}
