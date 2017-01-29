<?php
use SaltedHerring\Debugger;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Utilities;
use SaltedHerring\SaltedPayment\API\Paystation;
class SaltedPaymentController extends ContentController
{
    protected function route($result)
    {
        $state = $result['state'];
        $orderID = $result['order_id'];
        $url = array();

        if ($state == 'Success') {
            $url['url'] = SaltedPayment::get_merchant_setting('SuccessURL');
        } elseif ($state == 'Cancelled') {
            $url['url'] = SaltedPayment::get_merchant_setting('CancellationURL');
        } elseif ($state == 'CardSavedOnly') {
            $url['url'] = SaltedPayment::get_merchant_setting('CardSavedURL');
        } elseif ($state == 'Pending') {
            $url['url'] = SaltedPayment::get_merchant_setting('PaymentScheduledURL');
        } else {
            $url['url'] = SaltedPayment::get_merchant_setting('FailureURL');
        }

        $url = Utilities::LinkThis($url, 'order_id', $orderID);

        return $this->redirect($url);
    }

    protected function route_data($state = 'Failed', $order_id = null)
    {
        return array(
                    'state'         =>  $state,
                    'order_id'      =>  $order_id
                );
    }

    protected function handle_postback($data)
    {
        user_error("Please implement handle_postback() on $this->class", E_USER_ERROR);
    }

    protected function getOrder($merchant_reference)
    {
        $OrderClass = SaltedPayment::get_default_order_class();
        return $OrderClass::get()->filter(array('MerchantReference' => $merchant_reference))->first();
    }
}
