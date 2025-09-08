<?php
/**
 * Action Options UI Component
 * 
 * Provides filter options for action types in the
 * admin activity log grid listing.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ActionOptions
 * 
 * Option source for action type filter in admin activity log grid,
 * providing available action types for filtering.
 * 
 * @package CloudCommerce\AdminActivityTracker\Ui\Component\Listing\Column
 */
class ActionOptions implements OptionSourceInterface
{
    /**
     * Get options array for action types
     * 
     * Returns an array of available action types that can be
     * used for filtering in the admin activity log grid.
     * 
     * @return array Array of action type options with value and label
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'login', 'label' => __('Login')],
            ['value' => 'logout', 'label' => __('Logout')],
            ['value' => 'create', 'label' => __('Create')],
            ['value' => 'update', 'label' => __('Update')],
            ['value' => 'delete', 'label' => __('Delete')]
        ];
    }
}