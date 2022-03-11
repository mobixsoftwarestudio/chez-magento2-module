<?php

namespace Chez\Payments\Model\Method;

/**
 * Credit Card Payment Method
 */
class Cc extends \Magento\Payment\Model\Method\Cc
{
    /**
     * @var string
     */
    protected $_formBlockType = \Chez\Payments\Block\Form\Cc::class;
    protected $_infoBlockType = \Chez\Payments\Block\Payment\InfoCc::class;

    const CODE = 'chez_payments';

    protected $_code = self::CODE;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['BRL'];

    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

    protected $dataHelper;

    protected $adminSession;

    protected $messageManager;

    protected $request;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Chez\Payments\Helper\Data $dataHelper,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->_countryFactory = $countryFactory;

        $this->dataHelper = $dataHelper;
        $this->adminSession = $adminSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //@TODO Review. Necessary?
        $this->dataHelper->writeLog('Inside authorize');
    }

    /**
     * Payment capturing
     *
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /*@var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        try {

            $payment->setSkipOrderProcessing(true);
        } catch (\Exception $e) {

            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $this;
    }
}
