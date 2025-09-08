<?php
/**
 * Activity Log Grid Collection
 * 
 * Specialized collection for admin activity log grid display,
 * implementing search result interface for UI components.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog\Collection as ActivityLogCollection;

/**
 * Class Collection
 * 
 * Grid collection for admin activity log records, extending the base
 * collection with search result interface implementation.
 * 
 * @package CloudCommerce\AdminActivityTracker\Model\ResourceModel\ActivityLog\Grid
 */
class Collection extends ActivityLogCollection implements SearchResultInterface
{
    /**
     * Aggregations for search results
     * 
     * @var mixed
     */
    protected $aggregations;

    /**
     * Get aggregations for search results
     * 
     * @return mixed
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Set aggregations for search results
     * 
     * @param mixed $aggregations Aggregations object
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria (not implemented)
     * 
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria (not implemented)
     * 
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria Search criteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count of items
     * 
     * @return int Total count
     */
    public function getTotalCount(): int
    {
        return $this->getSize();
    }

    /**
     * Set total count (not implemented)
     * 
     * @param int $totalCount Total count
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items (not implemented)
     * 
     * @param array|null $items Items array
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * Get collection items
     * 
     * @return array Collection items array
     */
    public function getItems(): array
    {
        return parent::getItems() ?: [];
    }
}