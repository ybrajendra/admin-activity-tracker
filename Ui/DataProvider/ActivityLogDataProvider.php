<?php
/**
 * Activity Log Data Provider
 * 
 * Provides data for the admin activity log grid UI component,
 * handling data collection and formatting for display.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog\CollectionFactory;

/**
 * Class ActivityLogDataProvider
 * 
 * Data provider for admin activity log grid, extending Magento's
 * abstract data provider with activity log specific functionality.
 * 
 * @package CloudCommerce\AdminActivityTracker\Ui\DataProvider
 */
class ActivityLogDataProvider extends AbstractDataProvider
{
    /**
     * Constructor
     * 
     * @param string $name Data provider name
     * @param string $primaryFieldName Primary field name
     * @param string $requestFieldName Request field name
     * @param CollectionFactory $collectionFactory Activity log collection factory
     * @param array $meta Metadata array
     * @param array $data Additional data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data for the grid
     * 
     * Loads the activity log collection and formats the data
     * for display in the admin grid.
     * 
     * @return array Array containing total records count and items data
     */
    public function getData(): array
    {
        // Load collection if not already loaded
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        // Convert collection items to array format
        $items = [];
        foreach ($this->getCollection() as $item) {
            $items[] = $item->getData();
        }
        
        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => $items
        ];
    }
}