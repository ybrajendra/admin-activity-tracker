<?php
/**
 * Order Comment Plugin for Admin Activity Tracking
 * 
 * Intercepts order comment addition operations to track
 * order status and comment changes made in the admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Plugin;

use Magento\Sales\Model\Order;
use Magento\Backend\Model\Auth\Session;
use CloudCommerce\AdminActivityTracker\Model\ActivityLogRepository;
use CloudCommerce\AdminActivityTracker\Helper\Config;
use CloudCommerce\AdminActivityTracker\Logger\Logger;

/**
 * Class OrderCommentPlugin
 * 
 * Plugin for Magento\Sales\Model\Order to track order
 * comment and status changes for audit purposes.
 * 
 * @package CloudCommerce\AdminActivityTracker\Plugin
 */
class OrderCommentPlugin
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking order changes
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
     * Track order comment additions
     * 
     * Intercepts order comment addition events and logs them
     * for audit and tracking purposes.
     * 
     * @param Order $subject Order model instance
     * @param mixed $result Comment addition result
     * @param string $comment Comment text
     * @param string|bool $status Order status
     * @param bool $isVisibleOnFront Visibility flag for frontend
     * @return mixed Original comment addition result
     */
    public function afterAddCommentToStatusHistory(
        Order $subject,
        $result,
        $comment,
        $status = false,
        $isVisibleOnFront = false
    ) {
        try {
            if ($this->authSession->isLoggedIn() && $this->config->isEnabled() && !$this->config->isSectionExcluded('orders')) {
                $user = $this->authSession->getUser();
                
                $changes = json_encode([
                    'comment' => $comment,
                    'status' => $status ?: 'No status change',
                    'visible_on_front' => $isVisibleOnFront
                ]);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'update',
                    'Order Comment',
                    $subject->getId(),
                    $changes
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('OrderCommentPlugin error: ' . $e->getMessage());
        }

        return $result;
    }
}