#!/bin/bash

# ========================================
# 02 - CREATE ALL INTERFACES
# Creates all contract interfaces for the repository pattern
# ========================================

echo "📝 Creating all repository interfaces..."

# ========================================
# REPOSITORY INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/RepositoryInterface.php << 'EOF'
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
EOF

# ========================================
# CRITERIA INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/CriteriaInterface.php << 'EOF'
<?php

namespace Apiato\Repository\Contracts;

/**
 * Criteria Interface
 * Defines the contract for applying criteria to repository queries
 */
interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder $model
     * @param RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository);
}
EOF

# ========================================
# REPOSITORY CRITERIA INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/RepositoryCriteriaInterface.php << 'EOF'
<?php

namespace Apiato\Repository\Contracts;

use Illuminate\Support\Collection;

/**
 * Repository Criteria Interface
 * Defines the contract for managing criteria in repositories
 */
interface RepositoryCriteriaInterface
{
    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     * @return $this
     */
    public function pushCriteria($criteria);

    /**
     * Pop Criteria
     *
     * @param $criteria
     * @return $this
     */
    public function popCriteria($criteria);

    /**
     * Get Collection of Criteria
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     * @return mixed
     */
    public function getByCriteria(CriteriaInterface $criteria);

    /**
     * Skip Criteria
     *
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true);

    /**
     * Clear all Criteria
     *
     * @return $this
     */
    public function clearCriteria();

    /**
     * Apply criteria in current Query
     *
     * @return $this
     */
    public function applyCriteria();
}
EOF

# ========================================
# PRESENTER INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/PresenterInterface.php << 'EOF'
<?php

namespace Apiato\Repository\Contracts;

/**
 * Presenter Interface
 * Defines the contract for data presentation
 */
interface PresenterInterface
{
    /**
     * Prepare data to present
     *
     * @param mixed $data
     * @return mixed
     */
    public function present($data);
}
EOF

# ========================================
# PRESENTABLE INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/Presentable.php << 'EOF'
<?php

namespace Apiato\Repository\Contracts;

/**
 * Presentable Interface
 * Defines the contract for objects that can be presented
 */
interface Presentable
{
    /**
     * Set Presenter
     *
     * @param PresenterInterface $presenter
     * @return mixed
     */
    public function setPresenter(PresenterInterface $presenter);

    /**
     * Get Presenter
     *
     * @return mixed
     */
    public function presenter();
}
EOF

# ========================================
# CACHEABLE INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/CacheableInterface.php << 'EOF'
<?php

namespace Apiato\Repository\Contracts;

/**
 * Cacheable Interface
 * Defines the contract for caching functionality
 */
interface CacheableInterface
{
    /**
     * Set Cache Repository
     *
     * @param mixed $repository
     * @return $this
     */
    public function setCacheRepository($repository);

    /**
     * Get Cache Repository
     *
     * @return mixed
     */
    public function getCacheRepository();

    /**
     * Get Cache Key
     *
     * @param string $method
     * @param mixed $args
     * @return string
     */
    public function getCacheKey($method, $args = null);

    /**
     * Get Cache Minutes
     *
     * @return int
     */
    public function getCacheMinutes();

    /**
     * Skip Cache
     *
     * @param bool $status
     * @return $this
     */
    public function skipCache($status = true);
}
EOF

# ========================================
# VALIDATOR INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/ValidatorInterface.php << 'EOF'
<?php

namespace Apiato\Repository\Contracts;

/**
 * Validator Interface
 * Defines the contract for data validation
 */
interface ValidatorInterface
{
    const RULE_CREATE = 'create';
    const RULE_UPDATE = 'update';

    /**
     * Set data to validate
     *
     * @param array $input
     * @return $this
     */
    public function with(array $input);

    /**
     * Validate data for create action
     *
     * @return bool
     */
    public function passesCreate();

    /**
     * Validate data for update action
     *
     * @return bool
     */
    public function passesUpdate();

    /**
     * Validate data for given action
     *
     * @param string|null $action
     * @return bool
     */
    public function passes($action = null);

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors();
}
EOF

# ========================================
# TRANSFORMER INTERFACE
# ========================================

cat > src/Apiato/Repository/Contracts/TransformerInterface.php << 'EOF'
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
EOF

echo "✅ ALL INTERFACES CREATED!"
echo ""
echo "📝 Created interface files:"
echo "  - RepositoryInterface.php (core repository contract)"
echo "  - CriteriaInterface.php (criteria application contract)"
echo "  - RepositoryCriteriaInterface.php (criteria management contract)"
echo "  - PresenterInterface.php (data presentation contract)"
echo "  - Presentable.php (presentable objects contract)"
echo "  - CacheableInterface.php (caching functionality contract)"
echo "  - ValidatorInterface.php (data validation contract)"
echo "  - TransformerInterface.php (data transformation contract)"
echo ""
echo "🚀 Next: Run core classes generator"
echo "   ./03_create_core_classes.sh"