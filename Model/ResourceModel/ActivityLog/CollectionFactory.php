<?php
/**
 * Activity Log Collection Factory
 * 
 * Factory class for creating activity log collection instances,
 * providing a standardized way to instantiate collections.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class CollectionFactory
 * 
 * Factory for creating activity log collection instances,
 * following Magento's factory pattern for object creation.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog
 */
class CollectionFactory
{
    /**
     * Object manager for dependency injection
     * 
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     * 
     * @param ObjectManagerInterface $objectManager Object manager for creating instances
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create collection instance
     * 
     * Creates a new activity log collection instance with
     * optional data parameters.
     * 
     * @param array $data Optional data for collection initialization
     * @return Collection Activity log collection instance
     */
    public function create(array $data = []): Collection
    {
        return $this->objectManager->create(Collection::class, $data);
    }
}