<?php
/**
 * Admin Authentication Plugin
 * 
 * Intercepts admin login and logout events to track
 * authentication activities in the admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Backend\Model\Auth;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;
use Magento\Framework\App\State;

/**
 * Class AdminAuthPlugin
 * 
 * Plugin for Magento\Backend\Model\Auth to track admin user
 * login and logout activities for security and audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class AdminAuthPlugin
{
    /**
     * Activity log repository instance
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
     * Application state instance
     * 
     * @var State
     */
    private $appState;
    
    /**
     * Module logger instance
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param ActivityLogRepository $activityLogRepository Activity log repository for tracking events
     * @param Config $config Configuration helper
     * @param State $appState Application state checker
     * @param Logger $logger Module logger
     */
    public function __construct(
        ActivityLogRepository $activityLogRepository,
        Config $config,
        State $appState,
        Logger $logger
    ) {
        $this->activityLogRepository = $activityLogRepository;
        $this->config = $config;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * Track admin user login events
     * 
     * Intercepts successful login attempts and logs them
     * for security and audit tracking purposes.
     * 
     * @param Auth $subject Auth model instance
     * @param mixed $result Login result
     * @return mixed Original login result
     */
    public function afterLogin(Auth $subject, $result)
    {
        try {
            // Only track successful logins in admin area when module is enabled
            if ($result && $subject->getUser() && 
                $this->appState->getAreaCode() === 'adminhtml' && 
                $this->config->isEnabled()) {
                
                $user = $subject->getUser();
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'login',
                    'Admin Login',
                    null,
                    null
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('AdminAuthPlugin login error: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Track admin user logout events
     * 
     * Intercepts logout attempts and logs them for
     * security and audit tracking purposes.
     * 
     * @param Auth $subject Auth model instance
     * @param mixed $result Logout result
     * @return mixed Original logout result
     */
    public function afterLogout(Auth $subject, $result)
    {
        try {
            // Only track logouts in admin area when module is enabled
            if ($subject->getUser() && 
                $this->appState->getAreaCode() === 'adminhtml' && 
                $this->config->isEnabled()) {
                
                $user = $subject->getUser();
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'logout',
                    'Admin Logout',
                    null,
                    null
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('AdminAuthPlugin logout error: ' . $e->getMessage());
        }
        return $result;
    }
}