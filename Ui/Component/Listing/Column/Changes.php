<?php
/**
 * Changes Column UI Component
 * 
 * Formats and displays change data in the admin activity
 * log grid for better readability.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Changes
 * 
 * UI component column for formatting and displaying change data
 * in the admin activity log grid listing.
 * 
 * @package CloudCommerce\AdminActivityTracker\Ui\Component\Listing\Column
 */
class Changes extends Column
{
    /**
     * Constructor
     * 
     * @param ContextInterface $context UI component context
     * @param UiComponentFactory $uiComponentFactory UI component factory
     * @param array $components Child components
     * @param array $data Component data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare data source for changes column
     * 
     * Formats the changes data based on entity type for better
     * display in the grid column.
     * 
     * @param array $dataSource Grid data source
     * @return array Modified data source with formatted changes
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['changes'])) {
                    $item['changes'] = $this->formatChangesForDisplay($item['changes'], $item['entity_type'] ?? '');
                }
            }
        }
        return $dataSource;
    }

    /**
     * Format changes for display based on entity type
     * 
     * Applies specific formatting rules based on the entity type
     * to show only relevant change information.
     * 
     * @param string $changes JSON string of changes
     * @param string $entityType Entity type being changed
     * @return string Formatted changes string
     */
    private function formatChangesForDisplay($changes, $entityType): string
    {
        // For EAV Attribute changes, show only optionvisual
        if (strpos($entityType, 'Eav\Attribute') !== false) {
            $decoded = json_decode($changes, true);
            if (is_array($decoded) && isset($decoded['optionvisual'])) {
                return json_encode(['optionvisual' => $decoded['optionvisual']]);
            }
        }
        
        return $changes;
    }
}