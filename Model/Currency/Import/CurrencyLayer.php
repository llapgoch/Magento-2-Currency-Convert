<?php

namespace Thanhdv2811\CurrencyConverter\Model\Currency\Import;

/**
 * Currency rate import model (From https://currencylayer.com/)
 */
class CurrencyLayer extends \Magento\Directory\Model\Currency\Import\AbstractImport
{
    /**
     * @var string
     */
    const CURRENCY_CONVERTER_URL = 'http://apilayer.net/api/live?access_key={{API_KEY}}&currencies={{CURRENCY_TO}}&source={{CURRENCY_FROM}}&format=1';

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
        $timeout = (int)$this->scopeConfig->getValue(
            'currency/currencyLayer/timeout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $apiKey = $this->scopeConfig->getValue(
            'currency/currencyLayer/apikey',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, self::CURRENCY_CONVERTER_URL);
        $url = str_replace('{{CURRENCY_TO}}', $currencyTo, $url);
        $url = str_replace('{{API_KEY}}', $apiKey, $url);

        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();

        try {
            $response = $httpClient->setUri($url)
                ->setConfig(['timeout' => $timeout])
                ->request('GET')
                ->getBody();

            $resultKey = $currencyFrom . $currencyTo;
            $data = $this->jsonHelper->jsonDecode($response);

            if (isset($data['quotes'][$resultKey])) {
                $result = (float)$data['quotes'][$resultKey];
            } else {
                $this->_messages[] = __('We can\'t retrieve a rate from %1.', $url);
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