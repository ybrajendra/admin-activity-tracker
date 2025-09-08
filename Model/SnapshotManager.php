<?php
/**
 * Snapshot Manager Model
 * 
 * Manages entity snapshots for comparison purposes in admin
 * activity tracking, providing save and retrieve functionality.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * Class SnapshotManager
 * 
 * Service class for managing entity snapshots used in change
 * detection and activity tracking comparisons.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model
 */
class SnapshotManager
{
    /**
     * Resource connection for database operations
     * 
     * @var ResourceConnection
     */
    private $resource;

    /**
     * Constructor
     * 
     * @param ResourceConnection $resource Database resource connection
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Save entity snapshot to database
     * 
     * Stores a JSON-encoded snapshot of entity data for later comparison
     * to detect changes in admin activities.
     * 
     * @param string $entityType Class name of the entity
     * @param int $entityId ID of the entity
     * @param array $data Entity data to snapshot
     * @param string $dataType Type of snapshot (model, relation, etc.)
     * @return void
     */
    public function saveSnapshot($entityType, $entityId, $data, $dataType = 'model'): void
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('admin_activity_entity_snapshot');
        
        // Filter out ignore fields before saving
        $filteredData = $this->filterIgnoreFields($data);
        
        $connection->insertOnDuplicate(
            $tableName,
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'data_snapshot' => json_encode($filteredData),
                'data_type' => $dataType,
                'created_at' => date('Y-m-d H:i:s')
            ],
            ['data_snapshot', 'data_type', 'created_at']
        );
    }

    /**
     * Retrieve entity snapshot from database
     * 
     * Fetches and decodes a previously saved entity snapshot
     * for comparison with current entity state.
     * 
     * @param string $entityType Class name of the entity
     * @param int $entityId ID of the entity
     * @param string $dataType Type of snapshot (model, relation, etc.)
     * @return array|null Snapshot data array or null if not found
     */
    public function getSnapshot($entityType, $entityId, $dataType = 'model'): ?array
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('admin_activity_entity_snapshot');
        
        $select = $connection->select()
            ->from($tableName, ['data_snapshot'])
            ->where('entity_type = ?', $entityType)
            ->where('entity_id = ?', $entityId)
            ->where('data_type = ?', $dataType)
            ->order('created_at DESC')
            ->limit(1);
            
        $result = $connection->fetchOne($select);
        return $result ? json_decode($result, true) : null;
    }

    /**
     * Filter out ignored fields from snapshot data
     * 
     * Removes fields that should not be included in snapshots
     * to avoid unnecessary change detection noise.
     * 
     * @param array $data Data array to filter
     * @return array Filtered data array
     */
    private function filterIgnoreFields($data): array
    {
        $ignoreFields = [
            '_cache_instance_product_set_attributes'
        ];
        
        $filtered = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $ignoreFields)) {
                if (is_array($value)) {
                    $filtered[$key] = $this->filterIgnoreFields($value);
                } else {
                    $filtered[$key] = $value;
                }
            }
        }
        
        return $filtered;
    }
}