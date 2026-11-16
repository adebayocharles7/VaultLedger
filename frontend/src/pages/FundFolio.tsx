import { useRef, useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { fundFolio } from '../api/folios';
import { generateIdempotencyKey } from '../lib/idempotency';

export default function FundFolio() {
  const { folioId } = useParams<{ folioId: string }>();
  const [nairaAmount, setNairaAmount] = useState('');
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  // Generated once per "mount" of this form, not once per submit-click.
  // If the user's request times out and they hit "Fund" again, this ref
  // still holds the SAME key — so the backend recognizes it as a retry
  // of the same action rather than a second, separate deposit.
  const idempotencyKeyRef = useRef(generateIdempotencyKey());

  const mutation = useMutation({
    mutationFn: () => {
      const amountMinor = Math.round(Number(nairaAmount) * 100);
      return fundFolio(folioId!, amountMinor, idempotencyKeyRef.current);
    },
    onSuccess: (updatedFolio) => {
      // Two things need to reflect the new balance: the list view and
      // this folio's own cache entry. Invalidating both tells TanStack
      // Query "go refetch these next time they're used" rather than us
      // manually patching state everywhere.
      queryClient.invalidateQueries({ queryKey: ['folios'] });
      queryClient.invalidateQueries({ queryKey: ['folio', folioId] });

      // Only mint a fresh key after a confirmed success — a genuinely
      // new action (e.g. funding again later) deserves its own key.
      idempotencyKeyRef.current = generateIdempotencyKey();

      navigate(`/folios/${updatedFolio.id}`);
    },
  });

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (mutation.isPending) return; // guards against double-click too
    mutation.mutate();
  }

  return (
    <div className="max-w-sm mx-auto mt-16">
      <h1 className="text-xl font-semibold mb-4">Fund Folio</h1>
      <form onSubmit={handleSubmit} className="space-y-3">
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
        <button
          type="submit"
          disabled={mutation.isPending}
          className="w-full bg-black text-white rounded py-2 disabled:opacity-50"
        >
          {mutation.isPending ? 'Processing…' : 'Fund Folio'}
        </button>
        {mutation.isError && (
          <p className="text-red-600 text-sm">
            Something went wrong. It's safe to try again — this request
            won't be double-counted.
          </p>
        )}
      </form>
    </div>
  );
}
