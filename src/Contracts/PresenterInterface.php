<?php

declare(strict_types=1);

namespace Apiato\Repository\Contracts;

interface PresenterInterface
{
    public function present(mixed $data): mixed;
}
