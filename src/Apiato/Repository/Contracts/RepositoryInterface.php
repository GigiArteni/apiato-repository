<?php

namespace Apiato\Repository\Contracts;

/**
 * Repository Interface - Enhanced for Apiato v.13
 * Compatible with repository pattern + performance improvements
 */
interface RepositoryInterface
{
    // Core repository methods
    public function all($columns = ['*']);
    public function first($columns = ['*']);
    public function paginate($limit = null, $columns = ['*']);
    public function find($id, $columns = ['*']);
    public function findByField($field, $value, $columns = ['*']);
    public function findWhere(array $where, $columns = ['*']);
    public function findWhereIn($field, array $where, $columns = ['*']);
    public function findWhereNotIn($field, array $where, $columns = ['*']);
    public function findWhereBetween($field, array $where, $columns = ['*']);
    public function create(array $attributes);
    public function update(array $attributes, $id);
    public function updateOrCreate(array $attributes, array $values = []);
    public function delete($id);
    public function deleteWhere(array $where);
    public function orderBy($column, $direction = 'asc');
    public function with(array $relations);
    public function has(string $relation);
    public function whereHas(string $relation, \Closure $closure);
    public function hidden(array $fields);
    public function visible(array $fields);
    public function scopeQuery(\Closure $scope);
    public function getFieldsSearchable();
    public function setPresenter($presenter);
    public function skipPresenter($status = true);
}
