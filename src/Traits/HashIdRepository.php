<?php

declare(strict_types=1);

namespace Apiato\Repository\Traits;

/**
 * HashId support for repositories with Apiato integration
 */
trait HashIdRepository
{
    protected ?object $hashIds = null;

    protected function initializeHashIds(): void
    {
        if ($this->hashIds !== null) {
            return;
        }

        try {
            if (app()->bound('hashids')) {
                $this->hashIds = app('hashids');
            } elseif (class_exists('Apiato\Core\Foundation\Facades\Hashids')) {
                $this->hashIds = app('Apiato\Core\Foundation\Facades\Hashids');
            } elseif (class_exists('Hashids\Hashids')) {
                $this->hashIds = new \Hashids\Hashids(
                    config('apiato.hash-id.salt', config('app.key')),
                    config('apiato.hash-id.length', 6)
                );
            }
        } catch (\Exception) {
            $this->hashIds = null;
        }
    }

    public function decodeHashId(string $hashId): ?int
    {
        $this->initializeHashIds();

        if (!$this->hashIds) {
            return is_numeric($hashId) ? (int)$hashId : null;
        }

        try {
            if (method_exists($this->hashIds, 'decode')) {
                $decoded = $this->hashIds->decode($hashId);
                return !empty($decoded) ? (int)$decoded[0] : null;
            }
        } catch (\Exception) {
            // Invalid hash
        }

        return is_numeric($hashId) ? (int)$hashId : null;
    }

    public function encodeHashId(int $id): string
    {
        $this->initializeHashIds();

        if (!$this->hashIds) {
            return (string)$id;
        }

        try {
            if (method_exists($this->hashIds, 'encode')) {
                return $this->hashIds->encode($id);
            }
        } catch (\Exception) {
            // Encoding failed
        }

        return (string)$id;
    }

    public function findByHashId(string $hashId, array $columns = ['*']): ?object
    {
        $id = $this->decodeHashId($hashId);
        return $id ? $this->find($id, $columns) : null;
    }

    public function findByHashIdOrFail(string $hashId, array $columns = ['*']): object
    {
        $id = $this->decodeHashId($hashId);
        
        if ($id === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return $this->findOrFail($id, $columns);
    }

    public function updateByHashId(array $attributes, string $hashId): object
    {
        $id = $this->decodeHashId($hashId);
        
        if ($id === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        return $this->update($attributes, $id);
    }

    public function deleteByHashId(string $hashId): int
    {
        $id = $this->decodeHashId($hashId);
        return $id ? $this->delete($id) : 0;
    }

    protected function looksLikeHashId(string $value): bool
    {
        return !is_numeric($value) && 
               strlen($value) >= config('repository.hashid.min_length', 4) && 
               strlen($value) <= config('repository.hashid.max_length', 20) && 
               preg_match('/^[a-zA-Z0-9]+$/', $value);
    }
}
