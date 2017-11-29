<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Store\PostCode\Block;

use Magento\Customer\Model\Session;
/**
 * Description of Form
 *
 * @author HP
 */
class Form extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface{
    protected $_template = "form_info.phtml";
    protected $customerSession;
    protected $check = false;
    protected  $_resource;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    )
    {     
        
        parent::__construct($context, $data);
        $this->_resource = $resource;
        $this->customerSession = $customerSession;
       
    }

    
    public function getStoreUrl($postCode){
        $connection = $this->_resource->getConnection('Magento\Framework\App\ResourceConnection');
        $tableName = $connection->getTableName('core_config_data');
        $tableStore = $connection->getTableName('store');
        
        $select = $connection->select()->from($tableName, "scope_id")
            ->where('value=?', $postCode)
            ->where('path =?','general/store_information/postcode' );
        $result1 = $connection->fetchAll($select);
        //var_dump($result1);die();
        $url = [];
        if($result1 != null){
            $scope_id = [];
            foreach ($result1 as $value) {
                $scope_id[] = $value["scope_id"];
            }
            $in = '(' . implode(',', $scope_id) .')';
            $fields = array('store_id', 'code');
            $select1 = $connection->select()->from($tableStore, $fields)
            ->where('store_id <> ?', 1)
            ->where('website_id IN '.$in);        
            $result2 = $connection->fetchAll($select1);
            $store_id = [];
            $code = [];
            foreach ($result2 as $key => $value) {
                $store_id[] = $value["store_id"];
                $code[] = $value["code"];
            }
            
            $in = '(' . implode(',', $store_id) .')';
            $select2 = $connection->select()->from($tableName, 'value')
                    ->where('scope = ?', 'stores')
                    ->where('path = ?', 'web/unsecure/base_url')
                    ->where('scope_id IN '.$in);  
            $result3 = $connection->fetchAll($select2);
            
            foreach ($result3 as $key => $value) {

                $url[$key] = $value["value"].$code[$key];
            }
           
        }
        else{
            $scope_id = 0;
            $select = $connection->select()->from($tableName, 'value')
                    ->where('scope_id = ?', $scope_id)
                    ->where('path = ?', 'web/unsecure/base_url');        
            $result = $connection->fetchAll($select);
            $url[] = $result[0]["value"]."default";
        }
        
        return $url;
        
    }
    
    public function checkCookie(){
        $check = false;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cookieManager = $objectManager->get('Magento\Framework\Stdlib\CookieManagerInterface');
        $value = $cookieManager->getCookie('path');
        
        $url = null;
        
        if($this->customerSession->isLoggedIn() ){
            $connection = $this->_resource->getConnection('Magento\Framework\App\ResourceConnection');
            $tableName = $connection->getTableName('customer_entity');
            $customer_id = $this->customerSession->getCustomerId();
            $select = $connection->select()->from($tableName, 'postcode')
                    ->where('entity_id = ?', $customer_id);
            $result = $connection->fetchAll($select);
            $url = $result[0]["postcode"];
            echo $this->customerSession->getCustomerId();
            
        }
//        print_r($this->customerSession->getCustomer()->getData());
//        var_dump($this->customerSession->getCustomer()->getData());
//        die("mm");
        if($value == null && $url == null){
            $check =  false;
        }else{
            $check = true;
        }
        
        return $check;
    }
    
}
