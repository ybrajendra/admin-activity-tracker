<?php
/**
 * Order Status Plugin for Admin Activity Tracking
 * 
 * Intercepts order status operations to track changes
 * in order status assignments and configurations.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Status;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class OrderStatusPlugin
 * 
 * Plugin for order status resource model to track status
 * assignment changes for audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class OrderStatusPlugin
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking status changes
     * @param Config $config Configuration helper
     * @param Logger $logger Module logger
     */
    public function __construct(
        Session $authSession,
        ActivityLogRepository $activityLogRepository,
        Config $config,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->activityLogRepository = $activityLogRepository;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Track order status state assignments
     * 
     * Intercepts status-to-state assignment operations and logs them
     * for audit and tracking purposes.
     * 
     * @param Status $subject Order status resource model
     * @param mixed $result Assignment operation result
     * @param string $status Order status code
     * @param string $state Order state
     * @param bool $isDefault Whether this is the default status for the state
     * @param bool $visibleOnFront Whether status is visible on frontend
     * @return mixed Original assignment result
     */
    public function afterAssignState(
        Status $subject,
        $result,
        $status,
        $state,
        $isDefault,
        $visibleOnFront = false
    ) {
        try {
            if ($this->authSession->isLoggedIn() && $this->config->isEnabled() && !$this->config->isSectionExcluded('orders')) {
                $user = $this->authSession->getUser();
                
                $changes = json_encode([
                    'action' => 'assign_state',
                    'status' => $status,
                    'state' => $state,
                    'is_default' => $isDefault,
                    'visible_on_front' => $visibleOnFront
                ]);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'update',
                    'Order Status State Assignment',
                    $status,
                    $changes
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("OrderStatusPlugin error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Track order status state unassignments
     * 
     * Intercepts status-to-state unassignment operations and logs them
     * for audit and tracking purposes.
     * 
     * @param Status $subject Order status resource model
     * @param mixed $result Unassignment operation result
     * @param string $status Order status code
     * @param string $state Order state
     * @return mixed Original unassignment result
     */
    public function afterUnassignState(
        Status $subject,
        $result,
        $status,
        $state
    ) {
        try {
            if ($this->authSession->isLoggedIn() && $this->config->isEnabled() && !$this->config->isSectionExcluded('orders')) {
                $user = $this->authSession->getUser();
                
                $changes = json_encode([
                    'action' => 'unassign_state',
                    'status' => $status,
                    'state' => $state
                ]);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'update',
                    'Order Status State Assignment',
                    $status,
                    $changes
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("OrderStatusPlugin error: " . $e->getMessage());
        }

        return $result;
    }
}