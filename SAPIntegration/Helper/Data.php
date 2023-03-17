<?php

namespace Sigma\SAPIntegration\Helper;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * API Endpoint
     */
    protected $api_url = "http://amcsap-wd.adwanmarketing.com:8000/sap/opu/odata/sap/ZSD_SALESPROCESS_SRV/SalesHeaderSet";

    /**
     * Authorization Detials
     */
    protected $userName = "AMCESHOP";
    protected $password = "adwan118";

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Client $guzzleClient,
        ResponseFactory $responseFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Convert\Xml $parser,
        CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditmemoCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->_productFactory = $productFactory;
        $this->responseFactory = $responseFactory;
        $this->guzzleClient = $guzzleClient;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->_curl = $curl;
        $this->parser = $parser;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->orderRepository = $orderRepository;
        $this->creditmemoCollection = $creditmemoCollection;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Trigger the SAP API
     */
    public function triggerSapApi($order)
    {
        try{
            if($order->getState() == 'processing')
            {
                $sapApiTrigger = true;
                if($order->getSapApiTrigger()==0)
                {

                    $order->setSapApiTrigger($sapApiTrigger);
                    $order->save();
                    $orderId = $order->getIncrementId();
                    $createdAt = $order->getCreatedAt();
                    $this->logger("OrderId".$orderId);
                    $mainArray = [];
                    $productArray = [];
                    foreach($order->getAllItems() as $item ) {

                        $productArray['sku'] = $item->getSku();
                        $productArray['qty'] = $item->getQtyOrdered();
                        $productArray['price'] = $item->getPrice();
                        $productArray['currency'] = $order->getOrderCurrencyCode();
                        $mainArray[] = $productArray;
                    }
                     //API Trigger
                    $this->logger($mainArray,true);
                    $sapOrderId = $this->apiTrigger($mainArray,$orderId,$createdAt,'order');
                    $this->logger("SAP Order ID2:".$sapOrderId, true);
                    $this->logger("SAP Order ID type:".gettype($sapOrderId), true);
                    if($sapOrderId){
                        $order->setSapOrderId($sapOrderId);
                        $order->save();
                    }
                }
            }
        }catch(\Exception $e)
        {
            $this->logger($e,true);
        }

    }
    public function apiTrigger($mainArray, $orderId, $createdAt,$type,$sapId = null)
    {
        try{

            $this->logger("type".$type);
            $this->logger("SapId".$sapId);
            $this->logger($mainArray,true);
            $finalPayload =[];
            $orderDetails = [];
            $orderFinalArray = [];

            $token = $this->getCSRFToken($this->userName,$this->password,$this->api_url);
            $csrfToken = $token['x-csrf-token'][0];
            $sapCookie = $token['set-cookie'][1]." ". $token['set-cookie'][0];

            $createdAt = explode(" ", $createdAt);
            $createdAt = explode("-",$createdAt[0]);
            $createdAt = implode("",$createdAt);
            $this->logger("Final Created".$createdAt);
            //Set the Headers
            $headers = [
                "Content-Type" => "application/json",
                "x-csrf-token" => $csrfToken,
                "Connection"=> "keep-alive",
                "Cookie" => $sapCookie
            ];
            $this->_curl->setHeaders($headers);
            $this->logger($headers,true);
            //Set the Basic Auth Credentials

           $this->_curl->setCredentials($this->userName, $this->password);

            //Set the Curl Options
            $options = [
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_ENCODING=>'',
                CURLOPT_MAXREDIRS=>10,
                CURLOPT_TIMEOUT=>0,
                CURLOPT_FOLLOWLOCATION=>true
            ];
            $this->logger($options,true);
            $this->_curl->setOptions($options);

            foreach($mainArray as $item)
            {
                $orderDetails['Orderid']="";
                $orderDetails['Material'] = $item['sku'];
                $orderDetails['Batch'] = "";
                $orderDetails['Plant'] = "4000";
                $orderDetails['StoreLoc'] = "0001";
                $orderDetails['TargetQty'] = (string)$item['qty'];
                $orderDetails['CondType'] = "";
                $orderDetails['CondValue'] = (string)$item['price'];
                $orderDetails['Currency'] = $item['currency'];
                $orderFinalArray[] = $orderDetails;
            }
            $this->logger($orderFinalArray,true);
            if($type=='return')
            {
                $docType = 'ZRES';
                $ref = $sapId;
            }
            else{
                $docType = 'ZES';
                $ref = "";
            }
            $this->logger("type".$docType);
            $this->logger("SapId".$ref);
            //Final Payload
            $finalPayload["Orderid"] = "";
            $finalPayload["DocType"] = $docType;
            $finalPayload["PurchDate"] = "/Date(".$createdAt.")/";
            $finalPayload["SalesOrg"] = "1000";
            $finalPayload["DistrChan"] = "70";
            $finalPayload["Division"] = "00";
            $finalPayload["PurchN"] = $orderId;
            $finalPayload["Ref1"] = $ref;
            $finalPayload["PartnRole"] = "SP";
            $finalPayload["PartnNumb"] = "C1 ESALES";
            $finalPayload["HeaderToItem"] = $orderFinalArray;
            $this->logger($finalPayload,true);
            $finalPayload = json_encode($finalPayload);
            //$this->logger($finalPayload,true);


            $this->_curl->post($this->api_url, $finalPayload);
            $response = $this->_curl->getBody();
            $res = json_decode($response,true);
            $this->logger('Response from SAPOrder:');
            $this->logger($response, true);
            $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
            $xml = new \SimpleXMLElement($response);
            $array = json_decode(json_encode((array)$xml), TRUE);

            $sapOrderId = $array['content']['mproperties']['dOrderid'];
            $this->logger("SAP Order ID:".$sapOrderId, true);
            $this->logger("---------------------------------------------------");
            return $sapOrderId;
        }  catch(\Exception $e)
        {
            $this->logger($e,true);
        }
    }

    function getCSRFToken($userName,$password,$api_url)
    {
        try{
            $this->logger("Inside Token");
            //Set the Headers
            $request_headers = ['X-CSRF-Token: fetch','Connection: keep-alive','Content-Type: application/json','Accept: application/json'];
            $cookiePath = dirname(__FILE__).'/cookies.txt';
            $this->_curl->setHeaders($request_headers);
            //Set the Options
            $options = [
                CURLOPT_URL=>$api_url,
                CURLOPT_HTTPHEADER=>$request_headers,
                CURLOPT_POST=>0,
                CURLOPT_USERPWD=>"$userName:$password",
                CURLOPT_RETURNTRANSFER=>1,
                CURLOPT_VERBOSE=>1,
                CURLOPT_HEADER=>1,
                CURLOPT_NOBODY=>true,
                CURLOPT_SSL_VERIFYPEER=>true,
                CURLINFO_HEADER_OUT=>true,
                CURLOPT_COOKIESESSION=>true,
                CURLOPT_COOKIEFILE=>$cookiePath,
                CURLOPT_HEADERFUNCTION=>function($curl, $header) use (&$headers)
                {
                $len  = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) { // ignore invalid headers
                return $len;
                }
                $name = strtolower(trim($header[0]));
                if (is_array($headers) && !array_key_exists($name, $headers)) {
                $headers[$name] = [trim($header[1])];
                } else {
                $headers[$name][] = trim($header[1]);
                }
                return $len;

                }];

            $this->_curl->setOptions($options);
            $this->_curl->get($api_url);

            $response = $this->_curl->getBody();
            $this->logger($headers,true);

            return $headers;
        }catch(\Exception $e)
        {
            $this->logger($e,true);
        }
    }

    public function logger($text, $array=false){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/sapapi.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        if($array){
            $logger->info(print_r($text,true));
        }else{
            $logger->info($text);
        }

    }
    public function triggerReturnSapApi($creditMemo)
    {
        try{
                $creditCollection = $this->creditmemoCollection;
                $sapReturnApiTrigger = true;
                if($creditMemo->getSapReturnApiTrigger()==0)
                {

                    $creditMemo->setSapReturnApiTrigger($sapReturnApiTrigger);
                    $creditMemo->save();
                    $createdAt = $creditMemo->getCreatedAt();

                    $mainArray = [];
                    $productArray = [];

                    $orderId = $creditMemo->getOrderId();
                    $orderCollection = $this->_orderCollectionFactory->create()->addAttributeToSelect('*')->addFieldToFilter('entity_id',['eq'=>$orderId])->getFirstItem();
                    $orderIncrementId = $orderCollection->getIncrementId();

                    $sapOrderId = $orderCollection->getSapOrderId();
                    $this->logger("Order inc". $orderIncrementId);
                    $this->logger("SAP". $sapOrderId);
                    $this->logger("Cre". $createdAt);

                    foreach($creditMemo->getItems() as $item){
                        if($item->getQty()!=0)
                        {
                            $productArray['sku'] = $item->getSku();
                            $productArray['qty'] = $item->getQty();
                            $productArray['price'] = $item->getPrice();
                            $productArray['currency'] = $creditMemo->getOrderCurrencyCode();
                            $mainArray[] = $productArray;
                        }

                    }

                    //API trigger for return order
                    $this->logger($mainArray,true);
                    $returnOrderId = $this->apiTrigger($mainArray,$orderIncrementId,$createdAt,'return',$sapOrderId);
                     if($returnOrderId){
                        $creditMemo->setSapReturnOrderId($returnOrderId);
                        $creditMemo->save();
                     }
                }
            }catch(\Exception $e)
        {
            $this->logger($e,true);
        }
    }
    public function returnSapId($orderId)
    {
        $finalId=null;
        $searchCriteria = $this->searchCriteriaBuilder
        ->addFilter('order_id', $orderId)->create();
        try {
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria);
            if($creditmemos->count()>0)
            {
                $creditmemoRecords = $creditmemos->getItems();
                foreach ($creditmemos as $creditmemo) {
                  $ids[] = $creditmemo->getSapReturnOrderId();
                }
                if($ids)
                {
                    $finalId = implode(", ",$ids);
                }
            }
        } catch (Exception $exception)  {
            $this->logger($exception->getMessage());
            $creditmemoRecords = null;
        }
        return $finalId;
    }
}
