<?php
/**
 * Admin Logout Observer for Admin Activity Tracking
 * 
 * Observes admin logout events to track user
 * session termination activities in the admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class AdminLogoutObserver
 * 
 * Observer that tracks admin user logout events for
 * security and audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Observer
 */
class AdminLogoutObserver implements ObserverInterface
{
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking logout events
     * @param Config $config Configuration helper
     * @param Logger $logger Module logger
     */
    public function __construct(
        ActivityLogRepository $activityLogRepository,
        Config $config,
        Logger $logger
    ) {
        $this->activityLogRepository = $activityLogRepository;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute observer to track admin logout events
     * 
     * Monitors admin logout events and logs them
     * for security and audit tracking purposes.
     * 
     * @param Observer $observer Event observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            // Only track logouts when module is enabled
            if (!$this->config->isEnabled()) {
                return;
            }

            $user = $observer->getEvent()->getUser();
            
            if ($user) {
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
            $this->logger->error('AdminLogoutObserver error: ' . $e->getMessage());
        }
    }
}