#!/bin/bash

# ========================================
# 05 - CREATE REPOSITORY EVENTS
# Creates all repository events for lifecycle management
# ========================================

echo "📝 Creating repository event classes..."

# ========================================
# BASE EVENT CLASS
# ========================================

cat > src/Apiato/Repository/Events/RepositoryEventBase.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

use Illuminate\Database\Eloquent\Model;
use Apiato\Repository\Contracts\RepositoryInterface;

/**
 * Repository Event Base
 * Base class for all repository events
 */
abstract class RepositoryEventBase
{
    protected $model;
    protected RepositoryInterface $repository;
    protected string $action;

    /**
     * Create a new event instance
     */
    public function __construct(RepositoryInterface $repository, $model)
    {
        $this->repository = $repository;
        $this->model = $model;
    }

    /**
     * Get the model instance
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the repository instance
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Get the action name
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the model class name
     */
    public function getModelClass(): string
    {
        if ($this->model instanceof Model) {
            return get_class($this->model);
        }

        return 'Unknown';
    }

    /**
     * Get event data as array
     */
    public function toArray(): array
    {
        return [
            'action' => $this->getAction(),
            'model_class' => $this->getModelClass(),
            'repository_class' => get_class($this->repository),
            'timestamp' => now()->toISOString(),
        ];
    }
}
EOF

# ========================================
# CREATING EVENTS
# ========================================

cat > src/Apiato/Repository/Events/RepositoryEntityCreating.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Creating Event
 * Fired before a new entity is created
 */
class RepositoryEntityCreating extends RepositoryEventBase
{
    protected string $action = "creating";

    /**
     * Get the attributes being created
     */
    public function getAttributes(): array
    {
        return is_array($this->model) ? $this->model : [];
    }
}
EOF

cat > src/Apiato/Repository/Events/RepositoryEntityCreated.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Created Event
 * Fired after a new entity is created
 */
class RepositoryEntityCreated extends RepositoryEventBase
{
    protected string $action = "created";

    /**
     * Get the created model's ID
     */
    public function getModelId()
    {
        if ($this->model && method_exists($this->model, 'getKey')) {
            return $this->model->getKey();
        }

        return null;
    }
}
EOF

# ========================================
# UPDATING EVENTS
# ========================================

cat > src/Apiato/Repository/Events/RepositoryEntityUpdating.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Updating Event
 * Fired before an entity is updated
 */
class RepositoryEntityUpdating extends RepositoryEventBase
{
    protected string $action = "updating";

    /**
     * Get the attributes being updated
     */
    public function getAttributes(): array
    {
        return is_array($this->model) ? $this->model : [];
    }
}
EOF

cat > src/Apiato/Repository/Events/RepositoryEntityUpdated.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Updated Event
 * Fired after an entity is updated
 */
class RepositoryEntityUpdated extends RepositoryEventBase
{
    protected string $action = "updated";

    /**
     * Get the updated model's ID
     */
    public function getModelId()
    {
        if ($this->model && method_exists($this->model, 'getKey')) {
            return $this->model->getKey();
        }

        return null;
    }

    /**
     * Get the changes made to the model
     */
    public function getChanges(): array
    {
        if ($this->model && method_exists($this->model, 'getChanges')) {
            return $this->model->getChanges();
        }

        return [];
    }

    /**
     * Get the original attributes
     */
    public function getOriginal(): array
    {
        if ($this->model && method_exists($this->model, 'getOriginal')) {
            return $this->model->getOriginal();
        }

        return [];
    }
}
EOF

# ========================================
# DELETING EVENTS
# ========================================

cat > src/Apiato/Repository/Events/RepositoryEntityDeleting.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Deleting Event
 * Fired before an entity is deleted
 */
class RepositoryEntityDeleting extends RepositoryEventBase
{
    protected string $action = "deleting";

    /**
     * Get the ID of the entity being deleted
     */
    public function getEntityId()
    {
        return $this->model;
    }
}
EOF

cat > src/Apiato/Repository/Events/RepositoryEntityDeleted.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entity Deleted Event
 * Fired after an entity is deleted
 */
class RepositoryEntityDeleted extends RepositoryEventBase
{
    protected string $action = "deleted";

    /**
     * Get the deleted model's ID
     */
    public function getModelId()
    {
        if ($this->model && method_exists($this->model, 'getKey')) {
            return $this->model->getKey();
        }

        return null;
    }

    /**
     * Get the deleted model's attributes
     */
    public function getDeletedAttributes(): array
    {
        if ($this->model && method_exists($this->model, 'getAttributes')) {
            return $this->model->getAttributes();
        }

        return [];
    }
}
EOF

# ========================================
# BULK OPERATION EVENTS
# ========================================

cat > src/Apiato/Repository/Events/RepositoryEntitiesBulkCreated.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entities Bulk Created Event
 * Fired after multiple entities are created in bulk
 */
class RepositoryEntitiesBulkCreated extends RepositoryEventBase
{
    protected string $action = "bulk_created";
    protected array $entities;

    public function __construct(RepositoryInterface $repository, array $entities)
    {
        $this->repository = $repository;
        $this->entities = $entities;
        $this->model = null; // No single model for bulk operations
    }

    /**
     * Get the created entities
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Get the count of created entities
     */
    public function getCount(): int
    {
        return count($this->entities);
    }
}
EOF

cat > src/Apiato/Repository/Events/RepositoryEntitiesBulkUpdated.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entities Bulk Updated Event
 * Fired after multiple entities are updated in bulk
 */
class RepositoryEntitiesBulkUpdated extends RepositoryEventBase
{
    protected string $action = "bulk_updated";
    protected array $conditions;
    protected array $attributes;
    protected int $affectedRows;

    public function __construct(RepositoryInterface $repository, array $conditions, array $attributes, int $affectedRows)
    {
        $this->repository = $repository;
        $this->conditions = $conditions;
        $this->attributes = $attributes;
        $this->affectedRows = $affectedRows;
        $this->model = null; // No single model for bulk operations
    }

    /**
     * Get the update conditions
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get the updated attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the number of affected rows
     */
    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
EOF

cat > src/Apiato/Repository/Events/RepositoryEntitiesBulkDeleted.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

/**
 * Repository Entities Bulk Deleted Event
 * Fired after multiple entities are deleted in bulk
 */
class RepositoryEntitiesBulkDeleted extends RepositoryEventBase
{
    protected string $action = "bulk_deleted";
    protected array $conditions;
    protected int $affectedRows;

    public function __construct(RepositoryInterface $repository, array $conditions, int $affectedRows)
    {
        $this->repository = $repository;
        $this->conditions = $conditions;
        $this->affectedRows = $affectedRows;
        $this->model = null; // No single model for bulk operations
    }

    /**
     * Get the delete conditions
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get the number of affected rows
     */
    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
EOF

# ========================================
# CRITERIA EVENTS
# ========================================

cat > src/Apiato/Repository/Events/RepositoryCriteriaApplied.php << 'EOF'
<?php

namespace Apiato\Repository\Events;

use Apiato\Repository\Contracts\CriteriaInterface;

/**
 * Repository Criteria Applied Event
 * Fired when criteria is applied to a repository
 */
class RepositoryCriteriaApplied extends RepositoryEventBase
{
    protected string $action = "criteria_applied";
    protected CriteriaInterface $criteria;

    public function __construct(RepositoryInterface $repository, CriteriaInterface $criteria)
    {
        $this->repository = $repository;
        $this->criteria = $criteria;
        $this->model = null; // No model for criteria events
    }

    /**
     * Get the applied criteria
     */
    public function getCriteria(): CriteriaInterface
    {
        return $this->criteria;
    }

    /**
     * Get the criteria class name
     */
    public function getCriteriaClass(): string
    {
        return get_class($this->criteria);
    }
}
EOF

echo "✅ REPOSITORY EVENTS CREATED!"
echo ""
echo "📝 Created event files:"
echo "  - RepositoryEventBase.php (base class for all events)"
echo ""
echo "📝 CRUD Events:"
echo "  - RepositoryEntityCreating.php (before create)"
echo "  - RepositoryEntityCreated.php (after create)"
echo "  - RepositoryEntityUpdating.php (before update)"
echo "  - RepositoryEntityUpdated.php (after update)"
echo "  - RepositoryEntityDeleting.php (before delete)"
echo "  - RepositoryEntityDeleted.php (after delete)"
echo ""
echo "📝 Bulk Operation Events:"
echo "  - RepositoryEntitiesBulkCreated.php (bulk create)"
echo "  - RepositoryEntitiesBulkUpdated.php (bulk update)"
echo "  - RepositoryEntitiesBulkDeleted.php (bulk delete)"
echo ""
echo "📝 Criteria Events:"
echo "  - RepositoryCriteriaApplied.php (criteria application)"
echo ""
echo "🚀 Key features implemented:"
echo "  - Complete event lifecycle for CRUD operations"
echo "  - Bulk operation events for performance tracking"
echo "  - Criteria application events"
echo "  - Rich event data with model information"
echo "  - Event data serialization support"
echo ""
echo "💡 Usage example:"
echo "   Event::listen(RepositoryEntityCreated::class, function(\$event) {"
echo "       logger('Created: ' . \$event->getModelClass());"
echo "   });"
echo ""
echo "🚀 Next: Run providers and commands generator"
echo "   ./06_create_providers_commands.sh"