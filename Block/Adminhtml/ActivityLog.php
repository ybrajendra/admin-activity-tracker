<?php
/**
 * Admin Activity Log Grid Container Block
 * 
 * This block serves as a container for the admin activity log grid,
 * providing the main structure and configuration for displaying
 * admin activity tracking data in the backend.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class ActivityLog
 * 
 * Grid container block for displaying admin activity logs
 * Extends Magento's grid container to provide a structured view
 * of all admin activities tracked by the system
 * 
 * @package CloudCommerce\AdminActivityTracker\Block\Adminhtml
 */
class ActivityLog extends Container
{
    /**
     * Initialize the grid container
     * 
     * Sets up the controller, block group, and header text for the activity log grid.
     * Removes the 'add' button since activity logs are read-only records.
     * 
     * @return void
     */
    protected function _construct()
    {
        // Set the controller name for the grid
        $this->_controller = 'adminhtml_activityLog';
        
        // Set the block group for proper module identification
        $this->_blockGroup = 'CloudCommerce_AdminActivityTracker';
        
        // Set the page header text
        $this->_headerText = __('Admin Activity Log');
        
        // Call parent constructor
        parent::_construct();
        
        // Remove add button as activity logs are read-only
        $this->removeButton('add');
    }
}