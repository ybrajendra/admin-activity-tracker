<?php
/**
 * Model Delete Observer for Admin Activity Tracking
 * 
 * Observes model delete events to track entity deletions
 * performed in the admin panel for audit purposes.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;
use Magento\Framework\App\State;

/**
 * Class ModelDeleteObserver
 * 
 * Observer that tracks model delete operations in the admin area
 * to maintain an audit trail of deleted entities.
 * 
 * @package CloudCommerce\AdminActivityTracker\Observer
 */
class ModelDeleteObserver implements ObserverInterface
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
     * Configuration helper
     * 
     * @var Config
     */
    private $config;
    
    /**
     * Custom logger instance
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     * 
     * @param Session $authSession Admin authentication session
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking deletions
     * @param State $appState Application state checker
     * @param Config $config Configuration helper
     * @param Logger $logger Custom logger instance
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
     * Execute observer to track model delete events
     * 
     * Monitors model delete operations and logs them
     * for audit and tracking purposes.
     * 
     * @param Observer $observer Event observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            // Only track admin area actions when module is enabled
            if ($this->appState->getAreaCode() !== 'adminhtml' || !$this->authSession->isLoggedIn() || !$this->config->isEnabled()) {
                return;
            }

            $object = $observer->getEvent()->getObject();
            $entityType = get_class($object);
            
            // Check if section is excluded based on entity type
            if ($this->config->isSectionExcludedForEntity($entityType)) {
                return;
            }
            
            // Skip internal/system operations
            $skipClasses = [
                'Magento\Theme\Model\Theme\Data',
                'Magento\Framework\Session\SessionManager',
                'Magento\Backend\Model\Session',
                'Magento\Framework\View\Design\Theme\FlyweightFactory',
                'Magento\Framework\App\Config\Value',
                'Magento\Security\Model\AdminSessionInfo',
                'Magento\Ui\Model\Bookmark',
                'Magento\Framework\Flag',
                'Magento\AdminNotification\Model\Inbox',
                'Magento\AdminNotification\Model\System\Message'
            ];
            
            // Check for interceptor classes and base class
            $baseClass = str_replace('\Interceptor', '', $entityType);
            if (in_array($entityType, $skipClasses) || in_array($baseClass, $skipClasses)) {
                return;
            }

            $user = $this->authSession->getUser();

            $this->activityLogRepository->log(
                $user->getId(),
                $user->getUsername(),
                'delete',
                $entityType,
                $object->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('ModelDeleteObserver error: ' . $e->getMessage());
        }
    }

}