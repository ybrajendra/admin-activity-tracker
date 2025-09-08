<?php
/**
 * Invoice Register Observer for Admin Activity Tracking
 * 
 * Observes invoice registration events to track invoice
 * creation activities in the admin panel.
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
 * Class InvoiceRegisterObserver
 * 
 * Observer that tracks invoice registration events in the admin area
 * to maintain an audit trail of invoice creation activities.
 * 
 * @package CloudCommerce\AdminActivityTracker\Observer
 */
class InvoiceRegisterObserver implements ObserverInterface
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
     * Array to prevent duplicate invoice logging
     * 
     * @var array
     */
    private static $loggedInvoices = [];
    
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
     * @param ActivityLogRepository $activityLogRepository Activity logger for tracking invoice creation
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
     * Execute observer to track invoice registration events
     * 
     * Monitors invoice registration and logs creation activities
     * for audit and tracking purposes.
     * 
     * @param Observer $observer Event observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            // Only track admin area actions when module is enabled and orders section is not excluded
            if ($this->appState->getAreaCode() !== 'adminhtml' || !$this->authSession->isLoggedIn() || !$this->config->isEnabled() || $this->config->isSectionExcluded('orders')) {
                return;
            }

            $invoice = $observer->getEvent()->getInvoice();
            
            if ($invoice) {
                $invoiceKey = $invoice->getOrderId() . '_' . $invoice->getIncrementId();
                
                // Prevent duplicate logging
                if (in_array($invoiceKey, self::$loggedInvoices)) {
                    return;
                }
                
                self::$loggedInvoices[] = $invoiceKey;
                
                $user = $this->authSession->getUser();
                
                $changes = json_encode([
                    'action' => 'invoice_created',
                    'order_id' => $invoice->getOrderId(),
                    'invoice_increment_id' => $invoice->getIncrementId(),
                    'grand_total' => $invoice->getGrandTotal()
                ]);
                
                $this->activityLogRepository->log(
                    $user->getId(),
                    $user->getUsername(),
                    'create',
                    'Invoice Creation',
                    $invoice->getId(),
                    $changes
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('InvoiceRegisterObserver error: ' . $e->getMessage());
        }
    }
}