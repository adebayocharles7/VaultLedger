<?php

namespace App\Services;

use App\Models\IdempotencyRecord;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * IdempotencyService
 *
 * Ensures that retried API requests don't execute twice.
 *
 * Usage:
 *   $result = $this->idempotency->wrap($userId, $rawKey, function () {
 *       return $this->postingService->fund(...);
 *   });
 *
 * The raw key is hashed with the user ID so that two users
 * submitting the same string don't collide.
 */
class IdempotencyService
{
    private const TTL_HOURS = 24;

    public function wrap(string $userId, string $rawKey, Closure $operation): array
    {
        $hash = $this->hash($userId, $rawKey);

        // 1. Fast path: check cache before hitting DB.
        $cached = Cache::get("idempotency:{$hash}");
        if ($cached) {
            return ['cached' => true, 'payload' => $cached];
        }

        // 2. Check DB for existing record or create a new one atomically. (slower path than cache, but ensures correctness under concurrency)
        $record = IdempotencyRecord::firstOrCreate(
            ['key_hash' => $hash],
            [
                'user_id'    => $userId,
                'expires_at' => now()->addHours(self::TTL_HOURS),
            ]
        );

        // 3. Concurrent request already has the lock — return early with 409. The row already exists but isn't resolved, so another request is in-flight with the same key. 
        if ($record->wasRecentlyCreated === false && ! $record->isResolved()) {
            return ['concurrent' => true]; // tell caller to wait and retry later
        }
        
        // 4. If the record is already resolved, return the cached response.
        if ($record->isResolved()) {
            return ['cached' => true, 'payload' => $record->response_body];
        }

        // 5. Fresh request with lock acquired — execute the operation    
        $result = $operation();

        // 6. Update the record with the result and release the lock.
        $record->update([
            'response_body' => $result,
            'resolved_at'   => now(),
        ]);

        Cache::put("idempotency:{$hash}", $result, now()->addHours(self::TTL_HOURS));

        // 7. Return the (fresh) result to the caller.
        return ['cached' => false, 'payload' => $result];
    }

    private function hash(string $userId, string $rawKey): string
    {
        return hash('sha256', $userId . '|' . $rawKey);
    }
}