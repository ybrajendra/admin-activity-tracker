<?php
/**
 * Currency Plugin for Admin Activity Tracking
 * 
 * Intercepts currency save operations to track changes
 * in currency rates and configurations.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Directory\Model\Currency;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;
use Magento\Framework\App\State;

/**
 * Class CurrencyPlugin
 * 
 * Plugin for Magento\Directory\Model\Currency to track
 * currency rate changes for audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class CurrencyPlugin
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking currency changes
     * @param State $appState Application state checker
     * @param Config $config Configuration helper
     * @param Logger $logger Module logger
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        State $appState,
        Config $config,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->appState = $appState;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Track currency rate save operations
     * 
     * Intercepts currency rate save events and logs the changes
     * for audit and tracking purposes.
     * 
     * @param Currency $subject Currency model instance
     * @param mixed $result Save operation result
     * @param array $rates Currency rates data
     * @return mixed Original save result
     */
    public function afterSaveRates(
        Currency $subject,
        $result,
        $rates
    ) {
        try {
            if ($this->appState->getAreaCode() === 'adminhtml' && 
                $this->authSession->isLoggedIn() && 
                $this->config->isEnabled()) {
                
                $user = $this->authSession->getUser();
                
                $changes = json_encode([
                    'action' => 'currency_rates_updated',
                    'rates' => $rates
                ]);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'update',
                    'Currency Rates',
                    null,
                    $changes
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('CurrencyPlugin error: ' . $e->getMessage());
        }

        return $result;
    }
}