<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Middleware;

use App\Http\Request;
use App\Modules\Authentication\Services\AuthenticationService;
use App\Responses\Response;

/**
 * Resolves the authenticated User from a bearer token for protected routes.
 */
final class AuthenticationMiddleware
{
    public function __construct(private AuthenticationService $service)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $header = $request->header('Authorization');
        $token = is_string($header) && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1
            ? trim($matches[1])
            : '';
        $identity = $token === '' ? null : $this->service->authenticate($token);

        if ($identity === null) {
            return Response::error('unauthenticated', 'Authentication is required.', 401);
        }

        $request->setAttribute('auth.user', $identity['user']);
        $request->setAttribute('auth.token_id', $identity['token_id']);

        return $next($request);
    }
}