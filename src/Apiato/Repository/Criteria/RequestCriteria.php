<?php

namespace Apiato\Repository\Criteria;

use Illuminate\Http\Request;
use Apiato\Repository\Contracts\CriteriaInterface;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Enhanced RequestCriteria with advanced search capabilities
 * Enabled by REPOSITORY_ENHANCED_SEARCH=true
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
        
        // Check if enhanced search is enabled
        $enhancedSearch = config('repository.apiato.features.enhanced_search', true);
        $forceEnhanced = $this->request->get('enhanced', false);

        // Apply relationships
        if ($with) {
            $with = is_string($with) ? explode(',', $with) : $with;
            $model = $model->with($with);
        }

        // Apply search (enhanced or basic)
        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            // PATCH: skip array search values
            if (is_array($search)) {
                // If search is an array, skip or flatten as appropriate
                // For now, skip to avoid TypeError
            } elseif (($enhancedSearch || $forceEnhanced) && $this->shouldUseEnhancedSearch($search)) {
                $model = $this->applyEnhancedSearch($model, $search, $fieldsSearchable, $searchFields, $repository);
            } else {
                $model = $this->applyBasicSearch($model, $search, $fieldsSearchable, $searchFields, $repository);
            }
        }

        // Apply filters
        if ($filter && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $model = $this->applyFilters($model, $filter, $fieldsSearchable, $repository);
        }

        // Apply ordering
        if ($orderBy) {
            $model = $this->applyOrdering($model, $orderBy, $sortedBy);
        }

        return $model;
    }

    /**
     * Determine if enhanced search should be used
     */
    protected function shouldUseEnhancedSearch($search): bool
    {
        // Use enhanced search for:
        // 1. Quoted phrases: "john smith"
        // 2. Boolean operators: +required -excluded
        // 3. Fuzzy operators: word~2
        // 4. Multi-word searches without field specification
        if (!is_string($search)) {
            return false;
        }
        return preg_match('/["+~-]|^[^:]*\s+[^:]*$/', $search);
    }

    /**
     * Apply enhanced search with advanced features
     */
    protected function applyEnhancedSearch($model, $search, $fieldsSearchable, $searchFields, $repository)
    {
        // Parse enhanced search query
        $searchTerms = $this->parseEnhancedSearch($search);
        
        return $model->where(function($query) use ($searchTerms, $fieldsSearchable, $repository) {
            $this->buildEnhancedQuery($query, $searchTerms, $fieldsSearchable, $repository);
        });
    }

    /**
     * Parse enhanced search query into structured terms
     */
    protected function parseEnhancedSearch($search): array
    {
        $terms = [
            'required' => [],    // +term or "quoted phrase"
            'excluded' => [],    // -term
            'optional' => [],    // regular terms
            'fuzzy' => [],       // term~distance
            'phrases' => []      // "exact phrases"
        ];

        // Extract quoted phrases first
        preg_match_all('/"([^"]+)"/', $search, $phrases);
        foreach ($phrases[1] as $phrase) {
            $terms['phrases'][] = trim($phrase);
            $search = str_replace('"' . $phrase . '"', '', $search);
        }

        // Extract operators and terms
        preg_match_all('/([+\-]?)(\w+(?:~\d+)?)/', $search, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $operator = $match[1] ?? '';
            $term = $match[2] ?? '';
            
            if (empty($term)) continue;

            // Check for fuzzy search (word~2)
            if (preg_match('/(\w+)~(\d+)/', $term, $fuzzyMatch)) {
                $terms['fuzzy'][] = [
                    'term' => $fuzzyMatch[1],
                    'distance' => (int)$fuzzyMatch[2]
                ];
            } elseif ($operator === '+') {
                $terms['required'][] = $term;
            } elseif ($operator === '-') {
                $terms['excluded'][] = $term;
            } else {
                $terms['optional'][] = $term;
            }
        }

        return $terms;
    }

    /**
     * Build enhanced query with relevance scoring
     */
    protected function buildEnhancedQuery($query, $searchTerms, $fieldsSearchable, $repository)
    {
        $relevanceSelects = [];
        $relevanceScore = 0;

        // Get searchable fields
        $searchableFields = $this->getSearchableFieldNames($fieldsSearchable);

        // Required terms (must match)
        foreach ($searchTerms['required'] as $term) {
            $query->where(function($subQuery) use ($term, $searchableFields, $repository) {
                $this->applyTermToFields($subQuery, $term, $searchableFields, 'or', $repository);
            });
        }

        // Excluded terms (must not match)
        foreach ($searchTerms['excluded'] as $term) {
            $query->whereNot(function($subQuery) use ($term, $searchableFields, $repository) {
                $this->applyTermToFields($subQuery, $term, $searchableFields, 'or', $repository);
            });
        }

        // Exact phrases (high relevance)
        foreach ($searchTerms['phrases'] as $phrase) {
            $query->where(function($subQuery) use ($phrase, $searchableFields, $repository) {
                $this->applyTermToFields($subQuery, $phrase, $searchableFields, 'or', $repository, '=');
            });
            
            // Add relevance scoring for phrases
            foreach ($searchableFields as $field) {
                $relevanceSelects[] = "CASE WHEN {$field} LIKE '%{$phrase}%' THEN 10 ELSE 0 END";
            }
        }

        // Optional terms (boost relevance)
        if (!empty($searchTerms['optional'])) {
            $query->where(function($subQuery) use ($searchTerms, $searchableFields, $repository) {
                foreach ($searchTerms['optional'] as $term) {
                    $subQuery->orWhere(function($termQuery) use ($term, $searchableFields, $repository) {
                        $this->applyTermToFields($termQuery, $term, $searchableFields, 'or', $repository);
                    });
                }
            });

            // Add relevance scoring for optional terms
            foreach ($searchTerms['optional'] as $term) {
                foreach ($searchableFields as $field) {
                    $relevanceSelects[] = "CASE WHEN {$field} LIKE '%{$term}%' THEN 5 ELSE 0 END";
                }
            }
        }

        // Fuzzy matching (using SOUNDEX or LEVENSHTEIN if available)
        foreach ($searchTerms['fuzzy'] as $fuzzyTerm) {
            $term = $fuzzyTerm['term'];
            $distance = $fuzzyTerm['distance'];
            
            $query->orWhere(function($subQuery) use ($term, $searchableFields, $repository) {
                if (function_exists('soundex')) {
                    // Use SOUNDEX for fuzzy matching
                    foreach ($searchableFields as $field) {
                        $subQuery->orWhereRaw("SOUNDEX({$field}) = SOUNDEX(?)", [$term]);
                    }
                } else {
                    // Fallback to similar LIKE patterns
                    $this->applyTermToFields($subQuery, $term, $searchableFields, 'or', $repository);
                }
            });
        }

        // Add relevance scoring if we have selects
        if (!empty($relevanceSelects)) {
            $relevanceFormula = '(' . implode(' + ', $relevanceSelects) . ') as relevance_score';
            $query->selectRaw('*, ' . $relevanceFormula);
            $query->orderByDesc('relevance_score');
        }
    }

    /**
     * Apply a search term to multiple fields
     */
    protected function applyTermToFields($query, $term, $fields, $operator = 'or', $repository = null, $condition = 'like')
    {
        foreach ($fields as $field) {
            $value = $condition === 'like' ? "%{$term}%" : $term;
            
            // Handle HashId fields
            if ($this->isIdField($field) && $repository && method_exists($repository, 'processIdValue')) {
                $processedValue = $repository->processIdValue($term);
                $value = $condition === 'like' ? "%{$processedValue}%" : $processedValue;
            }

            // Handle relationships
            if (strpos($field, '.') !== false) {
                $this->applyRelationshipSearch($query, $field, $value, $condition, $operator);
            } else {
                if ($operator === 'or') {
                    $query->orWhere($field, $condition === 'like' ? 'LIKE' : '=', $value);
                } else {
                    $query->where($field, $condition === 'like' ? 'LIKE' : '=', $value);
                }
            }
        }
    }

    /**
     * Apply relationship search
     */
    protected function applyRelationshipSearch($query, $field, $value, $condition, $operator)
    {
        $parts = explode('.', $field);
        $relation = array_shift($parts);
        $relationField = implode('.', $parts);

        $method = $operator === 'or' ? 'orWhereHas' : 'whereHas';
        
        $query->$method($relation, function($relationQuery) use ($relationField, $value, $condition) {
            $relationQuery->where($relationField, $condition === 'like' ? 'LIKE' : '=', $value);
        });
    }

    /**
     * Get field names from searchable configuration
     */
    protected function getSearchableFieldNames($fieldsSearchable): array
    {
        $fields = [];
        
        foreach ($fieldsSearchable as $key => $value) {
            if (is_numeric($key)) {
                $fields[] = $value;
            } else {
                $fields[] = $key;
            }
        }
        
        return $fields;
    }

    /**
     * Apply basic search (original functionality)
     */
    protected function applyBasicSearch($model, $search, $fieldsSearchable, $searchFields, $repository)
    {
        $searchFields = is_array($searchFields) || is_null($searchFields) ? $searchFields : explode(';', $searchFields);
        $fields = $this->parserFieldsSearch($fieldsSearchable, $searchFields);
        $isFirstField = true;
        $searchData = $this->parserSearchData($search);
        $search = $this->parserSearchValue($search);

        $modelForceAndWhere = strtolower($searchData->get('isForceAndWhere', 'or'));

        // PATCH: filter $fields to only string keys
        $fields = array_filter($fields, 'is_string', ARRAY_FILTER_USE_KEY);
        foreach ($fields as $field => $condition) {
            // PATCH: skip non-string field names
            if (!is_string($field)) continue;
            $value = null;
            $condition = trim(strtolower($condition));

            if (isset($searchData[$field])) {
                $value = ($condition == "like" || $condition == "ilike") ? "%{$searchData[$field]}%" : $searchData[$field];
            } else {
                if (!is_null($search) && !empty($search)) {
                    $value = ($condition == "like" || $condition == "ilike") ? "%{$search}%" : $search;
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
                // PATCH: only call stripos if $field is string (already checked above)
                if (stripos($field, '.')) {
                    $explodeField = explode('.', $field);
                    $field = array_pop($explodeField);
                    $relation = implode('.', $explodeField);
                }

                $modelTableName = $model->getModel()->getTable();
                if ($isFirstField || $modelForceAndWhere == 'and') {
                    if (!is_null($relation)) {
                        $model->whereHas($relation, function ($query) use ($field, $condition, $value) {
                            $query->where($field, $condition, $value);
                        });
                    } else {
                        $model->where($modelTableName.'.'.$field, $condition, $value);
                    }
                    $isFirstField = false;
                } else {
                    if (!is_null($relation)) {
                        $model->orWhereHas($relation, function ($query) use ($field, $condition, $value) {
                            $query->where($field, $condition, $value);
                        });
                    } else {
                        $model->orWhere($modelTableName.'.'.$field, $condition, $value);
                    }
                }
            }
        }
        return $model;
    }

    /**
     * Check if field is an ID field
     */
    protected function isIdField(string $field): bool
    {
        return $field === 'id' || str_ends_with($field, '_id');
    }

    // Include all the other helper methods from the original RequestCriteria
    // (parserFieldsSearch, parserSearchData, parserSearchValue, applyFilters, applyOrdering, etc.)
    
    protected function parserFieldsSearch(array $fields = [], array $searchFields = null)
    {
        if (!is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=', 'like'
            ]);
            $originalFields = $fields;
            $fields = [];

            foreach ($searchFields as $index => $field) {
                // PATCH: skip array field names
                if (is_array($field)) continue;
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
        // PATCH: skip non-string $search
        if (!is_string($search)) {
            return collect([]);
        }
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
        // PATCH: skip non-string $search
        if (!is_string($search)) {
            return null;
        }
        return stripos($search, ';') || stripos($search, ':') ? null : $search;
    }

    protected function applyFilters($model, $filter, $fieldsSearchable, $repository)
    {
        $fields = $this->parserFieldsSearch($fieldsSearchable, null);
        $filterData = $this->parserSearchData($filter);

        foreach ($filterData as $field => $value) {
            if (array_key_exists($field, $fields)) {
                $condition = $fields[$field];
                if (is_numeric($condition)) {
                    $condition = "=";
                }

                // PATCH: skip array values for string-only operations
                if (is_array($value)) {
                    // If the condition is '=', treat as whereIn, else skip
                    if ($condition === '=') {
                        $model = $model->whereIn($field, $value);
                    }
                    continue;
                }

                if ($this->isIdField($field) && method_exists($repository, 'processIdValue') && config('repository.apiato.hashids.decode_filters', true)) {
                    $value = $repository->processIdValue($value);
                }

                $model = $model->where($field, $condition, $value);
            }
        }

        return $model;
    }

    protected function applyOrdering($model, $orderBy, $sortedBy)
    {
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

        return $model;
    }
}