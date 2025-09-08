<?php
/**
 * Cron Job for Admin Activity Data Cleanup
 * 
 * Automatically removes old admin activity logs and snapshots based on
 * the configured data retention period to prevent database bloat.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Cron;

use CloudCommerce\AdminActivityTracker\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class CleanupData
 * 
 * Cron job class responsible for cleaning up old admin activity data
 * based on the configured retention period in system configuration.
 * 
 * @package CloudCommerce\AdminActivityTracker\Cron
 */
class CleanupData
{
    /**
     * Configuration helper instance
     * 
     * @var Config
     */
    private $config;
    
    /**
     * Resource connection for database operations
     * 
     * @var ResourceConnection
     */
    private $resourceConnection;
    
    /**
     * Logger instance for cleanup operations
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param Config $config Configuration helper
     * @param ResourceConnection $resourceConnection Database resource connection
     * @param Logger $logger Custom logger for admin activity
     */
    public function __construct(
        Config $config,
        ResourceConnection $resourceConnection,
        Logger $logger
    ) {
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute cleanup process
     * 
     * Removes old activity logs and snapshots based on the configured
     * data retention period. Only runs if the module is enabled.
     * 
     * @return void
     */
    public function execute(): void
    {
        // Skip cleanup if module is disabled
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            // Get retention period from configuration
            $retentionMonths = $this->config->getDataRetentionMonths();
            
            // Calculate cutoff date for deletion
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionMonths} months"));
            
            // Get database connection
            $connection = $this->resourceConnection->getConnection();
            
            // Clean activity logs table
            $activityTable = $this->resourceConnection->getTableName('admin_activity_log');
            $deletedActivity = $connection->delete($activityTable, ['created_at < ?' => $cutoffDate]);
            
            // Clean snapshots table
            $snapshotTable = $this->resourceConnection->getTableName('admin_activity_entity_snapshot');
            $deletedSnapshots = $connection->delete($snapshotTable, ['created_at < ?' => $cutoffDate]);
            
            // Log successful cleanup
            $this->logger->info(
                "Admin Activity Tracker cleanup completed. Deleted {$deletedActivity} activity records and {$deletedSnapshots} snapshot records older than {$cutoffDate}"
            );
            
        } catch (\Exception $e) {
            // Log cleanup failure
            $this->logger->error("Admin Activity Tracker cleanup failed: " . $e->getMessage());
        }
    }
}