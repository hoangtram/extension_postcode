<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Store\PostCode\Controller\Account;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;

/**
 * Description of LoginPost
 *
 * @author HP
 */
class LoginPost extends \Magento\Customer\Controller\Account\LoginPost {
    const JOB_COOKIE_NAME = 'path';
    const JOB_COOKIE_DURATION = 3600;
    public function execute() {
        
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                            'This account is not confirmed.' .
                            ' <a href="%1">Click here</a> to resend confirmation email.', $value
                    );
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                } catch (AuthenticationException $e) {
                    $message = __('Invalid login or password.');
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('Invalid login or password.'));
                }
            } else {
                $this->messageManager->addError(__('A login and a password are required.'));
            }
        }
        $customer_id = $this->session->getCustomer()->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('customer_entity');
        $select = $connection->select()->from($tableName, "postcode")
            ->where('entity_id = ? ', $customer_id);
        $result = $connection->fetchAll($select);
        
        $url = $result[0]["postcode"];
        
        $tableConfig = $connection->getTableName('core_config_data');
        $select1 = $connection->select()->from($tableConfig, "value")
            ->where('scope_id=?', 0)
            ->where('path=?', 'web/unsecure/base_url');     
        $result1 = $connection->fetchAll($select1);
        $baseurl = $result1[0]["value"];
        
        if($url == null){
            
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }else{
            $path = str_replace($baseurl,"",$url);
            $cookieMetadata = $this->_objectManager->get('Magento\Framework\Stdlib\Cookie\CookieMetadataFactory');
            $cookieManager = $this->_objectManager->get('Magento\Framework\Stdlib\CookieManagerInterface');
            $metadata = $cookieMetadata
            ->createPublicCookieMetadata() 
            ->setDuration(self::JOB_COOKIE_DURATION)
            ->setPath("/");
            $cookieManager->setPublicCookie(
                self::JOB_COOKIE_NAME,
                $path,
                $metadata
            );
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }
       
    }

}
