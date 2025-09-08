<?php
/**
 * Data Retention Source Model
 * 
 * Provides options for data retention periods in
 * admin activity tracker system configuration.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class DataRetention
 * 
 * Source model for data retention period select field in
 * system configuration, providing available retention options.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model\Config\Source
 */
class DataRetention implements OptionSourceInterface
{
    /**
     * Get options array for data retention periods
     * 
     * Returns an array of available retention periods in months
     * for admin activity data cleanup.
     * 
     * @return array Array of retention period options with value and label
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('1 Month')],
            ['value' => '2', 'label' => __('2 Months')],
            ['value' => '3', 'label' => __('3 Months')],
            ['value' => '6', 'label' => __('6 Months')],
            ['value' => '12', 'label' => __('1 Year')]
        ];
    }
}