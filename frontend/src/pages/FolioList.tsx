import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { fetchFolios } from '../api/folios';
import type { Folio, FolioStatus } from '../api/types';

// Amounts come from Laravel in minor units (kobo), matching the backend's
// "never store floats" rule. Convert only at the display boundary.
function formatMoney(amountMinor: number, currency: string): string {
  const major = amountMinor / 100;
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency,
  }).format(major);
}

const statusColor: Record<FolioStatus, string> = {
  ACTIVE: 'bg-green-100 text-green-800',
  FROZEN: 'bg-yellow-100 text-yellow-800',
  CLOSED: 'bg-gray-200 text-gray-700',
};

export default function FolioList() {
  const { data: folios, isLoading, error } = useQuery<Folio[]>({
    queryKey: ['folios'],
    queryFn: fetchFolios,
  });

  if (isLoading) return <p className="p-6">Loading folios…</p>;
  if (error) return <p className="p-6 text-red-600">Failed to load folios.</p>;

  return (
    <div className="max-w-2xl mx-auto mt-10 space-y-3">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Your Folios</h1>
        <Link to="/remittances/new" className="text-sm underline">
          Send money
        </Link>
      </div>
      {folios?.map((folio) => (
        <div
          key={folio.id}
          className="border rounded p-4 flex justify-between items-center"
        >
          <div>
            <p className="font-medium">{folio.label}</p>
            <p className="text-2xl">
              {formatMoney(folio.balance_minor, folio.currency)}
            </p>
          </div>
          <div className="flex items-center gap-3">
            <span
              className={`text-xs px-2 py-1 rounded-full ${statusColor[folio.status]}`}
            >
              {folio.status}
            </span>
            <Link
              to={`/folios/${folio.id}/ledger`}
              className="text-sm underline"
            >
              History
            </Link>
            <Link
              to={`/folios/${folio.id}/fund`}
              className="text-sm underline"
            >
              Fund
            </Link>
          </div>
        </div>
      ))}
    </div>
  );
}
