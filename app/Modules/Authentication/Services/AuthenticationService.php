<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Repositories\AuthTokenRepository;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;
use RuntimeException;

/**
 * Coordinates password verification and bearer-token authentication.
 */
final class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepository,
        private AuthTokenRepository $tokenRepository,
        private array $config
    ) {
    }

    /** @return array{token: string, user: array<string, mixed>}|null */
    public function login(string $email, string $password): ?array
    {
        $record = $this->userRepository->findByEmail($email);

        if ($record === null || ($record['status'] ?? null) !== 'active'
            || ! is_string($record['password_hash'] ?? null)
            || ! password_verify($password, $record['password_hash'])) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $ttl = (int) ($this->config['app']['auth']['token_ttl'] ?? 3600);

        if ($ttl <= 0) {
            throw new RuntimeException('Authentication token lifetime must be positive.');
        }

        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        $this->tokenRepository->createToken((int) $record['id'], $tokenHash, $expiresAt);

        return [
            'token' => $token,
            'user' => User::fromArray($record)->toArray(),
        ];
    }

    /** @return array{user: array<string, mixed>, token_id: int}|null */
    public function authenticate(string $token): ?array
    {
        $tokenRecord = $this->tokenRepository->findValidByHash(hash('sha256', $token));

        if ($tokenRecord === null) {
            return null;
        }

        $user = $this->userRepository->findActive((int) $tokenRecord['user_id']);

        return $user === null
            ? null
            : ['user' => User::fromArray($user)->toArray(), 'token_id' => (int) $tokenRecord['id']];
    }

    public function logout(int|string $tokenId): bool
    {
        return $this->tokenRepository->revoke($tokenId);
    }
}