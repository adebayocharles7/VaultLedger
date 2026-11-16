import { useRef } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import { fetchRemittance, reverseRemittance } from '../api/remittances';
import { generateIdempotencyKey } from '../lib/idempotency';
import type { Remittance, RemittanceStatus } from '../api/types';

function formatMoney(amountMinor: number, currency: string): string {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency }).format(
    amountMinor / 100
  );
}

const statusColor: Record<RemittanceStatus, string> = {
  PENDING: 'bg-blue-100 text-blue-800',
  POSTED: 'bg-green-100 text-green-800',
  REVERSED: 'bg-gray-200 text-gray-700',
  FAILED: 'bg-red-100 text-red-800',
};

export default function RemittanceDetail() {
  const { remittanceId } = useParams<{ remittanceId: string }>();
  const queryClient = useQueryClient();
  const idempotencyKeyRef = useRef(generateIdempotencyKey());

  const { data: remittance, isLoading } = useQuery<Remittance>({
    queryKey: ['remittance', remittanceId],
    queryFn: () => fetchRemittance(remittanceId!),
  });

  const reverseMutation = useMutation({
    mutationFn: () => reverseRemittance(remittanceId!, idempotencyKeyRef.current),
    onSuccess: () => {
      // The reversal changes this remittance's own status AND both
      // folios' balances, plus it creates a brand-new counter-remittance
      // server-side — so folios and this remittance both need refetching.
      queryClient.invalidateQueries({ queryKey: ['remittance', remittanceId] });
      queryClient.invalidateQueries({ queryKey: ['folios'] });
      idempotencyKeyRef.current = generateIdempotencyKey();
    },
  });

  if (isLoading || !remittance) return <p className="p-6">Loading…</p>;

  const canReverse = remittance.status === 'POSTED';

  return (
    <div className="max-w-md mx-auto mt-16 space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Remittance</h1>
        <span
          className={`text-xs px-2 py-1 rounded-full ${statusColor[remittance.status]}`}
        >
          {remittance.status}
        </span>
      </div>

      <div className="border rounded p-4 space-y-2">
        <p className="text-2xl">
          {formatMoney(remittance.amount_minor, remittance.currency)}
        </p>
        {remittance.description && (
          <p className="text-gray-600 text-sm">{remittance.description}</p>
        )}
        <p className="text-xs text-gray-500 break-all">
          From: {remittance.source_folio_id ?? '—'}
        </p>
        <p className="text-xs text-gray-500 break-all">
          To: {remittance.destination_folio_id ?? '—'}
        </p>
        {remittance.failed_reason && (
          <p className="text-red-600 text-sm">{remittance.failed_reason}</p>
        )}
      </div>

      {canReverse && (
        <button
          onClick={() => reverseMutation.mutate()}
          disabled={reverseMutation.isPending}
          className="w-full border border-red-600 text-red-600 rounded py-2 disabled:opacity-50"
        >
          {reverseMutation.isPending ? 'Reversing…' : 'Reverse this transfer'}
        </button>
      )}

      {remittance.status === 'REVERSED' && (
        <p className="text-sm text-gray-500">
          This transfer has already been reversed.
        </p>
      )}
    </div>
  );
}
