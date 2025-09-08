<?php
/**
 * Configurable Product Save Plugin for Admin Activity Tracking
 * 
 * Intercepts configurable product save operations to track changes
 * in child product associations and configurations.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class ConfigurableProductSavePlugin
 * 
 * Plugin for configurable product resource model to track changes
 * in child product associations for audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class ConfigurableProductSavePlugin
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
     * Configuration helper instance
     * 
     * @var Config
     */
    private $config;
    
    /**
     * Module logger instance
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param Session $authSession Admin authentication session
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking product changes
     * @param Config $config Configuration helper
     * @param Logger $logger Module logger
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        Config $config,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Store current child products before save operation
     * 
     * Captures the current state of child product associations
     * before the save operation for comparison purposes.
     * 
     * @param Configurable $subject Configurable product resource model
     * @param mixed $mainProduct Main configurable product
     * @param array $productIds Array of child product IDs
     * @return array Original parameters
     */
    public function beforeSaveProducts(
        Configurable $subject,
        $mainProduct,
        array $productIds
    ) {
        // Store current child products before save
        if ($this->authSession->isLoggedIn() && $this->config->isEnabled() && !$this->config->isSectionExcluded('products') && $mainProduct->getId()) {
            $this->previousChildIds = $this->getCurrentChildIds($subject, $mainProduct->getId());
        }
        return [$mainProduct, $productIds];
    }

    /**
     * Track configurable product child association changes
     * 
     * Compares previous and current child product associations
     * and logs any changes for audit purposes.
     * 
     * @param Configurable $subject Configurable product resource model
     * @param mixed $result Save operation result
     * @param mixed $mainProduct Main configurable product
     * @param array $productIds Array of child product IDs
     * @return mixed Original save result
     */
    public function afterSaveProducts(
        Configurable $subject,
        $result,
        $mainProduct,
        array $productIds
    ) {
        try {
            if (!$this->authSession->isLoggedIn() || !$this->config->isEnabled() || $this->config->isSectionExcluded('products') || !$mainProduct->getId()) {
                return $result;
            }

            $user = $this->authSession->getUser();
            $entityClass = get_class($mainProduct);
            $entityId = $mainProduct->getId();

            $previousIds = $this->previousChildIds ?? [];
            $currentIds = $productIds;

            $addedIds = array_diff($currentIds, $previousIds);
            $removedIds = array_diff($previousIds, $currentIds);

            if (!empty($addedIds) || !empty($removedIds)) {
                $changes = [];
                
                if (!empty($addedIds)) {
                    $changes['added'] = $this->getChildProductData($addedIds);
                }
                
                if (!empty($removedIds)) {
                    $changes['removed'] = $this->getChildProductData($removedIds);
                }
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'update',
                    $entityClass,
                    $entityId,
                    json_encode($changes)
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('ConfigurableProductSavePlugin error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get current child product IDs for a configurable product
     * 
     * @param Configurable $subject Configurable product resource model
     * @param int $parentId Parent product ID
     * @return array Array of child product IDs
     */
    private function getCurrentChildIds($subject, $parentId)
    {
        $childrenData = $subject->getChildrenIds($parentId);
        return isset($childrenData[0]) ? array_keys($childrenData[0]) : [];
    }

    /**
     * Get child product data for logging
     * 
     * Retrieves product information (ID, SKU) for the given product IDs
     * to include in the activity log.
     * 
     * @param array $productIds Array of product IDs
     * @return array Array of product data
     */
    private function getChildProductData(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productRepository = $objectManager->get('Magento\Catalog\Api\ProductRepositoryInterface');
        
        $childData = [];
        foreach ($productIds as $productId) {
            try {
                $product = $productRepository->getById($productId);
                $childData[] = [
                    'id' => $product->getId(),
                    'sku' => $product->getSku()
                ];
            } catch (\Exception $e) {
                // Skip if product not found
            }
        }
        
        return $childData;
    }

    /**
     * Storage for previous child product IDs
     * 
     * @var array
     */
    private $previousChildIds = [];
}