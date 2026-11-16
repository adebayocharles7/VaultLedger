<?php

// Drop this in as backend/config/cors.php (or merge with your existing one).
// This is ONLY needed if the React app calls Laravel directly
// (VITE_API_BASE_URL=http://localhost:8000/api/v1).
// If you use the Vite dev proxy instead, you can skip this entirely in dev —
// but you'll still need it for your production deployment, since the
// deployed frontend and backend will live on different domains.

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // In production, replace with your real frontend domain,
    // e.g. 'https://vaultledger-frontend.vercel.app'
    'allowed_origins' => [
        'http://localhost:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Idempotent-Replay'],

    'max_age' => 0,

    // Required for Sanctum's cookie-based auth to work cross-origin.
    // If you're using Bearer tokens (as this frontend scaffold does)
    // instead of Sanctum's SPA cookie mode, this can stay false.
    'supports_credentials' => false,

];
