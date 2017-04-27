<?php
/**
 * Pravams Stripe Module
 *
 * @category    Pravams
 * @package     Pravams_Stripe
 * @copyright   Copyright (c) 2015 Pravams LLC. (http://www.pravams.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Pravams_Stripe_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc
{
    protected $_code = 'stripe';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;

    protected $_realTransactionIdKey = 'real_transaction_id';

    /*
     * Form block type
     * */
    protected $_formBlockType = 'payment/form_cc';

    /*
     * Info block type
     * */
    protected $_infoBlockType = 'payment/info_cc';

    public function authorize(Varien_Object $payment, $amount){
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payment')->__('Invalid amount for authorization'));
        }
        Mage::throwException(Mage::helper('payment')->__('This extension does not support only authorization'));
        //$this->_place($payment, $amount, self::REQUEST_TYPE_AUTH_ONLY);
        return $this;
    }


    public function capture(Varien_Object $payment, $amount){

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payment')->__('Invalid amount for capture'));
        }

        require_once('stripe-php/vendor/autoload.php');

//        $stripe = array(
//            secret_key      => Mage::getStoreConfig('payment/stripe/secretkey'),
//            publishable_key => Mage::getStoreConfig('payment/stripe/publishablekey')
//        );

        $stripe['secret_key'] = Mage::getStoreConfig('payment/stripe/secretkey');
        $stripe['publishable_key'] = Mage::getStoreConfig('payment/stripe/publishablekey');

        \Stripe\Stripe::setApiKey($stripe['secret_key']);

        $source['object'] = 'card';
        $source['number'] = $payment->getCcNumber();
        $source['exp_month'] = $payment->getCcExpMonth();
        $source['exp_year'] = $payment->getCcExpYear();
        $source['cvc'] = $payment->getCcCid();

        $grandTotal = $payment->getAmountOrdered();
        $grandTotal = $grandTotal*100;

        $chargeDescription = "Charge for ".$payment->getOrder()->getCustomer()->getEmail();

        $charge = \Stripe\Charge::create(array(
            "amount" => $grandTotal,
            "currency" => "usd",
            "source" => $source, // obtained with Stripe.js
            "description" => $chargeDescription
        ));

        /* response from the Stripe server */
        $responseTransactionId = $charge['id'];
        $responsePaid = $charge['paid'];
        $responseStatus = $charge['status'];
        $responseBalanceTransaction = $charge['balance_transaction'];

        $resSource['id'] = $charge['source']['id'];
        $resSource['Brand'] = $charge['source']['brand'];
        $resSource['Last4'] = $charge['source']['last4'];
        $resSource['Cvc'] = $charge['source']['cvc_check'];
        $resSource['FingerPrint'] = $charge['source']['fingerprint'];

        if($charge['status'] == 'succeeded'){
            $this->_addTransaction(
                $payment,
                $responseTransactionId,
                'capture',
                $resSource,
                array($this->_realTransactionIdKey => $responseTransactionId),
                $chargeDescription
            );
        }else{
            Mage::throwException(Mage::helper('payment')->__('Sorry there was an error. Please try again.'));
        }

        return $this;
    }

    public function void(Varien_Object $payment){
        Mage::throwException(Mage::helper('payment')->__('This extension does not support void transaction'));
        return $this;
    }

    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType,
                                       array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }
        $payment->unsLastTransId();

        /**
         * It for self using
         */
        $transaction->setMessage($message);

        return $transaction;
    }

}