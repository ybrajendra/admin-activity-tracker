<?php
/**
 * Excluded Sections Source Model
 * 
 * Provides options for sections that can be excluded from
 * admin activity tracking in system configuration.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ExcludedSections
 * 
 * Source model for excluded sections multiselect field in
 * system configuration, providing available section options.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model\Config\Source
 */
class ExcludedSections implements OptionSourceInterface
{
    /**
     * Get options array for excluded sections
     * 
     * Returns an array of available sections that can be excluded
     * from admin activity tracking.
     * 
     * @return array Array of section options with value and label
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'store_configurations', 'label' => __('Store Configurations')],
            ['value' => 'design_configurations', 'label' => __('Design Configurations')],
            ['value' => 'products', 'label' => __('Products')],
            ['value' => 'categories', 'label' => __('Categories')],
            ['value' => 'orders', 'label' => __('Orders')],
            ['value' => 'cms', 'label' => __('CMS')],
            ['value' => 'customers', 'label' => __('Customers')],
            ['value' => 'attributes', 'label' => __('Attributes')]
        ];
    }
}