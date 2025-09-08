<?php
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as SystemStore;

/**
 * Store options for admin activity log grid filter
 */
class StoreOptions implements OptionSourceInterface
{
    /**
     * @var SystemStore
     */
    private $systemStore;

    /**
     * @param SystemStore $systemStore
     */
    public function __construct(SystemStore $systemStore)
    {
        $this->systemStore = $systemStore;
    }

    /**
     * Get store options for multiselect filter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => '0', 'label' => __('All Store Views')]
        ];
        
        foreach ($this->systemStore->getWebsiteCollection() as $website) {
            $websiteLabel = $website->getName();
            
            foreach ($this->systemStore->getGroupCollection() as $group) {
                if ($group->getWebsiteId() != $website->getId()) {
                    continue;
                }
                
                $groupLabel = $websiteLabel . ' > ' . $group->getName();
                
                foreach ($this->systemStore->getStoreCollection() as $store) {
                    if ($store->getGroupId() != $group->getId()) {
                        continue;
                    }
                    
                    $options[] = [
                        'value' => $store->getId(),
                        'label' => $groupLabel . ' > ' . $store->getName()
                    ];
                }
            }
        }
        
        return $options;
    }
}