# VaultLedger — Wallet & Ledger API

A production-grade, double-entry wallet backend built with **Laravel 12**.

VaultLedger models financial movement the way banks do — every debit has a matching credit, every operation is atomic, and no balance is ever computed from a mutable column alone.

---

## Architecture Highlights

| Concept | Implementation |
|---|---|
| **Double-entry ledger** | Every fund movement writes two `LedgerEntry` rows (DEBIT + CREDIT) |
| **Idempotency** | All mutation endpoints accept an `Idempotency-Key` header — safe to retry |
| **Optimistic concurrency guard** | Wallet uses a `version` column; stale writes are rejected with `409` |
| **Pessimistic locking** | `lockForUpdate()` on balance reads inside transactions |
| **Event-driven side effects** | `FolioPosted`, `RemittanceFailed` events dispatched after each operation |
| **Async notifications** | `NotifyWalletOwner` job queued on significant events |
| **Soft-delete audit trail** | Nothing is hard-deleted; every record is permanently auditable |

---

## Domain Language

| Term | Meaning |
|---|---|
| **Folio** | A wallet — belongs to a user, holds a balance in one currency |
| **Remittance** | A fund transfer between two Folios (or an external source/sink) |
| **LedgerEntry** | An individual debit or credit line — the atomic unit of accounting |
| **Posting** | The act of committing a Remittance and writing its LedgerEntries |

---

## Endpoints

```
POST   /api/v1/folios                    Create a wallet (Folio)
GET    /api/v1/folios/{folio}            Show wallet + current balance
POST   /api/v1/folios/{folio}/fund       Top up a wallet
POST   /api/v1/folios/{folio}/withdraw   Withdraw from a wallet
POST   /api/v1/remittances               Transfer between two wallets
GET    /api/v1/remittances/{remittance}  Get transfer details
GET    /api/v1/folios/{folio}/ledger     Paginated ledger entries
```

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Run tests:
```bash
php artisan test --coverage
```

---

## Design Decisions

**Why double-entry?**  
A single `balance` column is a liability — it can drift out of sync with actual movements. Double-entry means the balance is always provable: `SUM(credits) - SUM(debits)` on ledger entries must equal the stored balance. The `ReconcileWalletBalance` command verifies this.

**Why idempotency keys?**  
Network retries are a fact of life. Without idempotency, a client that retries a failed request risks double-charging. Keys are hashed and stored; duplicate requests return the original response.

**Why version-based optimistic locking?**  
Prevents two concurrent requests from reading the same balance and both succeeding when only one should. The `version` increment acts as a compare-and-swap.