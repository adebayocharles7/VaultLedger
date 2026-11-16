// These mirror your Laravel Enums and API Resources exactly.
// Keeping them 1:1 with the backend means a mismatch shows up as a
// TypeScript error, not a runtime bug discovered by a recruiter.

export type FolioStatus = 'ACTIVE' | 'FROZEN' | 'CLOSED';

export type RemittanceStatus = 'PENDING' | 'POSTED' | 'REVERSED' | 'FAILED';

export type LedgerEntryType = 'debit' | 'credit';

export interface User {
  id: string;
  name: string;
  email: string;
}

export interface Folio {
  id: string;
  currency: string;
  label: string;
  balance_minor: number;
  version: number;
  status: FolioStatus;
}

export interface Remittance {
  id: string;
  source_folio_id: string | null;
  destination_folio_id: string | null;
  amount_minor: number;
  currency: string;
  status: RemittanceStatus;
  description: string | null;
  posted_at: string | null;
  failed_reason: string | null;
}

export interface LedgerEntry {
  id: string;
  folio_id: string;
  remittance_id: string;
  type: LedgerEntryType;
  amount_minor: number;
  running_balance_minor: number;
  narration: string | null;
  created_at: string;
}

// Shapes for request bodies — these match your FormRequest classes
export interface CreateFolioPayload {
  currency: string;
  label: string;
}

export interface CreateRemittancePayload {
  source_folio_id: string;
  destination_folio_id: string;
  amount_minor: number;
  description?: string;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

// Laravel's default validation failure response shape (422).
// e.g. { message: "The email has already been taken.",
//        errors: { email: ["The email has already been taken."] } }
export interface ValidationErrorResponse {
  message: string;
  errors: Record<string, string[]>;
}
