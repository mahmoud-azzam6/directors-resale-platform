<?php

declare(strict_types=1);

namespace App\Modules\Authorization\Middleware;

use App\Http\Request;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Authorization\Services\OrganizationScopeService;
use App\Responses\Response;

final class AuthorizationMiddleware
{
    public function __construct(private AuthorizationService $service) {}

    public function handle(
        Request $request,
        string $permission,
        callable $next,
        ?callable $targetResolver = null,
        string $scopeMode = OrganizationScopeService::HIERARCHY
    ): Response {
        $user = $request->attribute('auth.user');
        if (!is_array($user)) { return Response::error('unauthenticated', 'Authentication is required.', 401); }
        $request->setAttribute('auth.scope.organization_ids', $this->service->scopeFor($user, $scopeMode));
        $target = $targetResolver === null ? null : $targetResolver($request);
        $failure = $this->service->authorize($user, $permission, $target, $scopeMode);
        if ($failure !== null) { return $failure; }
        return $next($request);
    }
}
