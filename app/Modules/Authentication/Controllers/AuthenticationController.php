<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Controllers;

use App\Http\Request;
use App\Modules\Authentication\Services\AuthenticationService;
use App\Responses\Response;

/**
 * Handles authentication endpoints without exposing credential material.
 */
final class AuthenticationController
{
    public function __construct(private AuthenticationService $service)
    {
    }

    public function login(Request $request): Response
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (! is_string($email) || trim($email) === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($password) || $password === '') {
            return $this->invalidCredentials();
        }

        $result = $this->service->login(trim($email), $password);

        return $result === null
            ? $this->invalidCredentials()
            : Response::success('Authenticated.', $result);
    }

    public function logout(Request $request): Response
    {
        $this->service->logout((int) $request->attribute('auth.token_id'));

        return Response::success('Logged out.');
    }

    public function me(Request $request): Response
    {
        return Response::success('Authenticated User retrieved.', $request->attribute('auth.user'));
    }

    private function invalidCredentials(): Response
    {
        return Response::error('authentication_failed', 'Invalid credentials.', 401);
    }
}