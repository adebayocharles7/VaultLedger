import { apiClient } from './client';
import type { CreateRemittancePayload, Remittance } from './types';

interface RemittanceResponse {
  data: Remittance;
}

// Creating a remittance moves real money between two folios, so it gets
// the same idempotency treatment as fundFolio — the caller supplies the
// key so retries of the same submit don't create a second transfer.
export async function createRemittance(
  payload: CreateRemittancePayload,
  idempotencyKey: string
): Promise<Remittance> {
  const { data } = await apiClient.post<RemittanceResponse>(
    '/remittances',
    payload,
    { headers: { 'Idempotency-Key': idempotencyKey } }
  );
  return data.data;
}

export async function fetchRemittance(id: string): Promise<Remittance> {
  const { data } = await apiClient.get<RemittanceResponse>(`/remittances/${id}`);
  return data.data;
}

export async function reverseRemittance(
  id: string,
  idempotencyKey: string
): Promise<Remittance> {
  const { data } = await apiClient.post<RemittanceResponse>(
    `/remittances/${id}/reverse`,
    {},
    { headers: { 'Idempotency-Key': idempotencyKey } }
  );
  return data.data;
}
