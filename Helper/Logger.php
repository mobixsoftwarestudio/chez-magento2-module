<?php

namespace Chez\Payments\Helper;

/**
 * Class Logger
 *
 */
class Logger extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $customLogger;

    public function __construct(\Psr\Log\LoggerInterface $customLogger)
    {

        $this->customLogger = $customLogger;
    }

    /**
     * @param $obj
     */
    public function writeLog($obj)
    {
        if (is_string($obj)) {
            $this->customLogger->debug($obj);
        } else {
            $this->customLogger->debug(json_encode($obj));
        }
    }
}
