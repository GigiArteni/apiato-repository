<?php

declare(strict_types=1);

namespace Apiato\Repository\Criteria;

use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;
use Apiato\Repository\Traits\HashIdRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Enhanced RequestCriteria with full Apiato integration
 */
class RequestCriteria implements CriteriaInterface
{
    use HashIdRepository;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->initializeHashIds();
    }

    public function apply(Builder $model, RepositoryInterface $repository): Builder
    {
        $fieldsSearchable = $repository->getFieldsSearchable();

        $model = $this->applyIncludes($model);
        $model = $this->applySearch($model, $fieldsSearchable);
        $model = $this->applyFilters($model, $fieldsSearchable);
        $model = $this->applyOrdering($model);

        return $model;
    }

    protected function applyIncludes(Builder $model): Builder
    {
        $includes = $this->request->get(config('repository.criteria.params.include', 'include'));
        
        if (!$includes) {
            return $model;
        }

        $relations = array_map('trim', explode(',', $includes));
        $includes = [];

        foreach ($relations as $relation) {
            if (str_ends_with($relation, '_count')) {
                $baseRelation = str_replace('_count', '', $relation);
                $includes[] = $baseRelation . ':count';
            } else {
                $includes[] = $relation;
            }
        }

        return $model->with($includes);
    }

    protected function applySearch(Builder $model, array $fieldsSearchable): Builder
    {
        $search = $this->request->get(config('repository.criteria.params.search', 'search'));
        
        if (!$search || empty($fieldsSearchable)) {
            return $model;
        }

        $searchJoin = strtoupper($this->request->get('searchJoin', 'OR'));
        $searchData = $this->parseSearchData($search);

        return $model->where(function ($query) use ($searchData, $fieldsSearchable, $searchJoin) {
            $first = true;
            
            foreach ($searchData as $item) {
                $method = $first ? 'where' : ($searchJoin === 'AND' ? 'where' : 'orWhere');
                $this->applySearchCondition($query, $item, $fieldsSearchable, $method);
                $first = false;
            }
        });
    }

    protected function parseSearchData(string $search): array
    {
        $data = [];
        $parts = explode(';', $search);

        foreach ($parts as $part) {
            $segments = explode(':', trim($part));
            if (count($segments) >= 2) {
                $field = $segments[0];
                
                if (count($segments) === 2) {
                    $value = $this->processValue($segments[1]);
                    $data[] = ['field' => $field, 'operator' => '=', 'value' => $value];
                } else {
                    $operator = $segments[1];
                    $value = $this->processValue(implode(':', array_slice($segments, 2)));
                    $data[] = ['field' => $field, 'operator' => $operator, 'value' => $value];
                }
            }
        }

        return $data;
    }

    protected function processValue(string $value): string
    {
        if (str_contains($value, ',')) {
            $values = array_map('trim', explode(',', $value));
            $processed = [];
            
            foreach ($values as $val) {
                if ($this->looksLikeHashId($val)) {
                    $decoded = $this->decodeHashId($val);
                    $processed[] = $decoded ?? $val;
                } else {
                    $processed[] = $val;
                }
            }
            
            return implode(',', $processed);
        }

        if ($this->looksLikeHashId($value)) {
            $decoded = $this->decodeHashId($value);
            return (string)($decoded ?? $value);
        }

        return $value;
    }

    protected function applySearchCondition(Builder $query, array $item, array $fieldsSearchable, string $method = 'orWhere'): void
    {
        $field = $item['field'];
        $operator = strtolower($item['operator']);
        $value = $item['value'];

        if (!$this->isFieldSearchable($field, $fieldsSearchable)) {
            return;
        }

        $query->{$method}(function ($q) use ($field, $operator, $value) {
            switch ($operator) {
                case 'like':
                    $q->where($field, 'like', "%{$value}%");
                    break;
                
                case 'between':
                    $values = explode(',', $value);
                    if (count($values) === 2) {
                        $q->whereBetween($field, [trim($values[0]), trim($values[1])]);
                    }
                    break;
                
                case 'in':
                    $values = array_map('trim', explode(',', $value));
                    $q->whereIn($field, $values);
                    break;
                
                case 'date_between':
                    $dates = explode(',', $value);
                    if (count($dates) === 2) {
                        $start = Carbon::parse(trim($dates[0]))->startOfDay();
                        $end = Carbon::parse(trim($dates[1]))->endOfDay();
                        $q->whereBetween($field, [$start, $end]);
                    }
                    break;
                
                case 'today':
                    $q->whereDate($field, Carbon::today());
                    break;
                
                case 'this_week':
                    $q->whereBetween($field, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                
                case 'this_month':
                    $q->whereMonth($field, Carbon::now()->month)->whereYear($field, Carbon::now()->year);
                    break;
                
                default:
                    $q->where($field, $operator, $value);
            }
        });
    }

    protected function applyFilters(Builder $model, array $fieldsSearchable): Builder
    {
        $filter = $this->request->get(config('repository.criteria.params.filter', 'filter'));
        
        if (!$filter) {
            return $model;
        }

        $filterData = $this->parseSearchData($filter);

        foreach ($filterData as $item) {
            $this->applyFilterCondition($model, $item);
        }

        return $model;
    }

    protected function applyFilterCondition(Builder $model, array $item): void
    {
        $field = $item['field'];
        $operator = strtolower($item['operator']);
        $value = $item['value'];

        switch ($operator) {
            case 'between':
                $values = explode(',', $value);
                if (count($values) === 2) {
                    $model->whereBetween($field, [trim($values[0]), trim($values[1])]);
                }
                break;
            
            case 'in':
                $values = array_map('trim', explode(',', $value));
                $model->whereIn($field, $values);
                break;
            
            default:
                $model->where($field, $operator, $value);
        }
    }

    protected function applyOrdering(Builder $model): Builder
    {
        $orderBy = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'));
        $sortedBy = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');

        if (!$orderBy) {
            return $model;
        }

        $orderFields = explode(',', $orderBy);
        $sortDirections = explode(',', $sortedBy);

        foreach ($orderFields as $index => $field) {
            $direction = $sortDirections[$index] ?? $sortDirections[0] ?? 'asc';
            $field = trim($field);
            $direction = trim(strtolower($direction));

            if (in_array($direction, ['asc', 'desc'])) {
                $model->orderBy($field, $direction);
            }
        }

        return $model;
    }

    protected function isFieldSearchable(string $field, array $fieldsSearchable): bool
    {
        return in_array($field, array_keys($fieldsSearchable)) || in_array($field, $fieldsSearchable);
    }
}
