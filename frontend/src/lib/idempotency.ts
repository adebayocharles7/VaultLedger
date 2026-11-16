// Your backend hashes SHA-256(user_id + "|" + raw_key) and stores it for
// 24h, replaying the cached response on a duplicate key. That means the
// *frontend's* job is simple but important: generate ONE key per logical
// action, and reuse that same key if the request is retried (network
// blip, double-click, etc). Generating a new key on retry defeats the
// whole point — you'd get a genuine double-spend instead of a safe replay.

export function generateIdempotencyKey(): string {
  return crypto.randomUUID();
}
