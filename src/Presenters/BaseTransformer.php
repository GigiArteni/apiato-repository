<?php

declare(strict_types=1);

namespace Apiato\Repository\Presenters;

use Apiato\Repository\Contracts\TransformerInterface;
use League\Fractal\TransformerAbstract;

/**
 * Base transformer with HashId support and Apiato integration
 */
abstract class BaseTransformer extends TransformerAbstract implements TransformerInterface
{
    protected ?object $hashIds = null;

    public function __construct()
    {
        $this->initializeHashIds();
    }

    protected function initializeHashIds(): void
    {
        try {
            if (app()->bound('hashids')) {
                $this->hashIds = app('hashids');
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

    protected function encodeHashId(int $id): string
    {
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

    protected function encodeHashIds(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->encodeHashIds($value);
            } elseif ($this->isIdField($key) && is_numeric($value)) {
                $data[$key] = $this->encodeHashId((int)$value);
            }
        }

        return $data;
    }

    protected function isIdField(string $field): bool
    {
        $idFields = config('repository.hashid.fields', ['id', '*_id']);
        
        foreach ($idFields as $pattern) {
            if ($pattern === $field || fnmatch($pattern, $field)) {
                return true;
            }
        }

        return false;
    }

    public function includeRelations(): array
    {
        return [];
    }

    public function getAvailableIncludes(): array
    {
        return $this->availableIncludes;
    }

    public function getDefaultIncludes(): array
    {
        return $this->defaultIncludes;
    }

    abstract public function transform(mixed $data): array;
}
