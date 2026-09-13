# Implementation Plan: Transaction Module

Dokumen ini adalah rencana implementasi teknis dari modul transaksi rekonsiliasi setoran tunai. Mengikuti pola arsitektur yang sudah ada di project.

---

## Arsitektur Referensi

| Layer | Pattern | Lokasi Contoh |
|---|---|---|
| **Database** | UUID, SoftDeletes, Fillable attribute | `create_outlets_table.php` |
| **Domain Model** | `App\Domain\{Domain}\Models\` | `Outlet.php` |
| **Repository Interface** | `App\Domain\{Domain}\Repositories\{Name}RepositoryInterface.php` | `OutletRepositoryInterface.php` |
| **Eloquent Repository** | `App\Domain\{Domain}\Repositories\Eloquent{Name}Repository.php` | `EloquentOutletRepository.php` |
| **Controller** | `App\Http\Controllers\{Group}\{Name}Controller.php`, inject repo via constructor | `ChairController.php` |
| **Form Request** | `App\Http\Requests\{Group}\{Resource}\{Action}Request.php` | `Master/Chair/StoreChairRequest.php` |
| **Enum** | `App\Enums\`, implements `EnumOptions` trait | `FileUploadModule.php` |
| **Route** | `routes/web.php`, grouped by middleware permission | `web.php` |
| **Frontend Page** | `resources/js/pages/{Group}/{Name}.tsx` | `Chairs/Index.tsx` |
| **Toast** | `Inertia::flash('toast', [...])` | `ChairController.php` |
| **ID Encryption** | `Crypt::decryptString()` + `EncryptsId` trait | `ChairController.php` |

---

## Urutan Implementasi

```
[1] Enum
  └─> [2] Migration
        └─> [3] Domain Model
              └─> [4] Repository Interface + Implementation
                    └─> [5] Form Request
                          └─> [6] Controller
                                └─> [7] Route
                                      └─> [8] Frontend Page
```

---

## Fase 1: Enums

### [NEW] `app/Enums/TransactionStatus.php`
```
Enum: TransactionStatus (string-backed)
Cases: Draft, Approval, Correction, Comparing, Compared, Done
Trait: EnumOptions
Method: label() → label yang human-readable (misal: 'Draft', 'Menunggu Persetujuan', dll)
```

### [NEW] `app/Enums/PaymentMethod.php`
```
Enum: PaymentMethod (string-backed)
Cases: Cash = 'cash', QRIS = 'qris'
Trait: EnumOptions
Method: label() → 'Cash', 'QRIS'
```

---

## Fase 2: Migrations

Dibuat dengan `php artisan make:migration`. Urutan penting karena FK dependency.

### [NEW] Migration 1: `create_transactions_table`
```
Kolom:
- id (uuid, primary)
- outlet_id (uuid, FK → outlets, cascadeOnDelete)
- date (date)
- status (string, default: 'draft') → dicasting ke TransactionStatus enum di model
- spg_notes (text, nullable)
- supervisor_notes (text, nullable)
- admin_notes (text, nullable)
- created_by (uuid, FK → users, cascadeOnDelete)
- supervisor_actioned_by (uuid, nullable, FK → users, nullOnDelete)
- supervisor_actioned_at (timestamp, nullable)
- admin_actioned_by (uuid, nullable, FK → users, nullOnDelete)
- admin_actioned_at (timestamp, nullable)
- timestamps()
- softDeletes()

Unique Index: ['outlet_id', 'date']
```

### [NEW] Migration 2: `create_transaction_daily_incomes_table`
```
Kolom:
- id (uuid, primary)
- transaction_id (uuid, FK → transactions, cascadeOnDelete)
- chair_id (uuid, FK → chairs, cascadeOnDelete)
- amount (decimal, 15, 2)
- timestamps()

Unique Index: ['transaction_id', 'chair_id']
```

### [NEW] Migration 3: `create_transaction_replacement_realizations_table`
```
Kolom:
- id (uuid, primary)
- transaction_id (uuid, FK → transactions, cascadeOnDelete)
- problem_chair_id (uuid, FK → chairs, cascadeOnDelete)
- replacement_chair_id (uuid, FK → chairs, cascadeOnDelete)
- payment_method (string) → dicasting ke PaymentMethod enum di model
- amount (decimal, 15, 2)
- proof_image_path (string, nullable) → wajib jika payment_method = qris
- proof_video_path (string, nullable) → wajib semua metode
- timestamps()
```

### [NEW] Migration 4: `create_transaction_transfer_proofs_table`
```
Kolom:
- id (uuid, primary)
- transaction_id (uuid, FK → transactions, cascadeOnDelete)
- proof_image_path (string)
- timestamps()
```

### [NEW] Migration 5: `create_transaction_system_incomes_table`
```
Kolom:
- id (uuid, primary)
- transaction_id (uuid, FK → transactions, cascadeOnDelete)
- chair_id (uuid, FK → chairs, cascadeOnDelete)
- amount (decimal, 15, 2)
- timestamps()

Unique Index: ['transaction_id', 'chair_id']
```

---

## Fase 3: Domain Models

Semua model masuk ke domain baru `App\Domain\Transaction\Models\`.

### [NEW] `app/Domain/Transaction/Models/Transaction.php`
```
Namespace: App\Domain\Transaction\Models
Traits: HasUuids, SoftDeletes, EncryptsId, HasFactory
Fillable attribute: outlet_id, date, status, spg_notes, supervisor_notes, admin_notes,
                    created_by, supervisor_actioned_by, supervisor_actioned_at,
                    admin_actioned_by, admin_actioned_at

Casts:
- status → TransactionStatus::class
- supervisor_actioned_at → datetime
- admin_actioned_at → datetime
- date → date

Relations:
- outlet()         → belongsTo(Outlet)
- createdBy()      → belongsTo(User, 'created_by')
- supervisorActionedBy() → belongsTo(User, 'supervisor_actioned_by')
- adminActionedBy()      → belongsTo(User, 'admin_actioned_by')
- dailyIncomes()         → hasMany(TransactionDailyIncome)
- replacementRealizations() → hasMany(TransactionReplacementRealization)
- transferProofs()       → hasMany(TransactionTransferProof)
- systemIncomes()        → hasMany(TransactionSystemIncome)
```

### [NEW] `app/Domain/Transaction/Models/TransactionDailyIncome.php`
```
Traits: HasUuids
Fillable: transaction_id, chair_id, amount
Casts: amount → decimal:2
Relations:
- transaction() → belongsTo(Transaction)
- chair()       → belongsTo(Chair)
```

### [NEW] `app/Domain/Transaction/Models/TransactionReplacementRealization.php`
```
Traits: HasUuids
Fillable: transaction_id, problem_chair_id, replacement_chair_id, payment_method,
          amount, proof_image_path, proof_video_path
Casts:
- payment_method → PaymentMethod::class
- amount → decimal:2
Relations:
- transaction()       → belongsTo(Transaction)
- problemChair()      → belongsTo(Chair, 'problem_chair_id')
- replacementChair()  → belongsTo(Chair, 'replacement_chair_id')
```

### [NEW] `app/Domain/Transaction/Models/TransactionTransferProof.php`
```
Traits: HasUuids
Fillable: transaction_id, proof_image_path
Relations:
- transaction() → belongsTo(Transaction)
```

### [NEW] `app/Domain/Transaction/Models/TransactionSystemIncome.php`
```
Traits: HasUuids
Fillable: transaction_id, chair_id, amount
Casts: amount → decimal:2
Relations:
- transaction() → belongsTo(Transaction)
- chair()       → belongsTo(Chair)
```

---

## Fase 4: Repositories

Semua repository di `App\Domain\Transaction\Repositories\`.

### [NEW] `TransactionRepositoryInterface.php`
```php
Methods:
- getPaginatedForSpg(string $spgUserId, int $perPage, ?string $search, ?string $status): LengthAwarePaginator
- getPaginatedForSupervisor(string $supervisorId, int $perPage, ?string $status): LengthAwarePaginator
- getPaginatedForAdmin(int $perPage, ?string $status): LengthAwarePaginator
- findById(string $id): ?Transaction
- create(array $data): Transaction
- updateStatus(string $id, TransactionStatus $status, array $extra = []): bool
- existsForOutletAndDate(string $outletId, string $date, ?string $excludeId = null): bool
```

### [NEW] `EloquentTransactionRepository.php`
```
Implementasi dari interface di atas.
- getPaginatedForSpg: filter where('created_by', $spgUserId)
- getPaginatedForSupervisor: join linked_outlets_users, filter outlet yang di-link ke supervisor
- getPaginatedForAdmin: filter status = 'comparing' atau 'compared' atau 'done' (sesuai kebutuhan)
- findById: with(['outlet', 'dailyIncomes.chair', 'replacementRealizations.problemChair',
             'replacementRealizations.replacementChair', 'transferProofs', 'systemIncomes.chair',
             'createdBy', 'supervisorActionedBy', 'adminActionedBy'])
- updateStatus: update kolom status + kolom audit (actioned_by, actioned_at) sesuai role
- existsForOutletAndDate: cek unique constraint sebelum create
```

### [NEW] `TransactionDailyIncomeRepositoryInterface.php` & `EloquentTransactionDailyIncomeRepository.php`
```
Methods: upsertForTransaction(string $transactionId, array $items): void
         deleteByTransactionId(string $transactionId): void
         findByTransactionId(string $transactionId): Collection
```

### [NEW] `TransactionReplacementRealizationRepositoryInterface.php` & `EloquentTransactionReplacementRealizationRepository.php`
```
Methods: create(array $data): TransactionReplacementRealization
         update(string $id, array $data): bool
         delete(string $id): bool
         findByTransactionId(string $transactionId): Collection
```

### [NEW] `TransactionTransferProofRepositoryInterface.php` & `EloquentTransactionTransferProofRepository.php`
```
Methods: create(array $data): TransactionTransferProof
         delete(string $id): bool
         findByTransactionId(string $transactionId): Collection
```

### [NEW] `TransactionSystemIncomeRepositoryInterface.php` & `EloquentTransactionSystemIncomeRepository.php`
```
Methods: upsertForTransaction(string $transactionId, array $items): void
         findByTransactionId(string $transactionId): Collection
```

---

## Fase 5: Form Requests

Masuk ke `app/Http/Requests/Transaction/`.

| File | Validasi Utama |
|---|---|
| `StoreTransactionRequest` | outlet_id (exists, encrypted), date (required, date, setelah decrypt: unique per outlet) |
| `StoreTransactionDailyIncomeRequest` | `incomes` array, tiap item: chair_id (exists, milik outlet), amount (numeric, min:0) |
| `StoreTransactionReplacementRealizationRequest` | problem_chair_id, replacement_chair_id, payment_method (enum), amount (multiple of 5000), proof_image (required_if:payment_method=qris), proof_video (required) |
| `StoreTransactionTransferProofRequest` | proof_image (required, image, max size dari config) |
| `SubmitTransactionRequest` | Tidak ada field — hanya trigger validasi logic di controller |
| `StoreTransactionSystemIncomeRequest` | `system_incomes` array, tiap item: chair_id, amount (numeric, min:0) |
| `UpdateTransactionReplacementRealizationRequest` | Sama dengan Store, semua optional |

---

## Fase 6: Controllers

Masuk ke `app/Http/Controllers/Transaction/`.

### [NEW] `TransactionController.php` (SPG)
```
Constructor: inject TransactionRepositoryInterface, ChairRepositoryInterface, OutletRepositoryInterface

Methods:
- index()   → GET /transactions
             Inertia::render('Transactions/Index', [...])
             Data: list transaksi milik auth()->id() yang terpaginasi

- create()  → GET /transactions/create
             Inertia::render('Transactions/Create', ['outlets' => ...])
             Data: outlets yang di-link ke SPG (auth user)

- store()   → POST /transactions
             Validate → cek unique → create → flash toast → redirect index

- show()    → GET /transactions/{transaction}
             Inertia::render('Transactions/Show', [...])
             Data: transaction with all relations, chairs outlet tersebut

- submit()  → POST /transactions/{transaction}/submit
             Validasi: status harus draft/correction, semua kursi terisi, ada transfer proof
             → updateStatus(Approval) → flash → redirect

- destroy() → DELETE /transactions/{transaction}
             Hanya bisa jika status draft
             → delete → flash → redirect
```

### [NEW] `TransactionDailyIncomeController.php` (SPG)
```
Methods:
- upsert() → POST /transactions/{transaction}/daily-incomes
             Validasi status draft/correction
             → upsertForTransaction() → flash → redirect back
```

### [NEW] `TransactionReplacementRealizationController.php` (SPG)
```
Methods:
- store()   → POST /transactions/{transaction}/replacement-realizations
             Validasi status draft/correction
             Upload file (naming: {txid}_failed_{timestamp}, {txid}_success_{timestamp})
             → create → flash → redirect back

- update()  → PUT /transactions/{transaction}/replacement-realizations/{realization}
             Validasi status draft/correction
             → update (termasuk replace file jika ada upload baru) → flash → redirect back

- destroy() → DELETE /transactions/{transaction}/replacement-realizations/{realization}
             Validasi status draft/correction
             → hapus file dari storage → delete record → flash → redirect back
```

### [NEW] `TransactionTransferProofController.php` (SPG)
```
Methods:
- store()   → POST /transactions/{transaction}/transfer-proofs
             Validasi status draft/correction
             Upload file (naming: {txid}_transfer-proof)
             → create → flash → redirect back

- destroy() → DELETE /transactions/{transaction}/transfer-proofs/{proof}
             Validasi status draft/correction
             → hapus file dari storage → delete record → flash → redirect back
```

### [NEW] `SupervisorTransactionController.php` (Supervisor)
```
Methods:
- index()   → GET /supervisor/transactions
             Filter: status = 'approval', outlet yang di-link ke supervisor
             Inertia::render('Supervisor/Transactions/Index', [...])

- show()    → GET /supervisor/transactions/{transaction}
             Read-only + semua relasi
             Inertia::render('Supervisor/Transactions/Show', [...])

- approve() → POST /supervisor/transactions/{transaction}/approve
             Validasi: status harus 'approval'
             → updateStatus(Comparing, supervisor_actioned_by, supervisor_actioned_at) → flash → redirect

- reject()  → POST /supervisor/transactions/{transaction}/reject
             Validasi: status harus 'approval', supervisor_notes required
             → updateStatus(Correction, + supervisor_notes) → flash → redirect
```

### [NEW] `AdminTransactionController.php` (Admin)
```
Methods:
- index()   → GET /admin/transactions
             Filter: status = 'comparing', semua outlet
             Inertia::render('Admin/Transactions/Index', [...])

- showCompare() → GET /admin/transactions/{transaction}/compare
             Hanya tampilkan chair list + form system income (TANPA data SPG)
             Inertia::render('Admin/Transactions/Compare', ['chairs' => ...])

- storeSystemIncome() → POST /admin/transactions/{transaction}/system-incomes
             Validasi status harus 'comparing'
             → upsertForTransaction → updateStatus(Compared, admin_actioned_by, admin_actioned_at)
             → jalankan kalkulasi selisih → flash → redirect result

- showResult() → GET /admin/transactions/{transaction}/result
             Tampilkan data SPG + data Admin + selisih per kursi
             Inertia::render('Admin/Transactions/Result', ['comparison' => ...])

- approve() → POST /admin/transactions/{transaction}/approve
             Validasi status harus 'compared'
             → updateStatus(Done) → flash → redirect

- reject()  → POST /admin/transactions/{transaction}/reject
             Validasi status harus 'compared', admin_notes required
             → updateStatus(Correction, + admin_notes) → flash → redirect

- all()     → GET /admin/transactions/all
             Semua transaksi semua status (untuk Super Admin juga)
             Inertia::render('Admin/Transactions/All', [...])
```

---

## Fase 7: Logika Kalkulasi Selisih

Dibuat sebagai class terpisah agar bisa di-test secara unit.

### [NEW] `app/Domain/Transaction/Actions/CalculateVarianceAction.php`
```php
/**
 * Input: Transaction (with all relations loaded)
 * Output: array per chair_id berisi:
 * [
 *   'chair_id'           => '...',
 *   'chair_name'         => '...',
 *   'system_amount'      => 50000,
 *   'replacement_total'  => 10000,  // sum realizations where problem_chair_id = chair_id
 *   'system_adjusted'    => 40000,  // system_amount - replacement_total
 *   'spg_amount'         => 40000,
 *   'variance'           => 0,      // system_adjusted - spg_amount
 *   'status'             => 'ok',   // 'ok' | 'warning'
 * ]
 */
public function execute(Transaction $transaction): array
```

Dipanggil di `AdminTransactionController::storeSystemIncome()` setelah status diubah ke `compared`. Hasilnya bisa disimpan sebagai JSON di kolom `variance_result` di tabel `transactions` (opsional untuk performa) **atau** dihitung on-the-fly di `showResult()`.

> [!NOTE]
> Rekomendasi: hitung on-the-fly di `showResult()` saja (tidak perlu kolom tambahan). Kalau ada kebutuhan performa di masa depan, bisa ditambahkan kolom JSON `variance_snapshot`.

---

## Fase 8: Routes

Ditambahkan ke `routes/web.php` (atau file baru `routes/transaction.php` yang di-require).

```php
// SPG Routes
Route::middleware(['auth', 'verified', 'permission:transaction/*,*'])->group(function () {
    Route::resource('transactions', TransactionController::class)->except(['edit', 'update']);
    Route::post('transactions/{transaction}/submit', [TransactionController::class, 'submit'])
         ->name('transactions.submit');
    Route::post('transactions/{transaction}/daily-incomes', [TransactionDailyIncomeController::class, 'upsert'])
         ->name('transactions.daily-incomes.upsert');
    Route::resource('transactions.replacement-realizations', TransactionReplacementRealizationController::class)
         ->except(['index', 'show', 'edit']);
    Route::resource('transactions.transfer-proofs', TransactionTransferProofController::class)
         ->only(['store', 'destroy']);
});

// Supervisor Routes
Route::middleware(['auth', 'verified', 'permission:supervisor/*,*'])->group(function () {
    Route::get('supervisor/transactions', [SupervisorTransactionController::class, 'index'])
         ->name('supervisor.transactions.index');
    Route::get('supervisor/transactions/{transaction}', [SupervisorTransactionController::class, 'show'])
         ->name('supervisor.transactions.show');
    Route::post('supervisor/transactions/{transaction}/approve', [SupervisorTransactionController::class, 'approve'])
         ->name('supervisor.transactions.approve');
    Route::post('supervisor/transactions/{transaction}/reject', [SupervisorTransactionController::class, 'reject'])
         ->name('supervisor.transactions.reject');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'permission:admin/*,*'])->group(function () {
    Route::get('admin/transactions', [AdminTransactionController::class, 'index'])
         ->name('admin.transactions.index');
    Route::get('admin/transactions/all', [AdminTransactionController::class, 'all'])
         ->name('admin.transactions.all');
    Route::get('admin/transactions/{transaction}/compare', [AdminTransactionController::class, 'showCompare'])
         ->name('admin.transactions.compare');
    Route::post('admin/transactions/{transaction}/system-incomes', [AdminTransactionController::class, 'storeSystemIncome'])
         ->name('admin.transactions.system-incomes.store');
    Route::get('admin/transactions/{transaction}/result', [AdminTransactionController::class, 'showResult'])
         ->name('admin.transactions.result');
    Route::post('admin/transactions/{transaction}/approve', [AdminTransactionController::class, 'approve'])
         ->name('admin.transactions.approve');
    Route::post('admin/transactions/{transaction}/reject', [AdminTransactionController::class, 'reject'])
         ->name('admin.transactions.reject');
});
```

---

## Fase 9: Frontend Pages

Menggunakan pola yang sama dengan `Chairs/Index.tsx`: TypeScript interface, Inertia props, Dialog/Modal untuk sub-form, Shadcn/UI components.

### SPG

| File | Deskripsi |
|---|---|
| `resources/js/pages/Transactions/Index.tsx` | Tabel list transaksi milik SPG. Kolom: Outlet, Tanggal, Status badge, Aksi. Filter by status. |
| `resources/js/pages/Transactions/Create.tsx` | Form create: dropdown outlet (yang di-link ke SPG) + date picker. |
| `resources/js/pages/Transactions/Show.tsx` | Halaman utama SPG. Terdiri dari 3 section/card: Daily Income, Realisasi Pengganti, Bukti Transfer. Setiap section ada tombol tambah (Modal). Tombol Submit di bawah. |

**Modal yang dibutuhkan di `Show.tsx`:**
- `DailyIncomeModal` — form grid semua kursi outlet dengan input nominal.
- `ReplacementRealizationModal` — form tambah/edit realisasi pengganti (dynamic upload berdasarkan metode).
- `TransferProofModal` — form upload foto bukti transfer.

### Supervisor

| File | Deskripsi |
|---|---|
| `resources/js/pages/Supervisor/Transactions/Index.tsx` | List transaksi status `approval`. Filter outlet. |
| `resources/js/pages/Supervisor/Transactions/Show.tsx` | Read-only detail lengkap. Tombol Approve & Reject (Reject: modal dengan input catatan). |

### Admin

| File | Deskripsi |
|---|---|
| `resources/js/pages/Admin/Transactions/Index.tsx` | List transaksi status `comparing`. |
| `resources/js/pages/Admin/Transactions/Compare.tsx` | Form input system income per kursi. Tidak tampilkan data SPG. |
| `resources/js/pages/Admin/Transactions/Result.tsx` | Tampilkan tabel perbandingan per kursi: System Amount, Replacement Total, Adjusted, SPG Amount, Variance. Row warning jika variance > 0. Tombol Approve & Reject. |
| `resources/js/pages/Admin/Transactions/All.tsx` | Semua transaksi semua status. Dapat diakses Super Admin juga. |

---

## Fase 10: TypeScript Types

### [NEW] `resources/js/types/transaction.ts`
```typescript
export type TransactionStatus =
    | 'draft' | 'approval' | 'correction'
    | 'comparing' | 'compared' | 'done';

export type PaymentMethod = 'cash' | 'qris';

export interface Transaction {
    id: number; // encrypted
    outlet: { id: number; name: string };
    date: string;
    status: TransactionStatus;
    spg_notes: string | null;
    supervisor_notes: string | null;
    admin_notes: string | null;
    created_by: { id: number; name: string };
    supervisor_actioned_at: string | null;
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
```

---

## Fase 11: Testing

Buat test dengan `php artisan make:test Transaction/{Name}Test --phpunit`.

| Test File | Coverage |
|---|---|
| `tests/Feature/Transaction/CreateTransactionTest.php` | Buat transaksi, unique per outlet+date, validasi outlet milik SPG |
| `tests/Feature/Transaction/TransactionStatusFlowTest.php` | Seluruh happy path: draft→approval→comparing→compared→done |
| `tests/Feature/Transaction/SupervisorApproveRejectTest.php` | Approve/reject, akses hanya outlet yang di-link |
| `tests/Feature/Transaction/AdminCompareTest.php` | Input system income, kalkulasi variance, approve/reject final |
| `tests/Unit/Transaction/CalculateVarianceActionTest.php` | Unit test logika kalkulasi selisih per kursi |
| `tests/Feature/Transaction/FileUploadTest.php` | Upload proof, naming convention, validasi QRIS wajib foto |

---

## Checklist Implementasi

- [ ] **Fase 1** — Enums: `TransactionStatus`, `PaymentMethod`
- [ ] **Fase 2** — 5 Migrations
- [ ] **Fase 3** — 5 Domain Models + Factories
- [ ] **Fase 4** — 5 Repository Interfaces + Implementations
- [ ] **Fase 5** — Form Requests (7 files)
- [ ] **Fase 6** — 4 Controllers
- [ ] **Fase 7** — `CalculateVarianceAction`
- [ ] **Fase 8** — Routes (tambah ke `web.php` atau file terpisah)
- [ ] **Fase 9** — 8 Frontend Pages + 3 Modals
- [ ] **Fase 10** — TypeScript types
- [ ] **Fase 11** — 6 Test files
- [ ] Run `vendor/bin/pint --dirty` setelah setiap fase PHP
- [ ] Run `php artisan test --compact` setelah semua fase

---

> [!IMPORTANT]
> **Tunggu approval sebelum implementasi.** Konfirmasikan dokumen ini, lalu saya akan mulai dari Fase 1 (Enums) secara berurutan.
