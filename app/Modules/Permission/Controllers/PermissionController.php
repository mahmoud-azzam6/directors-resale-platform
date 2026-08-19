<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Http\Request;
use App\Modules\Permission\Services\PermissionService;
use App\Responses\Response;

final class PermissionController
{
    public function __construct(private PermissionService $service) {}
    public function index(Request $request): Response { return Response::success('Permissions retrieved.', $this->service->all()); }
}