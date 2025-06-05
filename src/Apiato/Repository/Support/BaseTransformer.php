<?php

namespace Apiato\Repository\Support;

use League\Fractal\TransformerAbstract;
use Apiato\Repository\Contracts\TransformerInterface;

/**
 * Base Transformer
 * Provides common functionality for data transformers
 */
abstract class BaseTransformer extends TransformerAbstract implements TransformerInterface
{
    /**
     * Transform the given model
     */
    abstract public function transform($model);

    /**
     * Transform a single item
     */
    protected function item($data, TransformerInterface $transformer, $resourceKey = null)
    {
        return $this->item($data, $transformer, $resourceKey);
    }

    /**
     * Transform a collection
     */
    protected function collection($data, TransformerInterface $transformer, $resourceKey = null)
    {
        return $this->collection($data, $transformer, $resourceKey);
    }

    /**
     * Transform with null check
     */
    protected function transformWithNullCheck($data, $transformer, $default = null)
    {
        if (is_null($data)) {
            return $default;
        }

        if ($transformer instanceof TransformerInterface) {
            return $transformer->transform($data);
        }

        if (is_callable($transformer)) {
            return $transformer($data);
        }

        return $data;
    }

    /**
     * Transform date to ISO format
     */
    protected function transformDate($date, $format = 'c')
    {
        if (is_null($date)) {
            return null;
        }

        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format($format);
    }

    /**
     * Transform boolean to string
     */
    protected function transformBoolean($value, $trueValue = 'yes', $falseValue = 'no')
    {
        return $value ? $trueValue : $falseValue;
    }
}
