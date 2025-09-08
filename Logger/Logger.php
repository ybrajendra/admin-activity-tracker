<?php
/**
 * Custom Logger for Admin Activity Tracker
 * 
 * Extends Monolog Logger to provide specialized logging
 * functionality for admin activity tracking operations.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Logger;

use Monolog\Logger as MonologLogger;

/**
 * Class Logger
 * 
 * Custom logger class that extends Monolog Logger to provide
 * specialized logging for admin activity tracking events.
 * 
 * @package CloudCommerce\AdminActivityTracker\Logger
 */
class Logger extends MonologLogger
{
    // This class inherits all functionality from MonologLogger
    // and is configured via DI to use the custom Handler
}