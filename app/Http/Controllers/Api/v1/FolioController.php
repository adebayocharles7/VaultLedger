<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PostingService;
use App\Services\IdempotencyService;
use App\Models\Folio;
use App\Http\Resources\FolioResource;
use App\Http\Resources\RemittanceResource;
use App\Http\Resources\LedgerEntryResource;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\FolioFrozenException;
use App\Exceptions\StaleVersionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FolioController extends Controller
{
    public function __construct(
        private readonly PostingService     $posting,
        private readonly IdempotencyService $idempotency
    ) {}

    /**
     * POST /api/v1/folios
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
            'balance_minor' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        $folio = Folio::create([
            'user_id' => $request->user()->id,
            'currency' => strtoupper($validated['currency']),
            'metadata' => $validated['metadata'] ?? [],
        ]);

        return response()->json($folio, 201);
    }

    /**
     * GET /api/v1/folios/{folio}
     */
    public function show(Folio $folio)
    {
        $this->authorize('view', $folio);
        return new FolioResource($folio->load('owner'));
    }

    /**
     * POST /api/v1/folios/{folio}/fund
     */
    public function fund(Request $request, Folio $folio)
    {

        $validated = $request->validate([
            'amount_minor' => 'required|integer|min:1',
            'narration' => 'nullable|string|max:255',
        ]);

        $this->authorize('update', $folio);
        
        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->resolveWithIdempotency(
                $request->user()->id,
                $idempotencyKey,
                fn () => $this->posting->fund(
                    $folio,
                    $validated['amount_minor'],
                    $validated['narration'] ?? 'Top-up'
                )
            );

            return $this->idempotentResponse($result, RemittanceResource::class, 201);
        } catch (FolioFrozenException $e) {
            return $this->walletError($e->getMessage(), 422);
        } catch (StaleVersionException $e) {
            return $this->walletError($e->getMessage(), 409);
        }
    }

    /**
     * POST /api/v1/folios/{folio}/withdraw
     */
    public function withdraw(Request $request, Folio $folio): JsonResponse
    {

        $this->authorize('update', $folio);

        try {
            $result = $this->resolveWithIdempotency(
                $request->user()->id,
                $request->header('Idempotency-Key'),
                fn () => $this->posting->withdraw(
                    $folio,
                    $request->validated('amount_minor'),
                    $request->validated('narration', 'Withdrawal')
                )
            );

        return $this->idempotentResponse($result, RemittanceResource::class, 200);
        } catch (InsufficientFundsException $e) {
            return $this->walletError($e->getMessage(), 422, 'INSUFFICIENT_FUNDS');
        } catch (FolioFrozenException $e) {
            return $this->walletError($e->getMessage(), 422, 'FOLIO_FROZEN');
        } catch (StaleVersionException $e) {
            return $this->walletError($e->getMessage(), 409, 'STALE_VERSION');
        }
    } 

    public function ledger(Request $request, Folio $folio): AnonymousResourceCollection
    {
        $this->authorize('view', $folio);

        $entries = $folio->ledgerEntries()
        ->with('remittance')
        ->latest()
        ->paginate($request->integer('per_page', 20));

        return LedgerEntryResource::collection($entries);
    }

     /**
     * Helper method to handle idempotent operations.
     */
    private function resolveWithIdempotency(string $userId, ?string $key, callable $operation): array
    {
        if (!$key) {
            // No idempotency key provided, just execute the operation
            $remittance = $operation();
            return [
                'cached' => false,
                'payload' => $remittance,
            ];
        }

        return $this->idempotency->wrap($userId, $key, $operation);       
    }   

    /**
    * Helper method to format idempotent responses.
    */  
    private function idempotentResponse(array $result, string $resourceClass, int $statusCode): JsonResponse   
    {
        if ($result['cached'] ?? false) {
            return response()->json(
                $result['payload']) 
                ->headers(['X-Idempotent-Replay', 'true'])
                ->setStatusCode($status);
        }           

        if ($result['concurrent'] ?? false) {
            return response()->json([
                'message' => 'A request with this idempotency key is already in-flight.',
                'code' => 'CONCURRENT_REQUEST',
            ], 409);
        }

        return (new $resourceClass($result['payload']))
            ->response()
            ->setStatusCode($status);
    }

    /**
    * Helper method to format wallet-related errors.
    */
    private function walletError(string $message, int $status, string $code = 'WALLET_ERROR'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
        ], $status);
    }
}
