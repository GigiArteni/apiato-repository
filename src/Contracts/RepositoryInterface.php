<?php

declare(strict_types=1);

namespace Apiato\Repository\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface RepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator;
    public function find(mixed $id, array $columns = ['*']): ?Model;
    public function findOrFail(mixed $id, array $columns = ['*']): Model;
    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection;
    public function findWhere(array $where, array $columns = ['*']): Collection;
    public function findWhereFirst(array $where, array $columns = ['*']): ?Model;
    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection;
    public function findWhereNotIn(string $field, array $values, array $columns = ['*']): Collection;
    public function findWhereBetween(string $field, array $values, array $columns = ['*']): Collection;
    public function create(array $attributes): Model;
    public function update(array $attributes, mixed $id): Model;
    public function updateOrCreate(array $attributes, array $values = []): Model;
    public function delete(mixed $id): int;
    public function deleteMultiple(array $ids): int;
    public function deleteWhere(array $where): int;
    public function query(): Builder;
    public function makeModel(): Model;
    public function resetModel(): static;
    public function model(): string;
    public function pushCriteria(CriteriaInterface $criteria): static;
    public function popCriteria(CriteriaInterface $criteria): static;
    public function getCriteria(): \Illuminate\Support\Collection;
    public function applyCriteria(): static;
    public function skipCriteria(bool $status = true): static;
    public function clearCriteria(): static;
    public function getFieldsSearchable(): array;
}
