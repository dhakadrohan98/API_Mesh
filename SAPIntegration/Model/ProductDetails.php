<?php

/**
 * @category  Sigma
 * @package   Sigma_SAPIntegration
 * @author    SigmaInfo Team
 * @copyright 2022 Sigma (https://www.sigmainfo.net/)
 */

namespace Sigma\SAPIntegration\Model;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;

class ProductDetails
{

    /**
     * API request URL
     */
    //const API_REQUEST_URI = 'https://amcdev:44303/';
    protected $urlPrefix = 'http://amcsap-wd.adwanmarketing.com:8000/';

    /**
     * API request endpoint
     */
    //const API_REQUEST_ENDPOINT = 'sap/opu/odata/sap/ZMM_MATMASTER_SRV/MatConSet?$format=json';
    protected $apiUrl = 'sap/opu/odata/sap/ZMM_MATMASTER_SRV/MatConSet?$format=json';
    protected $userName = "AMCESHOP";
    protected $password = "adwan118";

    protected $adminUsername = "parimal";
    protected $adminPassword = "Parimal@789";
    /**
     * @var ProductFactory
     */
    protected $_productResourceModel;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * GitApiService constructor
     *
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Client $guzzleClient,
        ResponseFactory $responseFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        ProductFactory $productFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->guzzleClient = $guzzleClient;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->_productFactory = $productFactory;
    }
    /**
     * @return string
     */
    public function getUrlPrefix()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Fetch data from API
     */
    public function productInfo()
    {
        try
        {
            $authorization = 'Basic '.base64_encode($this->userName.':'.$this->password);
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $authorization
            );

            $response =  $this->guzzleClient->request('GET', $this->urlPrefix.$this->apiUrl, array(
                'headers' => $headers
                )
            );
            $response = $response->getBody()->getContents();
            $res = json_decode($response, true);
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/productApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Response from SAP:');
            $logger->info(print_r($res, true));
            if($res)
            {
                foreach($res["d"]["results"] as $data)
                {
                    $productArray= [];
                    if($data["Sku"]!=''){
                        $sku = $data["Sku"];
                        $existSku = $this->isProductExist($sku);

                        $productArray['sku']= $data["Sku"];
                        $productArray['name']=$data["Name"];
                        $productArray['attribute_set_id'] = $data['AttributeSetId'];
                        $productArray['price'] = $data['Price'];
                        $productArray['status'] = $data['Status'];
                        $productArray['visibility'] = $data['Visibility'];
                        $productArray['type_id'] = strtolower($data['TypeId']);
                        $productArray['weight'] = $data['Weight'];
                        if($data['Qty']!='')
                        {
                            $productArray['extension_attributes']['stock_item']['qty'] = $data['Qty'];
                        }
                        if($data['IsInStock']!='')
                        {
                            if($data['IsInStock']=='T')
                            {
                                $productArray['extension_attributes']['stock_item']['is_in_stock'] = 1;
                            }
                            else{
                                $productArray['extension_attributes']['stock_item']['is_in_stock'] = 0;
                            }
                        }
                        $productArray['custom_attributes'][0]['attribute_code'] = "page_layout";
                        $productArray['custom_attributes'][0]['value'] = "product-full-width";

                        if ($existSku > 0) {
                            $this->updateProducts($sku, $productArray);
                        } else {
                            $this->createProducts($productArray);
                        }
                    }
                }
            }

        }
        catch(\Excpetion $e)
        {
            $logger->info($e->getMessage());
        }
    }

    /**
     * Get Admin Token via Admin Integration API
     */
    public function getAdminToken()
    {
        try{
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            );
            $request_body = array(
                'username' => $this->adminUsername,
                'password' => $this->adminPassword
            );

            $apiUrl = $this->getUrlPrefix() . "rest/default/V1/integration/admin/token";
            $response =  $this->guzzleClient->request('POST', $apiUrl, array(
                'headers' => $headers,
                'json' => $request_body,
                )
            );
            $response = $response->getBody()->getContents();
            $res = json_decode($response,true);
        } catch(\Exception $e)
        {
            $logger->info($e->getMessage());
        }
       return $res;
    }
    /**
     * Create Products
     *
     * @param [string] $adminToken
     * @return void
     */
    public function createProducts($request_body)
    {
        try{
            $adminToken = $this->getAdminToken();
            $authorization = "Bearer ".$adminToken;
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $authorization
            );
            $apiUrl = $this->getUrlPrefix() . "rest/default/V1/products";
            $response =  $this->guzzleClient->request('POST', $apiUrl, array(
                'headers' => $headers,
                'json' => [
                    'product' => $request_body
                ],
                )
            );
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/productApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Prouct Creation:');
            $logger->info(print_r($response->getBody()->getContents(), true));
        }catch(\Exception $e)
        {
            $logger->info($e->getMessage());
        }

        return $response;
    }
    /**
     * @param $sku
     * @return false|int
     */
    public function isProductExist($sku): int
    {
        return $this->_productFactory->create()->getIdBySku($sku);
    }
    /**
     * Create Products
     *
     * @param [string] $adminToken
     * @return void
     */
    public function updateProducts($sku, $request_body)
    {
        try{
            $adminToken = $this->getAdminToken();
            $authorization = 'Bearer '.$adminToken;
            $headers = array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $authorization
            );

            $apiUrl = $this->getUrlPrefix() . "rest/default/V1/products/".$sku;
            $response =  $this->guzzleClient->request('PUT', $apiUrl, array(
                'headers' => $headers,
                'json' =>  [
                    'product' => $request_body
                ],
                )
            );
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/productApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Product Updation:');
            $logger->info(print_r($response->getBody()->getContents(), true));
            return $response;
        } catch(\Exception $e)
        {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/productApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }

    }
}
