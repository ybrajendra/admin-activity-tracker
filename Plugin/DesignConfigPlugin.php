<?php
/**
 * Design Configuration Plugin for Admin Activity Tracking
 * 
 * Intercepts design configuration save operations to track
 * theme and design changes made in the admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Theme\Model\DesignConfigRepository;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Model\SnapshotManager;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;
use Magento\Framework\App\State;

/**
 * Class DesignConfigPlugin
 * 
 * Plugin for Magento\Theme\Model\DesignConfigRepository to track
 * design configuration changes for audit and monitoring purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class DesignConfigPlugin
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
     * Application state checker
     * 
     * @var State
     */
    private $appState;
    
    /**
     * Snapshot manager for data comparison
     * 
     * @var SnapshotManager
     */
    private $snapshotManager;
    
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking design changes
     * @param State $appState Application state checker
     * @param SnapshotManager $snapshotManager Snapshot manager for data comparison
     * @param Config $config Configuration helper
     * @param Logger $logger Module logger
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        State $appState,
        SnapshotManager $snapshotManager,
        Config $config,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->appState = $appState;
        $this->snapshotManager = $snapshotManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Track design configuration save operations
     * 
     * Intercepts design config save events, compares with snapshots,
     * and logs changes for audit and tracking purposes.
     * 
     * @param DesignConfigRepository $subject Design config repository instance
     * @param mixed $result Save operation result
     * @param mixed $designConfig Design configuration object
     * @return mixed Original save result
     */
    public function afterSave(
        DesignConfigRepository $subject,
        $result,
        $designConfig
    ) {
        try {
            if ($this->appState->getAreaCode() === 'adminhtml' && 
                $this->authSession->isLoggedIn() && 
                $this->config->isEnabled() &&
                !$this->config->isSectionExcluded('design_configurations')) {
                
                $user = $this->authSession->getUser();
                $entityType = 'Design Configuration';
                $entityId = $designConfig->getScope() . '_' . $designConfig->getScopeId();
                
                // Get current design config data
                $currentData = [];
                $designConfigData = $designConfig->getExtensionAttributes();
                if ($designConfigData && method_exists($designConfigData, 'getDesignConfigData')) {
                    foreach ($designConfigData->getDesignConfigData() as $field) {
                        $currentData[$field->getPath()] = $field->getValue();
                    }
                }
                
                // Get snapshot data
                $snapshotData = $this->snapshotManager->getSnapshot($entityType, $entityId, 'design_config');
                
                if ($snapshotData) {
                    // Compare and get only changed fields
                    $changedFields = [];
                    foreach ($currentData as $path => $value) {
                        $originalValue = $snapshotData[$path] ?? null;
                        if ($originalValue !== $value) {
                            $changedFields[$path] = $value;
                        }
                    }
                    
                    if (!empty($changedFields)) {
                        $changes = json_encode([
                            'action' => 'design_configuration_updated',
                            'scope' => $designConfig->getScope(),
                            'scope_id' => $designConfig->getScopeId(),
                            'changed_fields' => $changedFields
                        ]);
                    } else {
                        $changes = null;
                    }
                } else {
                    // No snapshot exists, save all fields
                    $changes = json_encode([
                        'action' => 'design_configuration_updated',
                        'scope' => $designConfig->getScope(),
                        'scope_id' => $designConfig->getScopeId(),
                        'all_fields' => $currentData
                    ]);
                }
                
                if ($changes) {
                    $this->activityLogRepository->log(
                        $user->getId(),
                        $user->getUsername(),
                        'update',
                        $entityType,
                        $entityId,
                        $changes
                    );
                }
                
                // Save current snapshot
                $this->snapshotManager->saveSnapshot($entityType, $entityId, $currentData, 'design_config');
            }
        } catch (\Exception $e) {
            $this->logger->error('DesignConfigPlugin error: ' . $e->getMessage());
        }

        return $result;
    }
}