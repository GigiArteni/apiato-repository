<?php

namespace Apiato\Repository\Contracts;

/**
 * Transformer Interface
 * Defines the contract for data transformation
 */
interface TransformerInterface
{
    /**
     * Transform the given data
     *
     * @param mixed $model
     * @return array
     */
    public function transform($model);
}
