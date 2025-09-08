<?php
/**
 * Admin Activity Log Index Controller
 * 
 * Handles the display of the admin activity log grid page
 * in the Magento admin panel.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * 
 * Controller for displaying the admin activity log grid page,
 * extending Magento's backend action controller.
 * 
 * @package CloudCommerce\AdminActivityTracker\Controller\Adminhtml\Log
 */
class Index extends Action
{
    /**
     * Result page factory for creating page responses
     * 
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     * 
     * @param Context $context Backend action context
     * @param PageFactory $resultPageFactory Page factory for creating result pages
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action to display activity log page
     * 
     * Creates and configures the admin activity log grid page
     * with proper menu activation and page title.
     * 
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // Create result page
        $resultPage = $this->resultPageFactory->create();
        
        // Set active menu item
        $resultPage->setActiveMenu('CloudCommerce_AdminActivityTracker::activity_log');
        
        // Set page title
        $resultPage->getConfig()->getTitle()->prepend(__('Admin Activity Log'));
        
        return $resultPage;
    }

    /**
     * Check if user is allowed to access this controller
     * 
     * Verifies that the current admin user has permission to
     * view the admin activity log.
     * 
     * @return bool True if access is allowed, false otherwise
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('CloudCommerce_AdminActivityTracker::activity_log');
    }
}