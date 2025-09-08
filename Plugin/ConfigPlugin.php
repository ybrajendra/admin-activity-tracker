<?php
/**
 * Configuration Save Plugin for Admin Activity Tracking
 * 
 * Intercepts system configuration save operations to track
 * configuration changes made in the admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Config\Model\Config;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config as ConfigHelper;
use CloudCommerce\AdminActivityTracker\Logger\Logger;
use Magento\Framework\App\State;

/**
 * Class ConfigPlugin
 * 
 * Plugin for Magento\Config\Model\Config to track system
 * configuration changes for audit and monitoring purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class ConfigPlugin
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
     * Configuration helper instance
     * 
     * @var ConfigHelper
     */
    private $configHelper;
    
    /**
     * Flag to prevent duplicate logging
     * 
     * @var bool
     */
    private static $configSaved = false;
    
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking config changes
     * @param State $appState Application state checker
     * @param ConfigHelper $configHelper Configuration helper
     * @param Logger $logger Module logger
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        State $appState,
        ConfigHelper $configHelper,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->appState = $appState;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Track configuration save operations
     * 
     * Intercepts configuration save events and logs the changes
     * for audit and tracking purposes.
     * 
     * @param Config $subject Configuration model instance
     * @param mixed $result Save operation result
     * @return mixed Original save result
     */
    public function afterSave(
        Config $subject,
        $result
    ) {
        try {
            // Only track admin area config saves when module is enabled and store configs are not excluded
            if ($this->appState->getAreaCode() === 'adminhtml' && 
                $this->authSession->isLoggedIn() && 
                $this->configHelper->isEnabled() &&
                !$this->configHelper->isSectionExcluded('store_configurations') &&
                !self::$configSaved) {
                
                self::$configSaved = true;
                
                $user = $this->authSession->getUser();
                
                // Get the configuration data that was saved
                $configData = $subject->getData();
                $groups = $configData['groups'] ?? [];
                
                $changedFields = [];
                foreach ($groups as $groupId => $groupData) {
                    if (isset($groupData['fields'])) {
                        foreach ($groupData['fields'] as $fieldId => $fieldData) {
                            if (isset($fieldData['value'])) {
                                $changedFields[$groupId . '/' . $fieldId] = $fieldData['value'];
                            }
                        }
                    }
                }
                
                $changes = json_encode([
                    'action' => 'configuration_updated',
                    'section' => $subject->getSection(),
                    'website' => $subject->getWebsite(),
                    'store' => $subject->getStore(),
                    'changed_fields' => $changedFields
                ]);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'update',
                    'Store Configuration',
                    null,
                    $changes
                );
                
                // Reset flag after a short delay
                register_shutdown_function(function() {
                    self::$configSaved = false;
                });
            }
        } catch (\Exception $e) {
            $this->logger->error('ConfigPlugin error: ' . $e->getMessage());
        }

        return $result;
    }
}