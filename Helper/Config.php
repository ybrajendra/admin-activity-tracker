<?php
/**
 * Configuration Helper for Admin Activity Tracker
 * 
 * Provides centralized access to module configuration settings
 * and utility methods for checking exclusions and settings.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 * 
 * Helper class for accessing and managing Admin Activity Tracker
 * configuration settings from system configuration.
 * 
 * @package CloudCommerce\AdminActivityTracker\Helper
 */
class Config extends AbstractHelper
{
    /**
     * XML path for module enabled/disabled setting
     */
    const XML_PATH_ENABLED = 'admin_activity_tracker/general/enabled';
    
    /**
     * XML path for excluded sections configuration
     */
    const XML_PATH_EXCLUDED_SECTIONS = 'admin_activity_tracker/general/excluded_sections';
    
    /**
     * XML path for data retention period setting
     */
    const XML_PATH_DATA_RETENTION = 'admin_activity_tracker/general/data_retention';
    
    /**
     * XML path for cleanup cron schedule setting
     */
    const XML_PATH_CLEANUP_CRON_TIME = 'admin_activity_tracker/general/cleanup_cron_time';

    /**
     * Check if the module is enabled
     * 
     * @return bool True if module is enabled, false otherwise
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get list of excluded sections
     * 
     * @return array Array of excluded section names
     */
    public function getExcludedSections(): array
    {
        $excludedSections = $this->scopeConfig->getValue(self::XML_PATH_EXCLUDED_SECTIONS, ScopeInterface::SCOPE_STORE);
        
        if (empty($excludedSections)) {
            return [];
        }
        
        // Handle both string and array formats
        if (is_string($excludedSections)) {
            return explode(',', $excludedSections);
        }
        
        if (is_array($excludedSections)) {
            return $excludedSections;
        }
        
        return [];
    }

    /**
     * Get data retention period in months
     * 
     * @return int Number of months to retain data
     */
    public function getDataRetentionMonths(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_DATA_RETENTION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get cleanup cron schedule expression
     * 
     * @return string Cron expression for cleanup job
     */
    public function getCleanupCronTime(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CLEANUP_CRON_TIME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if a specific section is excluded from tracking
     * 
     * @param string $section Section name to check
     * @return bool True if section is excluded, false otherwise
     */
    public function isSectionExcluded(string $section): bool
    {
        return in_array($section, $this->getExcludedSections());
    }
    
    /**
     * Check if an entity type should be excluded from tracking
     * 
     * Maps entity class names to their corresponding sections and
     * checks if that section is excluded from tracking.
     * 
     * @param string $entityType Full class name of the entity
     * @return bool True if entity should be excluded, false otherwise
     */
    public function isSectionExcludedForEntity(string $entityType): bool
    {
        // Map entity classes to their corresponding sections
        $entitySectionMap = [
            'Magento\Catalog\Model\Product' => 'products',
            'Magento\Catalog\Model\Product\Option\Value' => 'products',
            'Magento\CatalogInventory\Model\Adminhtml\Stock\Item' => 'products',
            'Magento\Catalog\Model\Category' => 'categories',
            'Magento\Sales\Model\Order' => 'orders',
            'Magento\Sales\Model\Order\Item' => 'orders',
            'Magento\Sales\Model\Order\Tax' => 'orders',
            'Magento\Sales\Model\Order\Tax\Item' => 'orders',
            'Magento\Sales\Model\Order\Status\History' => 'orders',
            'Magento\Sales\Model\Order\Payment' => 'orders',
            'Magento\Sales\Model\Order\Address' => 'orders',
            'Magento\Sales\Model\Order\Shipment' => 'orders',
            'Magento\Sales\Model\Order\Shipment\Item' => 'orders',
            'Magento\Sales\Model\Order\Creditmemo' => 'orders',
            'Magento\Sales\Model\Order\Creditmemo\Item' => 'orders',
            'Magento\Sales\Model\Order\Invoice' => 'orders',
            'Magento\Sales\Model\Order\Invoice\Item' => 'orders',
            'Magento\Quote\Model\Quote' => 'orders',
            'Magento\Quote\Model\Quote\Item' => 'orders',
            'Magento\Quote\Model\Quote\Item\Option' => 'orders',
            'Magento\Quote\Model\Quote\Address' => 'orders',
            'Magento\Quote\Model\Quote\Payment' => 'orders',
            'Magento\Quote\Model\Quote\Address\Rate' => 'orders',
            'Magento\Tax\Model\Sales\Order\Tax' => 'orders',
            'Magento\Tax\Model\Sales\Order\Tax\Item' => 'orders',
            'Magento\Cms\Model\Page' => 'cms',
            'Magento\Cms\Model\Block' => 'cms',
            'Magento\Customer\Model\Customer' => 'customers',
            'Magento\Customer\Model\Backend\Customer' => 'customers',
            'Magento\Customer\Model\Address' => 'customers',
            'Magento\Eav\Model\Entity\Attribute' => 'attributes',
            'Magento\Catalog\Model\ResourceModel\Eav\Attribute' => 'attributes',
            'Magento\Swatches\Model\Swatch' => 'attributes',
        ];
        
        // Remove interceptor suffix if present
        $baseClass = str_replace('\Interceptor', '', $entityType);
        
        // Get section for this entity type
        $section = $entitySectionMap[$baseClass] ?? null;
        
        // Check if section is excluded
        return $section && $this->isSectionExcluded($section);
    }
}