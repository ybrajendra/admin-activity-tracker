<?php
/**
 * Activity Log Model
 * 
 * Represents an admin activity log record with properties
 * and methods for managing activity tracking data.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class ActivityLog
 * 
 * Model class for admin activity log records, extending
 * Magento's abstract model with activity-specific functionality.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model
 */
class ActivityLog extends AbstractModel
{
    /**
     * Initialize model
     * 
     * Sets up the resource model class for database operations
     * related to admin activity log records.
     * 
     * @return void
     */
    protected function _construct(): void
    {
        // Initialize with resource model class
        $this->_init(\CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog::class);
    }
}