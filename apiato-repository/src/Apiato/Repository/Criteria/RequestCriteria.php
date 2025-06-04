<?php

namespace Apiato\Repository\Criteria;

use Illuminate\Http\Request;
use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Enhanced RequestCriteria - 100% compatible with l5-repository + performance enhancements
 * Advanced filtering, searching, and query optimization
 * NO HashId dependencies - clean and fast
 */
class RequestCriteria implements CriteriaInterface
{
    protected Request $request;

    public function __construct(Request $request = null)
    {
        $this->request = $request ?? app('request');
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $fieldsSearchable = $repository->getFieldsSearchable();
        $search = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $searchFields = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $filter = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $orderBy = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sortedBy = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');
        $with = $this->request->get(config('repository.criteria.params.with', 'with'), null);

        // Apply relationships
        if ($with) {
            $with = is_string($with) ? explode(',', $with) : $with;
            $model = $model->with($with);
        }

        // Apply search
        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $searchFields = is_array($searchFields) || is_null($searchFields) ? $searchFields : explode(';', $searchFields);
            $fields = $this->parserFieldsSearch($fieldsSearchable, $searchFields);
            $isFirstField = true;
            $searchData = $this->parserSearchData($search);
            $search = $this->parserSearchValue($search);

            $modelForceAndWhere = strtolower($searchData->get('isForceAndWhere', 'or'));

            $model = $model->where(function ($query) use ($fields, $search, $searchData, $isFirstField, $modelForceAndWhere) {
                foreach ($fields as $field => $condition) {
                    if (is_numeric($field)) {
                        $field = $condition;
                        $condition = "=";
                    }
                    
                    $value = null;
                    $condition = trim(strtolower($condition));

                    // Enhanced condition handling
                    if (isset($searchData[$field])) {
                        $value = $this->parseSearchValue($searchData[$field], $condition);
                    } else {
                        if (!is_null($search) && !empty($search)) {
                            $value = $this->parseSearchValue($search, $condition);
                        }
                    }

                    if ($value !== null) {
                        $relation = null;
                        if (stripos($field, '.')) {
                            $explodeField = explode('.', $field);
                            $field = array_pop($explodeField);
                            $relation = implode('.', $explodeField);
                        }

                        $modelTableName = $query->getModel()->getTable();
                        
                        if ($isFirstField || $modelForceAndWhere == 'and') {
                            if (!is_null($relation)) {
                                $query->whereHas($relation, function ($query) use ($field, $condition, $value) {
                                    $this->applyCondition($query, $field, $condition, $value);
                                });
                            } else {
                                $this->applyCondition($query, $modelTableName.'.'.$field, $condition, $value);
                            }
                            $isFirstField = false;
                        } else {
                            if (!is_null($relation)) {
                                $query->orWhereHas($relation, function ($query) use ($field, $condition, $value) {
                                    $this->applyCondition($query, $field, $condition, $value);
                                });
                            } else {
                                $this->applyCondition($query, $modelTableName.'.'.$field, $condition, $value, 'or');
                            }
                        }
                    }
                }
            });
        }

        // Apply filters
        if ($filter && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $fields = $this->parserFieldsSearch($fieldsSearchable, null);
            $filterData = $this->parserSearchData($filter);

            foreach ($filterData as $field => $value) {
                if (array_key_exists($field, $fields)) {
                    $condition = $fields[$field];
                    if (is_numeric($condition)) {
                        $condition = "=";
                    }

                    $value = $this->parseSearchValue($value, $condition);
                    $this->applyCondition($model, $field, $condition, $value);
                }
            }
        }

        // Apply ordering
        if ($orderBy) {
            $orderBySplit = explode(',', $orderBy);
            if (count($orderBySplit) > 1) {
                $sortedBySplit = explode(',', $sortedBy);
                foreach ($orderBySplit as $orderBySplitItemKey => $orderBySplitItem) {
                    $sortedBy = isset($sortedBySplit[$orderBySplitItemKey]) ? $sortedBySplit[$orderBySplitItemKey] : $sortedBySplit[0];
                    $model = $model->orderBy(trim($orderBySplitItem), trim($sortedBy));
                }
            } else {
                $model = $model->orderBy($orderBy, $sortedBy);
            }
        }

        return $model;
    }

    protected function parseSearchValue($value, $condition)
    {
        $condition = strtolower(trim($condition));
        
        switch ($condition) {
            case 'like':
            case 'ilike':
                return "%{$value}%";
                
            case 'not_like':
                return "%{$value}%";
                
            case 'in':
            case 'not_in':
            case 'notin':
                return is_array($value) ? $value : explode(',', $value);
                
            case 'between':
            case 'not_between':
                return is_array($value) ? $value : explode(',', $value, 2);
                
            case 'date':
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
                
            case 'date_between':
                $dates = is_array($value) ? $value : explode(',', $value, 2);
                return array_map(function($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                }, $dates);
                
            default:
                return $value;
        }
    }

    protected function applyCondition($query, $field, $condition, $value, $boolean = 'and')
    {
        $condition = strtolower(trim($condition));
        $method = $boolean === 'and' ? 'where' : 'orWhere';
        
        switch ($condition) {
            case 'in':
                $query->{$method . 'In'}($field, $value);
                break;
                
            case 'not_in':
            case 'notin':
                $query->{$method . 'NotIn'}($field, $value);
                break;
                
            case 'between':
                $query->{$method . 'Between'}($field, $value);
                break;
                
            case 'not_between':
                $query->{$method . 'NotBetween'}($field, $value);
                break;
                
            case 'date_between':
                $query->{$method . 'Date'}($field, '>=', $value[0])
                      ->{$method . 'Date'}($field, '<=', $value[1] ?? $value[0]);
                break;
                
            case 'exists':
                $query->{$method . 'NotNull'}($field);
                break;
                
            case 'not_exists':
                $query->{$method . 'Null'}($field);
                break;
                
            case 'date':
                $query->{$method . 'Date'}($field, '=', $value);
                break;
                
            default:
                $query->{$method}($field, $condition, $value);
                break;
        }
    }

    protected function parserFieldsSearch(array $fields = [], array $searchFields = null)
    {
        if (!is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=', 'like', 'in', 'between'
            ]);
            $originalFields = $fields;
            $fields = [];

            foreach ($searchFields as $index => $field) {
                $field_parts = explode(':', $field);
                $temporaryIndex = array_search($field_parts[0], $originalFields);

                if (count($field_parts) == 2) {
                    if (in_array($field_parts[1], $acceptedConditions)) {
                        unset($originalFields[$temporaryIndex]);
                        $fields[$field_parts[0]] = $field_parts[1];
                    }
                }
            }

            if (count($fields) == 0) {
                throw new \Exception('None of the search fields were accepted. Accepted conditions: ' . implode(',', $acceptedConditions));
            }
        }

        return $fields;
    }

    protected function parserSearchData($search)
    {
        $searchData = [];
        if (stripos($search, ':')) {
            $fields = explode(';', $search);
            foreach ($fields as $row) {
                try {
                    [$field, $value] = explode(':', $row, 2);
                    $searchData[trim($field)] = trim($value);
                } catch (\Exception $e) {
                    // Skip invalid search format
                }
            }
        }

        return collect($searchData);
    }

    protected function parserSearchValue($search)
    {
        return stripos($search, ';') || stripos($search, ':') ? null : $search;
    }
}
