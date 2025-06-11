<?php

namespace Apiato\Repository\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Apiato\Repository\Events\RepositoryBulkCreated;
use Apiato\Repository\Events\RepositoryBulkUpdated;

/**
 * Advanced Bulk Operations Trait
 * Provides high-performance bulk operations with automatic timestamp handling
 */
trait BulkOperations
{
    /**
     * Bulk insert records with automatic timestamps
     *
     * @param array $data Array of records to insert
     * @param array $options Bulk insert options
     * @return int Number of records inserted
     */
    public function bulkInsert(array $data, array $options = []): int
    {
        if (empty($data)) {
            return 0;
        }

        $options = array_merge([
            'batch_size' => 1000,
            'timestamps' => true,
            'ignore_duplicates' => false,
            'chunk_callback' => null,
        ], $options);

        // Add timestamps if enabled
        if ($options['timestamps']) {
            $now = Carbon::now();
            $data = array_map(function ($item) use ($now) {
                return array_merge($item, [
                    'created_at' => $item['created_at'] ?? $now,
                    'updated_at' => $item['updated_at'] ?? $now,
                ]);
            }, $data);
        }

        $table = $this->model->getTable();
        $totalInserted = 0;

        // Process in batches
        $chunks = array_chunk($data, $options['batch_size']);
        
        foreach ($chunks as $chunk) {
            if ($options['ignore_duplicates']) {
                $inserted = DB::table($table)->insertOrIgnore($chunk);
            } else {
                $inserted = DB::table($table)->insert($chunk);
                $inserted = count($chunk); // insert() returns bool, so count manually
            }
            
            $totalInserted += $inserted;
            
            // Call chunk callback if provided
            if ($options['chunk_callback'] && is_callable($options['chunk_callback'])) {
                $options['chunk_callback']($inserted, $totalInserted, count($data));
            }
        }

        // Fire bulk created event
        event(new RepositoryBulkCreated($this, $data));

        // Clear cache
        if (method_exists($this, 'clearCache')) {
            $this->clearCache();
        }

        return $totalInserted;
    }

    /**
     * Bulk update records with conditions
     *
     * @param array $values Values to update
     * @param array $conditions Where conditions
     * @param array $options Update options
     * @return int Number of records updated
     */
    public function bulkUpdate(array $values, array $conditions = [], array $options = []): int
    {
        $options = array_merge([
            'timestamps' => true,
            'process_hashids' => true,
        ], $options);

        // Add updated_at timestamp
        if ($options['timestamps']) {
            $values['updated_at'] = Carbon::now();
        }

        // Process HashIds in conditions
        if ($options['process_hashids']) {
            $conditions = $this->processHashIdsInConditions($conditions);
        }

        $query = $this->model->newQuery();

        // Apply conditions
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                [$field, $operator, $val] = $value;
                $query->where($field, $operator, $val);
            } else {
                $query->where($field, $value);
            }
        }

        $affectedRows = $query->update($values);

        // Fire bulk updated event
        event(new RepositoryBulkUpdated($this, $conditions, $values, $affectedRows));

        // Clear cache
        if (method_exists($this, 'clearCache')) {
            $this->clearCache();
        }

        return $affectedRows;
    }

    /**
     * Bulk upsert (insert or update) records
     *
     * @param array $data Array of records
     * @param array $uniqueColumns Columns to check for conflicts
     * @param array $updateColumns Columns to update on conflict (null = all except unique)
     * @param array $options Upsert options
     * @return array ['inserted' => int, 'updated' => int]
     */
    public function bulkUpsert(array $data, array $uniqueColumns, array $updateColumns = null, array $options = []): array
    {
        if (empty($data)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $options = array_merge([
            'batch_size' => 1000,
            'timestamps' => true,
        ], $options);

        // Add timestamps
        if ($options['timestamps']) {
            $now = Carbon::now();
            $data = array_map(function ($item) use ($now) {
                return array_merge($item, [
                    'created_at' => $item['created_at'] ?? $now,
                    'updated_at' => $item['updated_at'] ?? $now,
                ]);
            }, $data);
        }

        // If no update columns specified, use all columns except unique ones
        if ($updateColumns === null) {
            $updateColumns = array_keys($data[0] ?? []);
            $updateColumns = array_diff($updateColumns, $uniqueColumns);
        }

        $table = $this->model->getTable();
        $totalInserted = 0;
        $totalUpdated = 0;

        // Process in batches
        $chunks = array_chunk($data, $options['batch_size']);
        
        foreach ($chunks as $chunk) {
            // Get existing records to determine inserts vs updates
            $existingKeys = $this->getExistingKeys($chunk, $uniqueColumns);
            
            $toInsert = [];
            $toUpdate = [];
            
            foreach ($chunk as $record) {
                $key = $this->buildUniqueKey($record, $uniqueColumns);
                if (in_array($key, $existingKeys)) {
                    $toUpdate[] = $record;
                } else {
                    $toInsert[] = $record;
                }
            }
            
            // Perform inserts
            if (!empty($toInsert)) {
                DB::table($table)->insert($toInsert);
                $totalInserted += count($toInsert);
            }
            
            // Perform updates
            foreach ($toUpdate as $record) {
                $updateData = array_intersect_key($record, array_flip($updateColumns));
                $whereData = array_intersect_key($record, array_flip($uniqueColumns));
                
                if ($options['timestamps']) {
                    $updateData['updated_at'] = Carbon::now();
                }
                
                DB::table($table)->where($whereData)->update($updateData);
                $totalUpdated++;
            }
        }

        // Clear cache
        if (method_exists($this, 'clearCache')) {
            $this->clearCache();
        }

        return [
            'inserted' => $totalInserted,
            'updated' => $totalUpdated
        ];
    }

    /**
     * Laravel-style alias for bulkUpsert
     */
    public function bulkCreateOrUpdate(array $data, array $uniqueColumns, array $updateColumns = null, array $options = []): array
    {
        return $this->bulkUpsert($data, $uniqueColumns, $updateColumns, $options);
    }

    /**
     * Bulk delete with conditions
     *
     * @param array $conditions Where conditions
     * @param array $options Delete options
     * @return int Number of records deleted
     */
    public function bulkDelete(array $conditions, array $options = []): int
    {
        $options = array_merge([
            'process_hashids' => true,
            'soft_delete' => null, // Auto-detect from model
        ], $options);

        // Process HashIds in conditions
        if ($options['process_hashids']) {
            $conditions = $this->processHashIdsInConditions($conditions);
        }

        $query = $this->model->newQuery();

        // Apply conditions
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                [$field, $operator, $val] = $value;
                $query->where($field, $operator, $val);
            } else {
                $query->where($field, $value);
            }
        }

        // Check if model uses soft deletes
        $usesSoftDeletes = $options['soft_delete'] ?? method_exists($this->model, 'bootSoftDeletes');
        
        if ($usesSoftDeletes) {
            $affectedRows = $query->update(['deleted_at' => Carbon::now()]);
        } else {
            $affectedRows = $query->delete();
        }

        // Clear cache
        if (method_exists($this, 'clearCache')) {
            $this->clearCache();
        }

        return $affectedRows;
    }

    /**
     * Process HashIds in conditions array
     */
    protected function processHashIdsInConditions(array $conditions): array
    {
        if (!method_exists($this, 'processIdValue')) {
            return $conditions;
        }

        $processed = [];
        
        foreach ($conditions as $field => $value) {
            if ($this->isIdField($field)) {
                if (is_array($value)) {
                    // Handle array format: ['field', 'operator', 'value']
                    if (count($value) === 3) {
                        [$f, $op, $val] = $value;
                        if (is_array($val)) {
                            $val = array_map([$this, 'processIdValue'], $val);
                        } else {
                            $val = $this->processIdValue($val);
                        }
                        $processed[$field] = [$f, $op, $val];
                    } else {
                        // Handle array of values
                        $processed[$field] = array_map([$this, 'processIdValue'], $value);
                    }
                } else {
                    $processed[$field] = $this->processIdValue($value);
                }
            } else {
                $processed[$field] = $value;
            }
        }
        
        return $processed;
    }

    /**
     * Get existing unique keys from database
     */
    protected function getExistingKeys(array $data, array $uniqueColumns): array
    {
        if (empty($data)) {
            return [];
        }

        $query = $this->model->newQuery();
        
        // Build OR conditions for each record's unique key
        $query->where(function($q) use ($data, $uniqueColumns) {
            foreach ($data as $record) {
                $q->orWhere(function($subQ) use ($record, $uniqueColumns) {
                    foreach ($uniqueColumns as $column) {
                        if (isset($record[$column])) {
                            $subQ->where($column, $record[$column]);
                        }
                    }
                });
            }
        });

        return $query->pluck(implode('|', $uniqueColumns))->toArray();
    }

    /**
     * Build unique key string for a record
     */
    protected function buildUniqueKey(array $record, array $uniqueColumns): string
    {
        $keyParts = [];
        foreach ($uniqueColumns as $column) {
            $keyParts[] = $record[$column] ?? '';
        }
        return implode('|', $keyParts);
    }

    /**
     * Check if field is an ID field for HashId processing
     */
    protected function isIdField(string $field): bool
    {
        return $field === 'id' || str_ends_with($field, '_id');
    }
}