<?php
/**
 * Product Link Save Handler Plugin for Admin Activity Tracking
 * 
 * Intercepts product link save operations to track changes in
 * product relationships (related, upsell, cross-sell products).
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Catalog\Model\Product\Link\SaveHandler;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Model\SnapshotManager;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class ProductLinkSaveHandlerPlugin
 * 
 * Plugin for Magento\Catalog\Model\Product\Link\SaveHandler to track
 * product relationship changes for audit and monitoring purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class ProductLinkSaveHandlerPlugin
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
     * Module logger for debugging
     * 
     * @var Logger
     */
    private $logger;
    
    /**
     * Configuration helper instance
     * 
     * @var Config
     */
    private $config;

    /**
     * Constructor
     * 
     * @param Session $authSession Admin authentication session
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking link changes
     * @param SnapshotManager $snapshotManager Snapshot manager for data comparison
     * @param Logger $logger Module logger for debugging purposes
     * @param Config $config Configuration helper
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        SnapshotManager $snapshotManager,
        Logger $logger,
        Config $config
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->snapshotManager = $snapshotManager;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Track product link save operations
     * 
     * Intercepts product link save events, compares with snapshots,
     * and logs relationship changes for audit purposes.
     * 
     * @param SaveHandler $subject Link save handler instance
     * @param mixed $result Save operation result
     * @param string $entityType Entity type being saved
     * @param mixed $entity Product entity with updated links
     * @return mixed Original save result
     */
    public function afterExecute(
        SaveHandler $subject,
        $result,
        $entityType,
        $entity
    ) {
        try {
            $this->logger->info('ProductLinkSaveHandlerPlugin called for entity ID: ' . $entity->getId());
            
            if (!$this->authSession->isLoggedIn() || !$this->config->isEnabled() || $this->config->isSectionExcluded('products') || $entity->isObjectNew()) {
                return $result;
            }

            $entityClass = get_class($entity);
            $entityId = $entity->getId();
            $user = $this->authSession->getUser();

            // Get current relation snapshot
            $relationSnapshotData = $this->snapshotManager->getSnapshot($entityClass, $entityId, 'relation');

            // Load fresh relationship data from the entity (now has updated links)
            $currentRelationshipData = $this->loadCurrentRelationshipData($entity);
            $this->logger->info('Current relationship data: ' . json_encode($currentRelationshipData));
            
            // Compare relationship fields if we have previous data
            if ($relationSnapshotData) {
                $this->logger->info('Previous relationship data: ' . json_encode($relationSnapshotData));
                $relationshipChanges = $this->compareRelationshipData($relationSnapshotData, $currentRelationshipData);
                $this->logger->info('Relationship changes: ' . json_encode($relationshipChanges));
                
                if (!empty($relationshipChanges)) {
                    $changes = json_encode($relationshipChanges);
                    
                    // Log the changes
                    $this->activityLogRepository->log(
                        $user->getId(),
                        $user->getUsername(),
                        'update',
                        $entityClass,
                        $entityId,
                        $changes
                    );
                }
            }
            
            // Always save relation snapshot
            $this->snapshotManager->saveSnapshot($entityClass, $entityId, $currentRelationshipData, 'relation');

        } catch (\Exception $e) {
            $this->logger->error('ProductLinkSaveHandlerPlugin error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Load current product relationship data
     * 
     * Extracts product links and organizes them by relationship type
     * (related, upsell, cross-sell) for comparison purposes.
     * 
     * @param mixed $product Product entity with links
     * @return array Organized relationship data
     */
    private function loadCurrentRelationshipData($product)
    {
        $relationshipData = [
            'up_sell_products' => [],
            'cross_sell_products' => [],
            'related_products' => []
        ];
        
        // Get product links from the entity (these are the updated links being saved)
        $productLinks = $product->getProductLinks();
        
        foreach ($productLinks as $link) {
            $linkType = $link->getLinkType();
            $linkData = [
                'sku' => $link->getLinkedProductSku(),
                'position' => $link->getPosition()
            ];
            
            switch ($linkType) {
                case 'upsell':
                    $relationshipData['up_sell_products'][] = $linkData;
                    break;
                case 'crosssell':
                    $relationshipData['cross_sell_products'][] = $linkData;
                    break;
                case 'related':
                    $relationshipData['related_products'][] = $linkData;
                    break;
            }
        }
        
        return $relationshipData;
    }

    /**
     * Compare relationship data to identify changes
     * 
     * Compares original and new relationship data to determine
     * what product links have been modified.
     * 
     * @param array $original Original relationship data from snapshot
     * @param array $new Current relationship data
     * @return array Array of changed relationship fields
     */
    private function compareRelationshipData($original, $new)
    {
        $changes = [];
        $relationshipFields = ['up_sell_products', 'cross_sell_products', 'related_products'];
        
        foreach ($relationshipFields as $field) {
            $originalValue = $original[$field] ?? [];
            $newValue = $new[$field] ?? [];
            
            if (json_encode($originalValue) !== json_encode($newValue)) {
                $changes[$field] = $newValue;
            }
        }
        
        return $changes;
    }
}