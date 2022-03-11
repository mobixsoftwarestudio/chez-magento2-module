<?php

namespace Chez\Payments\Logger\Handler;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class Custom
 */
class Custom extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/chez.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
