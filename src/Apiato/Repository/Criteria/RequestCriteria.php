<?php

namespace Apiato\Repository\Criteria;

use Illuminate\Http\Request;
use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Enhanced RequestCriteria for Apiato v.13
 * Includes HashId support using vinkla/hashids + performance improvements
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

        // Apply search with HashId support
        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $searchFields = is_array($searchFields) || is_null($searchFields) ? $searchFields : explode(';', $searchFields);
            $fields = $this->parserFieldsSearch($fieldsSearchable, $searchFields);
            $isFirstField = true;
            $searchData = $this->parserSearchData($search);
            $search = $this->parserSearchValue($search);

            $modelForceAndWhere = strtolower($searchData->get('isForceAndWhere', 'or'));

            $model = $model->where(function ($query) use ($fields, $search, $searchData, $isFirstField, $modelForceAndWhere, $repository) {
                foreach ($fields as $field => $condition) {
                    if (is_numeric($field)) {
                        $field = $condition;
                        $condition = "=";
                    }
                    
                    $value = null;
                    $condition = trim(strtolower($condition));

                    if (isset($searchData[$field])) {
                        $value = $searchData[$field];
                    } else {
                        if (!is_null($search) && !empty($search)) {
                            $value = $search;
                        }
                    }

                    if ($value) {
                        // Process HashIds for ID fields using Apiato's service
                        if ($this->isIdField($field) && method_exists($repository, 'processIdValue') && config('repository.apiato.hashids.decode_search', true)) {
                            if ($condition == "like" || $condition == "ilike") {
                                $value = str_replace('%', '', $value);
                                $value = $repository->processIdValue($value);
                                $condition = "="; // Change to exact match for HashIds
                            } else {
                                $value = $repository->processIdValue($value);
                            }
                        } else {
                            // Apply like conditions for non-ID fields
                            if ($condition == "like" || $condition == "ilike") {
                                $value = "%{$value}%";
                            }
                        }

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
                                    $query->where($field, $condition, $value);
                                });
                            } else {
                                $query->where($modelTableName.'.'.$field, $condition, $value);
                            }
                            $isFirstField = false;
                        } else {
                            if (!is_null($relation)) {
                                $query->orWhereHas($relation, function ($query) use ($field, $condition, $value) {
                                    $query->where($field, $condition, $value);
                                });
                            } else {
                                $query->orWhere($modelTableName.'.'.$field, $condition, $value);
                            }
                        }
                    }
                }
            });
        }

        // Apply filters with HashId support
        if ($filter && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $fields = $this->parserFieldsSearch($fieldsSearchable, null);
            $filterData = $this->parserSearchData($filter);

            foreach ($filterData as $field => $value) {
                if (array_key_exists($field, $fields)) {
                    $condition = $fields[$field];
                    if (is_numeric($condition)) {
                        $condition = "=";
                    }

                    // Process HashIds for ID fields in filters
                    if ($this->isIdField($field) && method_exists($repository, 'processIdValue') && config('repository.apiato.hashids.decode_filters', true)) {
                        $value = $repository->processIdValue($value);
                    }

                    $model = $model->where($field, $condition, $value);
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

    protected function isIdField(string $field): bool
    {
        return $field === 'id' || str_ends_with($field, '_id');
    }

    protected function parserFieldsSearch(array $fields = [], array $searchFields = null)
    {
        if (!is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=', 'like'
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
                    [$field, $value] = explode(':', $row);
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
