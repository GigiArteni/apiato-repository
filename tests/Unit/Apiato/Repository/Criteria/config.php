<?php
// Namespaced config() polyfill for Apiato\Repository\Criteria
namespace Apiato\Repository\Criteria;

if (!function_exists('Apiato\Repository\Criteria\config')) {
    function config($key = null, $default = null) {
        $defaults = [
            'repository.criteria.params.search' => 'search',
            'repository.criteria.params.searchFields' => 'searchFields',
            'repository.criteria.params.filter' => 'filter',
            'repository.criteria.params.orderBy' => 'orderBy',
            'repository.criteria.params.sortedBy' => 'sortedBy',
            'repository.criteria.params.with' => 'with',
            'repository.apiato.features.enhanced_search' => true,
            'repository.criteria.acceptedConditions' => ['=', 'like', 'in', 'between', '>', '<', '>=', '<='],
            'repository.apiato.hashids.enabled' => false,
            'repository.apiato.hashids.auto_decode' => false,
            'repository.cache.enabled' => false,
            'repository.apiato.hashids.decode_search' => false,
            'repository.apiato.hashids.decode_filters' => false,
            'repository.apiato.hashids.fields' => ['id', '*_id'],
            'repository.apiato.hashids.auto_encode' => false,
        ];
        if ($key === null) return $defaults;
        return $defaults[$key] ?? $default;
    }
}
