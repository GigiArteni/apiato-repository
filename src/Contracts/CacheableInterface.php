<?php

declare(strict_types=1);

namespace Apiato\Repository\Contracts;

interface CacheableInterface
{
    public function cacheMinutes(int $minutes): static;
    public function cacheKey(string $key): static;
    public function skipCache(bool $status = true): static;
    public function clearCache(): bool;
    public function getCacheKey(string $method, array $args = []): string;
    public function getCacheMinutes(): int;
    public function getCacheTags(): array;
}
