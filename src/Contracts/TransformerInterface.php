<?php

declare(strict_types=1);

namespace Apiato\Repository\Contracts;

interface TransformerInterface
{
    public function transform(mixed $data): array;
    public function includeRelations(): array;
    public function getAvailableIncludes(): array;
    public function getDefaultIncludes(): array;
}
