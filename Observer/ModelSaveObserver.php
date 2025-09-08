<?php
/**
 * Model Save Observer for Admin Activity Tracking
 * 
 * Observes model save events to track changes made to entities
 * in the admin panel, comparing current data with snapshots.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Model\SnapshotManager;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;
use Magento\Framework\App\State;

/**
 * Class ModelSaveObserver
 * 
 * Observer that tracks model save operations in the admin area,
 * comparing current data with previous snapshots to identify changes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Observer
 */
class ModelSaveObserver implements ObserverInterface
{
    /**
     * Admin authentication session
     * 
     * @var Session
     */
    private $authSession;
    
    /**
     * Activity logger instance
     * 
     * @var ActivityLogRepository
     */
    private $activityLogRepository;
    
    /**
     * Snapshot manager for data comparison
     * 
     * @var SnapshotManager
     */
    private $snapshotManager;
    
    /**
     * Application state checker
     * 
     * @var State
     */
    private $appState;
    
    /**
     * Configuration helper
     * 
     * @var Config
     */
    private $config;
    
    /**
     * Custom logger instance
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param Session $authSession Admin authentication session
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking changes
     * @param SnapshotManager $snapshotManager Snapshot manager for data comparison
     * @param State $appState Application state checker
     * @param Config $config Configuration helper
     * @param Logger $logger Custom logger instance
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        SnapshotManager $snapshotManager,
        State $appState,
        Config $config,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->snapshotManager = $snapshotManager;
        $this->appState = $appState;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute observer to track model save events
     * 
     * Monitors model save operations, compares with snapshots,
     * and logs changes for audit and tracking purposes.
     * 
     * @param Observer $observer Event observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            // Only track admin area actions when module is enabled
            if ($this->appState->getAreaCode() !== 'adminhtml' || !$this->authSession->isLoggedIn() || !$this->config->isEnabled()) {
                return;
            }

            $object = $observer->getEvent()->getObject();
            $entityType = get_class($object);
            
            // Check if section is excluded based on entity type
            if ($this->config->isSectionExcludedForEntity($entityType)) {
                return;
            }
            


            // Skip internal/system operations
            $skipClasses = [
                'Magento\Theme\Model\Theme\Data',
                'Magento\Framework\Session\SessionManager',
                'Magento\Backend\Model\Session',
                'Magento\Framework\View\Design\Theme\FlyweightFactory',
                'Magento\Framework\App\Config\Value',
                'Magento\Config\Model\Config\Backend\Encrypted',
                'Magento\Cookie\Model\Config\Backend\Path',
                'Magento\Cookie\Model\Config\Backend\Domain',
                'Magento\Config\Model\Config\Backend\Baseurl',
                'Magento\Security\Model\AdminSessionInfo',
                'Magento\Ui\Model\Bookmark',
                'Magento\Framework\Flag',
                'Magento\AdminNotification\Model\Inbox',
                'Magento\AdminNotification\Model\System\Message',
                'Magento\Catalog\Model\Product\Option',
                'Magento\Catalog\Model\Product\Option\Value',
                'Magento\Indexer\Model\Mview\View\State',
                'Magento\Indexer\Model\Indexer\State',
                'Magento\Eav\Model\Entity\Attribute\Set',
                'Magento\Quote\Model\Quote',
                'Magento\Quote\Model\Quote\Item',
                'Magento\Quote\Model\Quote\Item\Option',
                'Magento\Quote\Model\Quote\Address',
                'Magento\Quote\Model\Quote\Payment',
                'Magento\Quote\Model\Quote\Address\Rate',
                'Magento\Tax\Model\Sales\Order\Tax',
                'Magento\Tax\Model\Sales\Order\Tax\Item',
                'Magento\AdminAdobeIms\Model\User'
            ];
            
            // Check for interceptor classes and base class
            $baseClass = str_replace('\Interceptor', '', $entityType);
            
            // Skip all config backend classes
            if (strpos($entityType, 'Config\Backend') !== false || strpos($baseClass, 'Config\Backend') !== false) {
                return;
            }
            
            if (in_array($entityType, $skipClasses) || in_array($baseClass, $skipClasses) || !$object->hasDataChanges()) {
                return;
            }

            $user = $this->authSession->getUser();
            $action = $object->isObjectNew() ? 'create' : 'update';
            $entityId = $object->getId();

            $changes = null;
            if ($action === 'update') {
                $snapshotData = $this->snapshotManager->getSnapshot($entityType, $entityId, 'model');
                $normalizedCurrentData = $this->normalizeDataForSnapshot($object->getData());
                
                if ($snapshotData) {
                    // Compare with existing snapshot
                    $changedData = $this->compareData($snapshotData, $normalizedCurrentData);
                    if (!empty($changedData)) {
                        $changes = json_encode($changedData);
                    }
                } else {
                    // No snapshot exists, save all current data
                    $changes = json_encode($normalizedCurrentData);
                }
                
                if ($changes) {
                    $this->activityLogRepository->log(
                        $user->getId(),
                        $user->getUsername(),
                        $action,
                        $entityType,
                        $entityId,
                        $changes
                    );
                }
            } elseif ($action === 'create') {
                // Log create action with all data
                $normalizedCurrentData = $this->normalizeDataForSnapshot($object->getData());
                $changes = json_encode($normalizedCurrentData);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    $action,
                    $entityType,
                    $entityId,
                    $changes
                );
            }
            
            // Always save current snapshot for next comparison
            $this->snapshotManager->saveSnapshot($entityType, $entityId, $this->normalizeDataForSnapshot($object->getData()), 'model');
        } catch (\Exception $e) {
            $this->logger->error('ModelSaveObserver error: ' . $e->getMessage());
        }
    }

    private function compareArrays($original, $new, $parentKey = null)
    {
        $changes = [];
        
        // Check for changed or new values
        foreach ($new as $key => $value) {
            $originalValue = $original[$key] ?? null;
            $normalizedOriginal = $this->normalizeValue($originalValue);
            $normalizedNew = $this->normalizeValue($value);
            
            if (!isset($original[$key]) && !$this->valuesAreEqual(null, $normalizedNew)) {
                $changes[$key] = $value;
            } elseif (is_array($normalizedOriginal) && is_array($normalizedNew)) {
                $nestedChanges = $this->compareArrays($originalValue, $value, $key);
                if (!empty($nestedChanges)) {
                    $changes[$key] = $nestedChanges;
                }
            } elseif (!$this->valuesAreEqual($normalizedOriginal, $normalizedNew)) {
                // Special handling for media gallery image removal
                if ($parentKey === 'images' && is_array($value) && isset($value['removed']) && $value['removed'] == '1') {
                    // Include original image data with removal flag
                    $changes[$key] = array_merge($originalValue ?? [], $value);
                } else {
                    $changes[$key] = $this->formatValue($key, $value);
                }
            }
        }
        
        // Check for removed values
        foreach ($original as $key => $value) {
            if (!isset($new[$key]) && !$this->valuesAreEqual($this->normalizeValue($value), null)) {
                $changes[$key] = null;
            }
        }
        
        return $changes;
    }

    private function valuesAreEqual($original, $new)
    {
        // Handle null/empty string equivalence
        if (($original === null || $original === '') && ($new === null || $new === '')) {
            return true;
        }
        
        // Handle null to empty array
        if ($original === null && is_array($new) && empty($new)) {
            return true;
        }
        
        // Handle empty array to null
        if (is_array($original) && empty($original) && $new === null) {
            return true;
        }
        
        // Handle array comparison
        if (is_array($original) && is_array($new)) {
            // Different lengths means different
            if (count($original) !== count($new)) {
                return false;
            }
            
            // Compare array contents
            return json_encode($original) === json_encode($new);
        }
        
        // Handle string/numeric equivalence
        if (is_numeric($original) && is_numeric($new)) {
            return (float)$original === (float)$new;
        }
        
        // Handle boolean/string equivalence
        if (is_bool($original) || is_bool($new)) {
            return (bool)$original === (bool)$new;
        }
        
        return $original === $new;
    }

    private function compareData($original, $new)
    {
        $skipFields = [
            'updated_at', 'created_at', '_cache_instance_product_set_attributes',
            '_cache_instance_used_product_attributes', 'quantity_and_stock_status', 'stock_data',
            'current_product_id', 'affect_product_custom_options', 'current_store_id',
            'product_has_weight', 'use_config_gift_message_available', 'website_ids',
            'url_key_create_redirect', 'can_save_custom_options', 'save_rewrites_history',
            'is_custom_option_changed', 'custom_design_from_is_formated',
            'special_from_date_is_formated', 'custom_design_to_is_formated',
            'special_to_date_is_formated', 'news_from_date_is_formated',
            'news_to_date_is_formated',
            'force_reindex_eav_required', 'extension_attributes',
            'up_sell_products', 'cross_sell_products', 'related_products',
            'is_changed_categories', 'affected_category_ids'
        ];
        
        $changes = [];
        foreach ($new as $key => $value) {
            if (in_array($key, $skipFields) || strpos($key, 'use_config_') === 0) {
                continue;
            }
            
            $originalValue = $original[$key] ?? null;
            
            // Skip if both original and new are empty/null
            if (empty($originalValue) && empty($value)) {
                continue;
            }
            
            if (is_array($originalValue) && is_array($value)) {
                // For category_ids and state_codes show complete new array if different
                if (in_array($key, ['category_ids', 'state_codes'])) {
                    if (!$this->valuesAreEqual($originalValue, $value)) {
                        $changes[$key] = $value;
                    }
                } else {
                    $arrayChanges = $this->compareArrays($originalValue, $value, $key);
                    if (!empty($arrayChanges)) {
                        $changes[$key] = $arrayChanges;
                    }
                }
            } elseif (!$this->valuesAreEqual($this->normalizeValue($originalValue), $this->normalizeValue($value))) {
                $changes[$key] = $this->formatValue($key, $value);
            }
        }
        
        return $changes;
    }

    private function normalizeValue($value)
    {
        // Convert empty array/object to consistent empty array for comparison
        if (is_array($value) && empty($value)) {
            return [];
        }
        
        // Convert stdClass empty object to empty array
        if (is_object($value) && $value instanceof \stdClass && empty((array)$value)) {
            return [];
        }

        // Convert string "0"/"1" to integer for consistency
        if ($value === "0" || $value === "1") {
            return (int)$value;
        }

        // Trim strings to avoid "  abc" vs "abc"
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    private function normalizeDataForSnapshot($data)
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeDataForSnapshot($value);
            } elseif (is_object($value) && method_exists($value, 'getData')) {
                // Check if it's a Product object
                $objectClass = get_class($value);
                if ($objectClass === 'Magento\Catalog\Model\Product' || $objectClass === 'Magento\Catalog\Model\Product\Interceptor') {
                    // Use minimal data for Product objects
                    $normalized[$key] = [
                        'entity_id' => $value->getId(),
                        'sku' => $value->getSku(),
                        'position' => $value->getData('position') ?? null
                    ];
                } else {
                    // Use full data for other objects, but filter out cache attributes
                    $objectData = $value->getData();
                    unset($objectData['_cache_instance_product_set_attributes']);
                    $normalized[$key] = $objectData;
                }
            } elseif (is_object($value)) {
                // Convert other objects to array or skip
                $normalized[$key] = [];
            } else {
                $normalized[$key] = $value;
            }
        }
        return $normalized;
    }

    private function formatValue($key, $value)
    {
        // Mask password fields
        if (is_string($key) && stripos($key, 'password') !== false) {
            return '******';
        }
        
        // Format serialized_options for attribute option values
        if ($key === 'serialized_options' && is_array($value)) {
            $formatted = [];
            foreach ($value as $option) {
                if (is_string($option)) {
                    parse_str(urldecode($option), $parsed);
                    if (isset($parsed['optionvisual']['value'])) {
                        foreach ($parsed['optionvisual']['value'] as $optionId => $labels) {
                            if (is_array($labels) && !empty($labels[0])) {
                                $formatted[] = $labels[0];
                            }
                        }
                    }
                }
            }
            return !empty($formatted) ? implode(', ', $formatted) : $value;
        }
        
        return $value;
    }

}