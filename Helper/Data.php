<?php

namespace Chez\Payments\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Framework\HTTP\ClientInterface;
use stdClass;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const API_URL                                       = 'payment/chez_payments/api_url'; //'http://172.17.0.1:4000'; //
    const API_URL_GET_INSTALLMENTS                      = '/pipeline/integration/installment_politic/simulation';
    const API_URL_POST_TRANSACTION                      = '/transaction/payment';
    const XML_PATH_PAYMENT_TOKEN                        = 'payment/chez_payments/api_token';
    const XML_PATH_PAYMENT_DEBUG                        = 'payment/chez_payments/debug';

    protected $storeManager;

    protected $checkoutSession;

    protected $customer;

    protected $_curl;

    protected $serializer;

    private $remoteAddress;

    protected $transactionRepository;

    protected $orderCommentSender;

    protected $httpClient;

    protected $messageManager;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Framework\App\Helper\Context $context,
        \Chez\Payments\Helper\Logger $loggerHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        OrderCommentSender $orderCommentSender,
        ClientInterface $httpClient
    ) {
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepo = $customer;
        $this->_logHelper  = $loggerHelper;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->_curl = $curl;
        $this->serializer = $serializer;
        $this->remoteAddress = $remoteAddress;
        $this->orderCommentSender = $orderCommentSender;
        $this->transactionRepository = $transactionRepository;
        $this->httpClient = $httpClient;
        parent::__construct($context);
    }

    /**
     * Set the header details.
     * @return Array
     */
    public function setHeaders()
    {
        $headers = [
            'Platform: Magento',
            'Content-Type: application/json',
            'Platform-Version: ' . $this->getMagentoVersion(),
        ];

        return $headers;
    }

    /**
     * Create a transaction using Chez API.
     * * @param order
     * * @param payment
     * @return Array
     */

    public function setTransaction($order, $payment)
    {

        try {

            $products = $this->getItemsParams($order);

            $order_data = $order->getData();
            $aditional_data = $payment->getAdditionalInformation();

            $shippingAddress = $order->getShippingAddress();
            $phone = new stdClass();
            $phone->countryCode = "55";
            $phone->areaCode = substr($shippingAddress->getTelephone(), 0, 2);
            $phone->number = substr($shippingAddress->getTelephone(), 2);

            $cidade = $this->getCityFromAreaCode($phone->areaCode);

            $address = new stdClass();
            $address->street = join(" ", $shippingAddress->getStreet());
            $address->district = 'Bairro';
            $address->number = 'SN';
            $address->state = $cidade['uf'];
            $address->city = $shippingAddress->getCity();
            $address->postal_code = $shippingAddress->getPostCode();
            $address->country = $shippingAddress->getCountryId();

            $credit_card = new stdClass();
            $credit_card->card_number = $aditional_data['cc_number'];
            $credit_card->cvc = $aditional_data['cc_cid'];
            $credit_card->expirationMonth = $aditional_data['cc_exp_month'];
            $credit_card->expirationYear = $aditional_data['cc_exp_year'];
            $credit_card->holder = $aditional_data['cc_owner_name'];
            $selected_installment = explode('|', $aditional_data['selected_installment']);
            $installmentCount = 1;
            if (count($selected_installment) > 1)
                $installmentCount = $selected_installment[0];
            else {
                if (strlen($selected_installment[0]) == 2)
                    $installmentCount = substr($selected_installment[0], 0, 1);
                if (strlen($selected_installment[0]) == 3)
                    $installmentCount = substr($selected_installment[0], 0, 2);
            }


            $credit_card->installmentCount = $installmentCount;

            $params = new stdClass();
            $params->token = $this->getToken();
            $params->payment_method = 'credit';
            $params->credit_card = $credit_card;
            $params->cpf_cnpj = $aditional_data['cc_owner_cpf_cnpj'];
            $params->fullname = $aditional_data['cc_owner_name'];
            $params->costumer_type = strlen($params->cpf_cnpj) < 14 ? 'pf' : 'pj';
            $params->email = $order_data['customer_email'];
            $params->birthdate = $aditional_data['cc_owner_birthday_year'] . '-' .
                $aditional_data['cc_owner_birthday_month'] . '-' .
                $aditional_data['cc_owner_birthday_day'];
            $params->address = $address;
            $params->phone = $phone;
            $params->description = 'Magento Pagamento ID:' . $payment->getTransactionId();
            $params->products = $products;

            //dd($params);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Error on parse params:' . $e->getMessage(), 'Error');
            $this->writeLog('Error on parse params: ' . $e->getMessage());
            return json_encode(
                array('error' => 'Error on parse params (' . $e->getMessage() . ')')
            );
        }


        try {
            $this->writeLog('Parameters being sent to API URL (' . self::API_URL_POST_TRANSACTION . '): ' . var_export($params, true));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getApiUrl() . self::API_URL_POST_TRANSACTION);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_TIMEOUT, 45);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->setHeaders());

            $response = curl_exec($ch);

            if (curl_error($ch)) {
                $this->writeLog('-----Curl error response----: ' . var_export(curl_error($ch), true));
                throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase(curl_error($ch)));
            }

            curl_close($ch);

            $this->writeLog('Retorno Chez (/' . self::API_URL_GET_INSTALLMENTS . '): ' . var_export($response, true));

            $response = json_decode($response);
            if (isset($response->message) && strtolower(trim($response->message)) == 'there are validation errors.') {
                return json_encode(
                    array('error' => $response)
                );
            } else {
                return json_encode(
                    array('success' => $response)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Communication failure with Chez API'), 'Error');
            $this->writeLog('Communication failure with Chez API: ' . $e->getMessage());
            return json_encode(
                array('error' => 'Communication failure with Chez API (' . $e->getMessage() . ')')
            );
        }
    }

    /**
     * Get installments from Pipeline
     * @param number
     * @return number
     */
    public function getInstallments($value)
    {
        $params = new stdClass();
        $params->value = round($value, 2);
        $params->api_token = $this->getToken();
        $params->vendorPortionLimit = 4;
        $params->vendorPortionFee = 1;
        $params->customerPortionFee = 2;
        $params->subaccount_id = '61730e854865ea00203d1576';

        $this->writeLog('Parameters being sent to API URL (' . self::API_URL_GET_INSTALLMENTS . '): ' . var_export($params, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl() . self::API_URL_GET_INSTALLMENTS);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->setHeaders());

        try {
            $response = curl_exec($ch);

            if (curl_error($ch)) {
                $this->writeLog('-----Curl error response----: ' . var_export(curl_error($ch), true));
                throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase(curl_error($ch)));
            }

            curl_close($ch);

            $this->writeLog('Retorno Chez (/' . self::API_URL_GET_INSTALLMENTS . '): ' . var_export($response, true));

            return json_encode(
                array('success' => json_decode($response))
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Communication failure with Chez API'), 'Error');
            $this->writeLog('Communication failure with Chez API: ' . $e->getMessage());
            return json_encode(
                array('error' => 'Communication failure with Chez API (' . $e->getMessage() . ')')
            );
        }
    }

    /**
     * Get State e City from Phone area code
     * @param areaCode
     * @return array
     */

    function getCityFromAreaCode($areaCode)
    {
        switch ($areaCode) {
            case '68':
                return ['uf' => 'AC', 'description' => 'Acre'];
                break;
            case '82':
                return ['uf' => 'AL', 'description' => 'Alagoas'];
                break;
            case '96':
                return ['uf' => 'AP', 'description' => 'Amapá'];
                break;
            case '92':
                return ['uf' => 'AM', 'description' => 'Amazonas'];
                break;
            case '71':
                return ['uf' => 'BA', 'description' => 'Bahia'];
                break;
            case '73':
                return ['uf' => 'BA', 'description' => 'Bahia'];
                break;
            case '74':
                return ['uf' => 'BA', 'description' => 'Bahia'];
                break;
            case '75':
                return ['uf' => 'BA', 'description' => 'Bahia'];
                break;
            case '77':
                return ['uf' => 'BA', 'description' => 'Bahia'];
                break;
            case '85':
                return ['uf' => 'CE', 'description' => 'Ceará'];
                break;
            case '88':
                return ['uf' => 'CE', 'description' => 'Ceará'];
                break;
            case '61':
                return ['uf' => 'DF', 'description' => 'Brasília'];
                break;
            case '27':
                return ['uf' => 'ES', 'description' => 'Espirito Santo'];
                break;
            case '28':
                return ['uf' => 'ES', 'description' => 'Espirito Santo'];
                break;
            case '62':
                return ['uf' => 'GO', 'description' => 'Goiás'];
                break;
            case '64':
                return ['uf' => 'GO', 'description' => 'Goiás'];
                break;
            case '98':
                return ['uf' => 'MA', 'description' => 'Maranhão'];
                break;
            case '99':
                return ['uf' => 'MA', 'description' => 'Maranhão'];
                break;
            case '65':
                return ['uf' => 'MT', 'description' => 'Mato Grosso'];
                break;
            case '66':
                return ['uf' => 'MT', 'description' => 'Mato Grosso'];
                break;
            case '67':
                return ['uf' => 'MS', 'description' => 'Mato Grosso do Sul'];
                break;
            case '31':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '32':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '33':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '34':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '35':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '37':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '38':
                return ['uf' => 'MG', 'description' => 'Minas Gerais'];
                break;
            case '91':
                return ['uf' => 'PA', 'description' => 'Pará'];
                break;
            case '93':
                return ['uf' => 'PA', 'description' => 'Pará'];
                break;
            case '94':
                return ['uf' => 'PA', 'description' => 'Pará'];
                break;
            case '83':
                return ['uf' => 'PB', 'description' => 'Paraíba'];
                break;
            case '41':
                return ['uf' => 'PR', 'description' => 'Paraná'];
                break;
            case '42':
                return ['uf' => 'PR', 'description' => 'Paraná'];
                break;
            case '43':
                return ['uf' => 'PR', 'description' => 'Paraná'];
                break;
            case '44':
                return ['uf' => 'PR', 'description' => 'Paraná'];
                break;
            case '45':
                return ['uf' => 'PR', 'description' => 'Paraná'];
                break;
            case '46':
                return ['uf' => 'PR', 'description' => 'Paraná'];
                break;
            case '81':
                return ['uf' => 'PE', 'description' => 'Pernambuco'];
                break;
            case '87':
                return ['uf' => 'PE', 'description' => 'Pernambuco'];
                break;
            case '86':
                return ['uf' => 'PI', 'description' => 'Piauí'];
                break;
            case '89':
                return ['uf' => 'PI', 'description' => 'Piauí'];
                break;
            case '21':
                return ['uf' => 'RJ', 'description' => 'Rio de Janeiro'];
                break;
            case '22':
                return ['uf' => 'RJ', 'description' => 'Rio de Janeiro'];
                break;
            case '24':
                return ['uf' => 'RJ', 'description' => 'Rio de Janeiro'];
                break;
            case '84':
                return ['uf' => 'RN', 'description' => 'Rio Grande do Norte'];
                break;
            case '54':
                return ['uf' => 'RN', 'description' => 'Rio Grande do Norte'];
                break;
            case '55':
                return ['uf' => 'RS', 'description' => 'Rio Grande do Sul'];
                break;
            case '51':
                return ['uf' => 'RS', 'description' => 'Rio Grande do Sul'];
                break;
            case '53':
                return ['uf' => 'RS', 'description' => 'Rio Grande do Sul'];
                break;
            case '69':
                return ['uf' => 'RO', 'description' => 'Rondônia'];
                break;
            case '95':
                return ['uf' => 'RR', 'description' => 'Roraima'];
                break;
            case '47':
                return ['uf' => 'SC', 'description' => 'Santa Cataria'];
                break;
            case '48':
                return ['uf' => 'SC', 'description' => 'Santa Cataria'];
                break;
            case '49':
                return ['uf' => 'SC', 'description' => 'Santa Cataria'];
                break;
            case '11':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '12':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '13':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '14':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '15':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '16':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '17':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '18':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '19':
                return ['uf' => 'SP', 'description' => 'São Paulo'];
                break;
            case '79':
                return ['uf' => 'SE', 'description' => 'Sergipe'];
                break;
            case '63':
                return ['uf' => 'TO', 'description' => 'Tocatins'];
                break;
            default:
                return ['uf' => 'DF', 'description' => 'Brasília'];
                break;
        }
    }

    /**
     * Check if debug mode is active
     * @return bool
     */
    public function isDebugActive()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_DEBUG, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Write something to chez.log
     * @param $obj mixed|string
     */
    public function writeLog($obj)
    {
        if ($this->isDebugActive()) {
            $this->_logHelper->writeLog($obj);
        }
    }

    /**
     * Get API Token.
     * @return string
     */
    public function getToken()
    {
        $token = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_TOKEN, ScopeInterface::SCOPE_WEBSITE);

        if (empty($token)) {
            return false;
        }

        return $token;
    }

    /**
     * Get API URL.
     * @return string
     */
    public function getApiUrl()
    {
        $api_url = $this->scopeConfig->getValue(self::API_URL, ScopeInterface::SCOPE_WEBSITE);

        if (empty($api_url)) {
            return false;
        }

        return $api_url;
    }

    /**
     * Return store base url
     * return string
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Return GrandTotal
     * return decimal
     */
    public function getGrandTotal()
    {
        return  $this->checkoutSession->getQuote()->getGrandTotal();
    }

    /**
     * Convert array values to utf-8
     * @param array $params
     *
     * @return array
     */
    protected function convertEncoding(array $params)
    {
        foreach ($params as $k => $v) {
            $params[$k] = utf8_decode($v);
        }
        return $params;
    }

    /**
     * Convert API params (already ISO-8859-1) to url format (curl string)
     * @param array $params
     *
     * @return string
     */
    protected function convertToCURLString(array $params)
    {
        $fieldsString = '';
        foreach ($params as $k => $v) {
            $fieldsString .= $k . '=' . urlencode($v) . '&';
        }
        return rtrim($fieldsString, '&');
    }

    /**
     * Retrieves visible products of the order, omitting its children (yes, this is different than Magento's method)
     * @param Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getAllVisibleItems($order)
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            if (!$item->isDeleted() && !$item->getParentItem()) {
                $items[] = $item;
            }
        }
        return $items;
    }


    /**
     * Return items information, to be send to API
     * @param Magento\Sales\Model\Order $order
     * @param float $percent
     * @return array
     */
    public function getItemsParams(\Magento\Sales\Model\Order $order)
    {
        $products = [];

        $shipping_amount = $order->getShippingAmount();
        if (round($shipping_amount, 2) > 0) {
            $product = new stdClass();
            $product->title = 'Custos com envio';
            $product->value = round($shipping_amount, 2);
            $product->quantity = "1.6";
            array_push($products, $product);
        }

        $items = $this->getAllVisibleItems($order);
        if ($items) {
            $itemsCount = count($items);
            for ($x = 1, $y = 0; $x <= $itemsCount; $x++, $y++) {
                $itemPrice = $items[$y]->getPrice();
                $qtyOrdered = $items[$y]->getQtyOrdered();

                if ($itemPrice == 0) {
                    continue;
                }
                $product = new stdClass();
                $product->title = $items[$y]->getName();
                $product->value = round($itemPrice, 2);
                $product->quantity = $qtyOrdered;
                array_push($products, $product);
            }
        }
        return $products;
    }

    /**
     * Remove duplicated spaces from string
     * @param $string
     * @return string
     */
    public function removeDuplicatedSpaces($string)
    {
        $string = $this->normalizeChars($string);

        return preg_replace('/\s+/', ' ', trim($string));
    }

    /**
     * Replace language-specific characters by ASCII-equivalents.
     * @see http://stackoverflow.com/a/16427125/529403
     * @param string $s
     * @return string
     */
    public function normalizeChars($s)
    {
        $replace = [
            'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'È' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ñ' => 'N', 'Ò' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'ä' => 'a', 'ã' => 'a', 'á' => 'a', 'à' => 'a', 'å' => 'a', 'æ' => 'ae', 'è' => 'e', 'ë' => 'e', 'ì' => 'i',
            'í' => 'i', 'î' => 'i', 'ï' => 'i', 'Ã' => 'A', 'Õ' => 'O',
            'ñ' => 'n', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'ú', 'û' => 'u', 'ü' => 'ý', 'ÿ' => 'y',
            'Œ' => 'OE', 'œ' => 'oe', 'Š' => 'š', 'Ÿ' => 'Y', 'ƒ' => 'f', 'Ğ' => 'G', 'ğ' => 'g', 'Š' => 'S',
            'š' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ş' => 's', 'ț' => 't', 'Ț' => 'T', 'ÿ' => 'y', 'Ž' => 'Z', 'ž' => 'z'
        ];
        return preg_replace('/[^0-9A-Za-zÃÁÀÂÇÉÊÍÕÓÔÚÜãáàâçéêíõóôúü.\-\/ ]/u', '', strtr($s, $replace));
    }

    /**
     * Tests if $string is a valid json
     * @param $string
     *
     * @return bool
     */
    public function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Get BR State code even if it was typed manually
     * @param $state
     *
     * @return string
     */
    public function getStateCode($state)
    {
        if (strlen($state) == 2 && is_string($state)) {
            return mb_convert_case($state, MB_CASE_UPPER);
        } elseif (strlen($state) > 2 && is_string($state)) {
            $state = $this->normalizeChars($state);
            $state = trim($state);
            $state = $this->stripAccents($state);
            $state = mb_convert_case($state, MB_CASE_UPPER);
            $codes = [
                'AC' => 'ACRE',
                'AL' => 'ALAGOAS',
                'AM' => 'AMAZONAS',
                'AP' => 'AMAPA',
                'BA' => 'BAHIA',
                'CE' => 'CEARA',
                'DF' => 'DISTRITO FEDERAL',
                'ES' => 'ESPIRITO SANTO',
                'GO' => 'GOIAS',
                'MA' => 'MARANHAO',
                'MT' => 'MATO GROSSO',
                'MS' => 'MATO GROSSO DO SUL',
                'MG' => 'MINAS GERAIS',
                'PA' => 'PARA',
                'PB' => 'PARAIBA',
                'PR' => 'PARANA',
                'PE' => 'PERNAMBUCO',
                'PI' => 'PIAUI',
                'RJ' => 'RIO DE JANEIRO',
                'RN' => 'RIO GRANDE DO NORTE',
                'RO' => 'RONDONIA',
                'RS' => 'RIO GRANDE DO SUL',
                'RR' => 'RORAIMA',
                'SC' => 'SANTA CATARINA',
                'SE' => 'SERGIPE',
                'SP' => 'SAO PAULO',
                'TO' => 'TOCANTINS'
            ];
            $code = array_search($state, $codes);
            if (false !== $code) {
                return $code;
            }
        }
        return $state;
    }

    /**
     * Replace accented characters
     * @param $string
     *
     * @return string
     */
    public function stripAccents($string)
    {
        return preg_replace('/[`^~\'"]/', null, iconv('UTF-8', 'ASCII//TRANSLIT', $string));
    }

    /**
     * Returns Store config value
     *
     * @param string
     * @return string/bool
     */
    public function getStoreConfigValue($scopeConfigPath)
    {
        return  $this->scopeConfig->getValue($scopeConfigPath, ScopeInterface::SCOPE_STORE);
    }

    public function setSessionVl($value)
    {
        return $this->checkoutSession->setCustomparam($value);
    }

    public function getSessionVl()
    {
        return $this->checkoutSession->getCustomparam();
    }

    public function getModuleInformation()
    {
        return $this->moduleList->getOne('Chez_Payments');
    }

    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }
}
