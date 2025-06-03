<?php

declare(strict_types=1);

namespace Apiato\Repository\Exceptions;

use Exception;

/**
 * Repository Exception
 */
class RepositoryException extends Exception
{
    public static function modelNotFound(string $model): static
    {
        return new static("Model {$model} not found or not an instance of Illuminate\\Database\\Eloquent\\Model");
    }

    public static function presenterNotFound(string $presenter): static
    {
        return new static("Presenter {$presenter} not found or not an instance of PresenterInterface");
    }

    public static function validatorNotFound(string $validator): static
    {
        return new static("Validator {$validator} not found or not an instance of ValidatorInterface");
    }

    public static function criteriaNotFound(string $criteria): static
    {
        return new static("Criteria {$criteria} not found or not an instance of CriteriaInterface");
    }
}
