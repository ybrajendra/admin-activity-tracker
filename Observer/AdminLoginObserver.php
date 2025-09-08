<?php
/**
 * Admin Login Observer for Admin Activity Tracking
 * 
 * Observes admin login events to track successful
 * authentication activities in the admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class AdminLoginObserver
 * 
 * Observer that tracks admin user login events for
 * security and audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Observer
 */
class AdminLoginObserver implements ObserverInterface
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking login events
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
     * Execute observer to track admin login events
     * 
     * Monitors successful admin login events and logs them
     * for security and audit tracking purposes.
     * 
     * @param Observer $observer Event observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            // Only track logins when module is enabled
            if (!$this->config->isEnabled()) {
                return;
            }

            $user = $observer->getEvent()->getUser();
            
            if ($user) {
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
            $this->logger->error('AdminLoginObserver error: ' . $e->getMessage());
        }
    }
}