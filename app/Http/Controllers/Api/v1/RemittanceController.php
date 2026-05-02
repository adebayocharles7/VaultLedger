<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\WalletExceptions\FolioFrozenException;
use App\Exceptions\WalletExceptions\InsufficientFundsException;
use App\Exceptions\WalletExceptions\StaleVersionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\RemittanceResource;
use App\Models\Folio;
use App\Models\Remittance;
use App\Services\IdempotencyService;
use App\Services\PostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemittanceController extends Controller
{
    public function __construct(
        private readonly PostingService     $posting,
        private readonly IdempotencyService $idempotency
        ) {}

    /** 
     * POST /api/v1/remittances
     * 
     * Initiates a peer-to-peer transfer between two folios. The source folio is debited and the destination folio is credited in a single atomic operation. The remittance record captures the details of the transfer for auditing and reconciliation purposes.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_folio_id' => 'required|ulid|exists:folios,id',
            'destination_folio_id' => 'required|ulid|exists:folios,id|different:source_folio_id',
            'amount_minor' => 'required|integer|min:1',
            'narration' => 'nullable|string|max:255',
        ]);

        $source = Folio::findOrFail($validated['source_folio_id']);
        $destination = Folio::findOrFail($validated['destination_folio_id']);

        $this->authorize('update', $source);

        if ($source->currency !== $destination->currency) {
            return response()->json([
                'message' => 'Cross-currency transfers are not supported.',
                'code' => 'CURRENCY_MISMATCH',
            ], 422);
        }

        try {
            $wrappedResult = $this->idempotency->wrap(
                $request->user()->id,
                $request->header('Idempotency-Key', (string) \Str::uuid()),
                fn () => $this->posting->transfer(                    
                    source: $source,
                    destination: $destination,
                    amountMinor: $validated['amount_minor'],
                    narration: $validated['narration'] ?? null,
                )
            );

            $remittance = $wrappedResult['payload'];

            return (new RemittanceResource($remittance))
                ->response()
                ->setStatusCode(201)
                ->withHeaders( 
                   ($wrappedResult['cached'] ?? false) 
                   ? ['X-Idempotent-Replay' => 'true']
                   : []
                );

        } catch (InsufficientFundsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_FUNDS'
            ], 422);
        } catch (FolioFrozenException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'FOLIO_FROZEN'
            ], 422);
        } catch (StaleVersionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CONCURRENT_MODIFICATION'
            ], 409);
        }
    }

    /**
     * GET /api/v1/remittances/{remittance}
     */
    public function show(Remittance $remittance): RemittanceResource
    {
        $this->authorize('view', $remittance);

        return new RemittanceResource(
            $remittance
                ->load(['sourceFolio', 'destinationFolio', 'ledgerEntries'])
        );
    }

    /* 
    * POST /api/v1/remittances/{remittance} endpoint 
    */
    public function reverse(Remittance $remittance): JsonResponse {
        $this->authorize('update', $remittance);

        try {
            $counter = $this->posting->reverse($remittance);

            return (new RemittanceResource($counter))
                ->response()
                ->setStatusCode(201);
        } catch (\LogicException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'NON_REVERSIBLE',
            ], 422);
        } catch (InsufficientFundsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_FUNDS',
            ], 422);
        }
    }

    

}
