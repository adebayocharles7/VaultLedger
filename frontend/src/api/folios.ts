import { apiClient } from './client';
import type { CreateFolioPayload, Folio, LedgerEntry } from './types';

// Laravel wraps single-resource responses as { data: Folio } by default
// when using an API Resource — hence the nested "data" here.
interface FolioResponse {
  data: Folio;
}
interface FolioListResponse {
  data: Folio[];
}
interface LedgerListResponse {
  data: LedgerEntry[];
}

export async function fetchFolios(): Promise<Folio[]> {
  const { data } = await apiClient.get<FolioListResponse>('/folios');
  return data.data;
}

export async function fetchFolio(id: string): Promise<Folio> {
  const { data } = await apiClient.get<FolioResponse>(`/folios/${id}`);
  return data.data;
}

export async function createFolio(payload: CreateFolioPayload): Promise<Folio> {
  const { data } = await apiClient.post<FolioResponse>('/folios', payload);
  return data.data;
}

export async function fetchFolioLedger(id: string): Promise<LedgerEntry[]> {
  const { data } = await apiClient.get<LedgerListResponse>(`/folios/${id}/ledger`);
  return data.data;
}

export async function fundFolio(
  id: string,
  amountMinor: number,
  idempotencyKey: string
): Promise<Folio> {
  const { data } = await apiClient.post<FolioResponse>(
    `/folios/${id}/fund`,
    { amount_minor: amountMinor },
    { headers: { 'Idempotency-Key': idempotencyKey } }
  );
  return data.data;
}
