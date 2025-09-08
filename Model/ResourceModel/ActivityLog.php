<?php
/**
 * Activity Log Resource Model
 * 
 * Handles database operations for admin activity log records,
 * providing CRUD functionality for activity tracking data.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class ActivityLog
 * 
 * Resource model for admin activity log table operations,
 * extending Magento's abstract database resource model.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model\ResourceModel
 */
class ActivityLog extends AbstractDb
{
    /**
     * Initialize resource model
     * 
     * Sets up the main table and primary key field for
     * admin activity log database operations.
     * 
     * @return void
     */
    protected function _construct(): void
    {
        // Initialize with table name and primary key
        $this->_init('admin_activity_log', 'id');
    }
}