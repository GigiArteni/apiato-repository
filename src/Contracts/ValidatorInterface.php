<?php

declare(strict_types=1);

namespace Apiato\Repository\Contracts;

interface ValidatorInterface
{
    public function validate(array $data, string $action = 'create'): array;
    public function getRules(string $action = 'create'): array;
    public function getMessages(): array;
    public function getAttributes(): array;
}
