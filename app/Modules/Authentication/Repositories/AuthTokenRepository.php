<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Repositories;

use App\Core\Repository\BaseRepository;

/**
 * Persists hashed authentication tokens and their lifecycle state.
 */
final class AuthTokenRepository extends BaseRepository
{
    protected function table(): string
    {
        return 'auth_tokens';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @return array<string, mixed>|null */
    public function findValidByHash(string $tokenHash): ?array
    {
        $record = $this->query()->where('token_hash', '=', $tokenHash)->first();

        if ($record === null || $record['revoked_at'] !== null
            || strtotime((string) $record['expires_at']) <= time()) {
            return null;
        }

        return $record;
    }

    /** @return array<string, mixed> */
    public function createToken(int $userId, string $tokenHash, string $expiresAt): array
    {
        return $this->create([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function revoke(int|string $id): bool
    {
        return $this->query()
            ->where('id', '=', $id)
            ->update(['revoked_at' => date('Y-m-d H:i:s')]) > 0;
    }
}