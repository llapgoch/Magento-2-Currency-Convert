<?php

namespace Thanhdv2811\CurrencyConverter\Model\Currency\Import;

use Magento\Framework\Exception\LocalizedException;

/**
 * Currency rate import model (From https://free.currencyconverterapi.com/)
 */
class Fcc extends \Magento\Directory\Model\Currency\Import\AbstractImport
{
    /**
     * @var string
     */
    const CURRENCY_CONVERTER_URL = 'https://free.currencyconverterapi.com/api/v3/convert?q={{CURRENCY_FROM}}_{{CURRENCY_TO}}&apiKey={{API_KEY}}';

    /** @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    /**
     * Http Client Factory
     *
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * Core scope config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Initialize dependencies
     *
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        parent::__construct($currencyFactory);
        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param int $retry
     * @return float|null
     */
    protected function _convert($currencyFrom, $currencyTo, $retry = 0)
    {
        $result = null;

        $apiKey = $this->scopeConfig->getValue(
            'currency/freeCurrencyConverter/apikey',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        if(!$apiKey){
            throw new LocalizedException(__('Please enter an API key to use this service'));
        }

        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, self::CURRENCY_CONVERTER_URL);
        $url = str_replace('{{CURRENCY_TO}}', $currencyTo, $url);
        $url = str_replace('{{API_KEY}}', $apiKey, $url);
        
        $timeout = (int)$this->scopeConfig->getValue(
            'currency/freeCurrencyConverter/timeout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        try {

            // Removed the use of Zend Client as it throws a wobbler for HTTP/2
            $response = file_get_contents($url);
            
            $resultKey = $currencyFrom . '_' . $currencyTo;
            $data = $this->jsonHelper->jsonDecode($response);
            $results = $data['results'][$resultKey];
            $queryCount = $data['query']['count'];
            if (!$queryCount && !isset($results)) {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $url);
            } else {
                $result = (float)$results['val'];
            }
        } catch (\Exception $e) {
            if ($retry == 0) {
                $this->_convert($currencyFrom, $currencyTo, 1);
            } else {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $url);
            }
        }
        return $result;
    }
}