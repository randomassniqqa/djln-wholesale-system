<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * HARDENED: Middleware to enforce idempotency on POST/PUT/PATCH requests
     * 
     * Protocol:
     * - Client sends Idempotency-Key header (UUID v4 recommended)
     * - Server checks if this key+user exists in idempotency_keys table
     * - If yes and not expired (24h TTL), return cached response immediately
     * - If no or expired, allow request to execute normally
     * - After response, cache it with idempotency key for future duplicates
     * 
     * This prevents double-click bugs where same POST twice creates two records
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce for POST, PUT, PATCH requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Skip if no Idempotency-Key header
        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey || !auth()->check()) {
            return $next($request);
        }

        try {
            // Generate hash of request body for duplicate detection
            $requestHash = md5(json_encode($request->except(['_token', '_method', 'password', 'password_confirmation'])));

            // Check if this idempotency key exists
            $cached = DB::table('idempotency_keys')
                ->where('idempotency_key', $idempotencyKey)
                ->where('user_id', auth()->id())
                ->where('request_path', $request->path())
                ->first();

            // If cached and not expired, return cached response
            if ($cached && $cached->expires_at && now()->lessThan($cached->expires_at)) {
                Log::info('Idempotent request cache hit', [
                    'idempotency_key' => $idempotencyKey,
                    'user_id' => auth()->id(),
                    'path' => $request->path(),
                ]);

                return response()->json(
                    json_decode($cached->response_data),
                    $cached->response_status ?? 200
                );
            }

            // Process the request normally
            $response = $next($request);

            // Cache the response for future identical requests
            DB::table('idempotency_keys')->updateOrInsert(
                [
                    'idempotency_key' => $idempotencyKey,
                    'user_id' => auth()->id(),
                    'request_path' => $request->path(),
                ],
                [
                    'request_method' => $request->method(),
                    'request_hash' => json_encode(['hash' => $requestHash]),
                    'response_data' => json_encode($response->getOriginalContent()),
                    'response_status' => $response->getStatusCode(),
                    'expires_at' => now()->addHours(24), // 24-hour TTL
                    'updated_at' => now(),
                ]
            );

            return $response;
        } catch (Exception $e) {
            Log::error('Idempotency middleware error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? null,
                'path' => $request->path(),
            ]);

            // On error, allow request to proceed (fail open, not closed)
            return $next($request);
        }
    }
}
