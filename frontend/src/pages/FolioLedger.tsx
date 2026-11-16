import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import { fetchFolio, fetchFolioLedger } from '../api/folios';
import type { Folio, LedgerEntry } from '../api/types';

function formatMoney(amountMinor: number, currency: string): string {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency }).format(
    amountMinor / 100
  );
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString('en-NG', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

export default function FolioLedger() {
  const { folioId } = useParams<{ folioId: string }>();

  const { data: folio } = useQuery<Folio>({
    queryKey: ['folio', folioId],
    queryFn: () => fetchFolio(folioId!),
  });

  // Separate query, separate cache key — the ledger can be refetched
  // independently of the folio's own summary data.
  const { data: entries, isLoading } = useQuery<LedgerEntry[]>({
    queryKey: ['folio', folioId, 'ledger'],
    queryFn: () => fetchFolioLedger(folioId!),
  });

  return (
    <div className="max-w-2xl mx-auto mt-10 space-y-4">
      <div>
        <h1 className="text-xl font-semibold">{folio?.label ?? 'Folio'} — Ledger</h1>
        {folio && (
          <p className="text-gray-500 text-sm">
            Current balance: {formatMoney(folio.balance_minor, folio.currency)}
          </p>
        )}
      </div>

      {isLoading && <p>Loading ledger…</p>}

      {/* Entries come back newest-first from the API. Each row is
          append-only server-side — there's no "edit" concept here,
          which is why there's no edit/delete UI at all. */}
      <div className="border rounded divide-y">
        {entries?.map((entry) => (
          <div key={entry.id} className="p-3 flex justify-between items-center">
            <div>
              <p className="text-sm">{entry.narration ?? '—'}</p>
              <p className="text-xs text-gray-400">{formatDate(entry.created_at)}</p>
            </div>
            <div className="text-right">
              <p
                className={
                  entry.type === 'credit' ? 'text-green-700' : 'text-red-700'
                }
              >
                {entry.type === 'credit' ? '+' : '-'}
                {formatMoney(entry.amount_minor, folio?.currency ?? 'NGN')}
              </p>
              <p className="text-xs text-gray-400">
                bal: {formatMoney(entry.running_balance_minor, folio?.currency ?? 'NGN')}
              </p>
            </div>
          </div>
        ))}
        {entries?.length === 0 && (
          <p className="p-3 text-sm text-gray-500">No activity yet.</p>
        )}
      </div>
    </div>
  );
}
