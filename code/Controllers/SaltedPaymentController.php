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
        $url['order_class'] = $result['order_class'];

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

    protected function route_data($state = 'Failed', $order_class = null, $order_id = null)
    {
        return array(
                    'state'         =>  $state,
                    'order_class'   =>  $order_class,
                    'order_id'      =>  $order_id
                );
    }

    protected function update_payment($data)
    {
        user_error("Please implement update_payment() on $this->class", E_USER_ERROR);
    }
}
