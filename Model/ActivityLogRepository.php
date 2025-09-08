<?php
/**
 * Activity Log Repository
 * 
 * Handles the logging of admin activities to the database,
 * providing a centralized repository for activity tracking.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\HTTP\Header;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Class ActivityLogRepository
 * 
 * Repository class for logging admin activities to the database,
 * providing methods to record various types of admin actions.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model
 */
class ActivityLogRepository
{
    /**
     * Resource connection for database operations
     * 
     * @var ResourceConnection
     */
    private $resourceConnection;
    
    /**
     * Remote address utility for IP detection
     * 
     * @var RemoteAddress
     */
    private $remoteAddress;
    
    /**
     * HTTP header utility for user agent detection
     * 
     * @var Header
     */
    private $httpHeader;
    
    /**
     * Store manager for store context
     * 
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor
     * 
     * @param ResourceConnection $resourceConnection Database resource connection
     * @param RemoteAddress $remoteAddress Remote address utility
     * @param Header $httpHeader HTTP header utility
     * @param StoreManagerInterface $storeManager Store manager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        RemoteAddress $remoteAddress,
        Header $httpHeader,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->remoteAddress = $remoteAddress;
        $this->httpHeader = $httpHeader;
        $this->storeManager = $storeManager;
    }

    /**
     * Log admin activity to database
     * 
     * Records an admin activity with all relevant details including
     * user information, action type, entity details, and changes.
     * 
     * @param int $adminUserId Admin user ID who performed the action
     * @param string $username Admin username who performed the action
     * @param string $action Type of action performed (create, update, delete, etc.)
     * @param string|null $entityType Class name of the entity being acted upon
     * @param int|null $entityId ID of the entity being acted upon
     * @param string|null $changes JSON string of changes made to the entity
     * @return void
     */
    public function log($adminUserId, $username, $action, $entityType = null, $entityId = null, $changes = null): void
    {
        // Get store context
        $storeId = null;
        $websiteId = null;
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $websiteId = $this->storeManager->getWebsite()->getId();
        } catch (\Exception $e) {
            // Ignore if store context not available
        }

        try {
            // Direct database insert using resource connection
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('admin_activity_log');
            
            $data = [
                'admin_user_id' => $adminUserId,
                'username' => $username,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'store_id' => $storeId,
                'website_id' => $websiteId,
                'changes' => $changes,
                'ip_address' => $this->remoteAddress->getRemoteAddress(),
                'user_agent' => $this->httpHeader->getHttpUserAgent(),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $connection->insert($tableName, $data);
        } catch (\Exception $e) {
            // Silently handle database errors to prevent breaking admin functionality
        }
    }
}