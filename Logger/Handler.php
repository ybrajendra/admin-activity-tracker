<?php
/**
 * Custom Log Handler for Admin Activity Tracker
 * 
 * Handles logging for admin activity tracking with automatic log rotation
 * and compression when file size exceeds the configured limit.
 */
declare(strict_types=1);

namespace CloudCommerce\AdminActivityTracker\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Handler
 * 
 * Custom log handler that extends Magento's base handler to provide
 * automatic log rotation and compression for admin activity logs.
 * 
 * @package CloudCommerce\AdminActivityTracker\Logger
 */
class Handler extends Base
{
    /**
     * Logger type level (INFO level)
     * 
     * @var int
     */
    protected $loggerType = Logger::INFO;
    
    /**
     * Log file name and path
     * 
     * @var string
     */
    protected $fileName = '/var/log/admin_activity.log';
    
    /**
     * Directory list instance for path resolution
     * 
     * @var DirectoryList
     */
    private $directoryList;
    
    /**
     * Constructor
     * 
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem Filesystem driver
     * @param DirectoryList $directoryList Directory list for path resolution
     * @param string|null $filePath Optional file path
     * @param string|null $fileName Optional file name
     */
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        DirectoryList $directoryList,
        $filePath = null,
        $fileName = null
    ) {
        $this->directoryList = $directoryList;
        parent::__construct($filesystem, $filePath, $fileName);
    }
    
    /**
     * Write log record with rotation check
     * 
     * Checks for log rotation before writing the record to ensure
     * log files don't grow too large.
     * 
     * @param $record Log record to write
     * @return void
     */
    protected function write($record): void
    {
        $this->checkLogRotation();
        parent::write($record);
    }
    
    /**
     * Check and perform log rotation if needed
     * 
     * Monitors the log file size and creates a compressed archive
     * when the file exceeds 1MB, then clears the original log file.
     * 
     * @return void
     */
    private function checkLogRotation(): void
    {
        // Get the full path to the log file
        $logFile = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/log/admin_activity.log';
        
        // Check if file exists and exceeds size limit (1MB)
        if (file_exists($logFile) && filesize($logFile) > 1000 * 1024) {
            // Create timestamp for archive filename
            $timestamp = date('Y-m-d_H-i-s');
            $zipFile = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/log/admin_activity_' . $timestamp . '.zip';
            
            // Create ZIP archive if ZipArchive class is available
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
                    // Add log file to archive with timestamped name
                    $zip->addFile($logFile, 'admin_activity_' . $timestamp . '.log');
                    $zip->close();
                    
                    // Clear the original log file content
                    file_put_contents($logFile, '');
                }
            }
        }
    }
}