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

class CustomerDetails
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
    protected $apiUrl = 'sap/opu/odata/sap/ZSD_CUSTMASTER_SRV/CustMastSet?$format=json';
    protected $userName = "AMCESHOP";
    protected $password = "adwan118";

    protected $adminUsername = "rohan";
    protected $adminPassword = "rohan@123";
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
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->responseFactory = $responseFactory;
        $this->guzzleClient = $guzzleClient;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->logger = $logger;
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
    public function customerInfo()
    {
        try{
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
            $res = json_decode($response,true);
            //print_r($res);
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customerApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Response from SAP:');
            $logger->info(print_r($res, true));
            if($res)
            {
                foreach($res["d"]["results"] as $data)
                {
                    $customerArray = [];
                    if($data['Email']!='' && $data['Name1']!='')
                    {
                        //$data['Name1']  = $data['Name1']!='' ? $data['Name1'] : ' ';
                        $data['Name2']  = $data['Name2']!='' ? $data['Name2'] : $data['Name1'];
                        if ($data['DefBill']!='' && $data['DefShip']!='' && $data['Street']!='' && $data['Country']!='' && $data['City']!='' && $data['PostCode']!='' && $data['TelNumber']!='')
                        {
                            $data['DefBill'] = $data['DefBill']!='' ? $data['DefBill'] : true;
                            $data['DefShip'] = $data['DefShip']!='' ? $data['DefShip'] : true;
                            $customerArray['firstname'] = $data['Name1'];
                            $customerArray['lastname'] = $data['Name2'];
                            $customerArray['email'] = $data['Email'];
                            $customerArray['addresses']['defaultBilling'] = $data['DefBill'];
                            $customerArray['addresses']['defaultShipping'] = $data['DefShip'];
                            $customerArray['addresses']['firstname'] = $data['Name1'];
                            $customerArray['addresses']['lastname'] = $data['Name2'];
                            $customerArray['addresses']['postcode'] = $data['PostCode'];
                            $customerArray['addresses']['street'] = $data['Street'];
                            $customerArray['addresses']['city'] = $data['City'];
                            $customerArray['addresses']['telephone'] = $data['TelNumber'];
                            $customerArray['addresses']['countryId'] =$data['Country'];
                            $customerArray['addresses']['region_id'] ='1260';
                        }
                        else{

                            $customerArray['firstname'] = $data['Name1'];
                            $customerArray['lastname'] = $data['Name2'];
                            $customerArray['email'] = $data['Email'];
                            if($data['Kunnr']!='')
                            {
                                $customerArray['extension_attributes']['customer_number'] = $data['Kunnr'];
                            }
                        }
                        // print_r($customerArray);
                        $this->createCustomers($customerArray);
                    }
                }
            }
        } catch(\Excpetion $e)
        {
            echo $e->getMessage();
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
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customerApi.log');
            $logger = new \Zend_Log();
            $logger->info('Admin token:\n');
            $logger->info(print_r($res, true));
        } catch(\Exception $e)
        {
            echo $e->getMessage();
        }

        return $res;
    }
    /**
     * Create Customers
     *
     * @param [string] $adminToken
     * @return void
     */
    public function createCustomers($request_body)
    {
        try{
        $adminToken = $this->getAdminToken();
        $authorization = 'Bearer '.$adminToken;
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $authorization
        );
        $apiUrl = $this->getUrlPrefix() . "rest/default/V1/customers";
        $response =  $this->guzzleClient->request('POST', $apiUrl, array(
            'headers' => $headers,
            'json' => [
                'customer' => $request_body
            ],
            )
        );
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customerApi.log');
        $logger = new \Zend_Log();
        $logger->info('Customer Creation:\n');
        $logger->info(print_r($response, true));
        return $response;
        } catch(\Exception $e)
        {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customerApi.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }

    }
}
