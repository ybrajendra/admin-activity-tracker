<?php
/**
 * Activity Log Collection
 * 
 * Handles collection operations for admin activity log records,
 * providing filtering, sorting, and data retrieval functionality.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use CloudCommerce\AdminActivityTracker\Model\ActivityLog as ActivityLogModel;
use CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog as ActivityLogResourceModel;

/**
 * Class Collection
 * 
 * Collection class for admin activity log records,
 * extending Magento's abstract collection functionality.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize collection
     * 
     * Sets up the model and resource model classes for
     * activity log collection operations.
     * 
     * @return void
     */
    protected function _construct(): void
    {
        // Initialize with model and resource model classes
        $this->_init(ActivityLogModel::class, ActivityLogResourceModel::class);
    }
}