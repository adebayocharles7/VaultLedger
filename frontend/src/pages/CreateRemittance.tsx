import { useRef, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { fetchFolios } from '../api/folios';
import { createRemittance } from '../api/remittances';
import { generateIdempotencyKey } from '../lib/idempotency';
import type { Folio } from '../api/types';

export default function CreateRemittance() {
  const [sourceFolioId, setSourceFolioId] = useState('');
  const [destinationFolioId, setDestinationFolioId] = useState('');
  const [nairaAmount, setNairaAmount] = useState('');
  const [description, setDescription] = useState('');

  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const idempotencyKeyRef = useRef(generateIdempotencyKey());

  // Reuse the folios list we already fetch elsewhere — TanStack Query
  // dedupes this against FolioList's identical ['folios'] query, so it's
  // not a second network request if that data is still fresh.
  const { data: folios } = useQuery<Folio[]>({
    queryKey: ['folios'],
    queryFn: fetchFolios,
  });

  const mutation = useMutation({
    mutationFn: () => {
      const amountMinor = Math.round(Number(nairaAmount) * 100);
      return createRemittance(
        {
          source_folio_id: sourceFolioId,
          destination_folio_id: destinationFolioId,
          amount_minor: amountMinor,
          description: description || undefined,
        },
        idempotencyKeyRef.current
      );
    },
    onSuccess: (remittance) => {
      // Both folios' balances just changed, and this remittance itself
      // is now a fetchable resource — invalidate all three.
      queryClient.invalidateQueries({ queryKey: ['folios'] });
      queryClient.invalidateQueries({ queryKey: ['folio', sourceFolioId] });
      queryClient.invalidateQueries({ queryKey: ['folio', destinationFolioId] });

      idempotencyKeyRef.current = generateIdempotencyKey();
      navigate(`/remittances/${remittance.id}`);
    },
  });

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (mutation.isPending) return;
    mutation.mutate();
  }

  return (
    <div className="max-w-sm mx-auto mt-16">
      <h1 className="text-xl font-semibold mb-4">Send Money</h1>
      <form onSubmit={handleSubmit} className="space-y-3">
        <label className="block text-sm text-gray-600">From folio</label>
        <select
          value={sourceFolioId}
          onChange={(e) => setSourceFolioId(e.target.value)}
          className="w-full border rounded px-3 py-2"
          required
        >
          <option value="" disabled>
            Select a folio
          </option>
          {folios?.map((folio) => (
            <option key={folio.id} value={folio.id}>
              {folio.label} — {folio.currency}
            </option>
          ))}
        </select>

        <label className="block text-sm text-gray-600">To folio ID</label>
        <input
          type="text"
          placeholder="Destination folio ID"
          value={destinationFolioId}
          onChange={(e) => setDestinationFolioId(e.target.value)}
          className="w-full border rounded px-3 py-2"
          required
        />

        <label className="block text-sm text-gray-600">Amount (₦)</label>
        <input
          type="number"
          min="1"
          step="0.01"
          value={nairaAmount}
          onChange={(e) => setNairaAmount(e.target.value)}
          className="w-full border rounded px-3 py-2"
          required
        />

        <input
          type="text"
          placeholder="Description (optional)"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          className="w-full border rounded px-3 py-2"
        />

        <button
          type="submit"
          disabled={mutation.isPending || sourceFolioId === destinationFolioId}
          className="w-full bg-black text-white rounded py-2 disabled:opacity-50"
        >
          {mutation.isPending ? 'Sending…' : 'Send'}
        </button>

        {sourceFolioId && sourceFolioId === destinationFolioId && (
          <p className="text-amber-600 text-sm">
            Source and destination can't be the same folio.
          </p>
        )}
        {mutation.isError && (
          <p className="text-red-600 text-sm">
            Transfer failed. Check the destination ID and your balance, then
            try again — safe to retry.
          </p>
        )}
      </form>
    </div>
  );
}
