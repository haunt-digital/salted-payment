<?php
use SaltedHerring\Debugger;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Utilities;
use SaltedHerring\SaltedPayment\API\Poli;
class PoliController extends ContentController
{
    public function index($request)
    {
        if (!$request->isPost()) {
            if ($token = $request->getVar('token')) {
                $result = $this->update_payment($token);
                $this->route($result);
            }
        }

        $token = $request->postVar('Token');
        if (empty($token)) {
            $token = $request->getVar('token');
        }

        $this->update_payment($token);
    }

    private function route($result)
    {
        $state = $result['state'];
        $orderID = $result['order_id'];

        $url = array();

        if ($state == 'Completed') {
            $url['url'] = SaltedPayment::get_merchant_setting('SuccessURL');
            $url = Utilities::LinkThis($url, 'order_id', $orderID);
        } elseif ($state == 'Cancelled') {
            $url['url'] = SaltedPayment::get_merchant_setting('CancellationURL');
            $url = Utilities::LinkThis($url, 'order_id', $orderID);
        } else {
            $url['url'] = SaltedPayment::get_merchant_setting('FailureURL');
            $url = Utilities::LinkThis($url, 'order_id', $orderID);
        }

        return $this->redirect($url);
    }

    private function update_payment($token)
    {
        $result = Poli::fetch($token);
        $payment = SaltedPaymentModel::get()->filter(array('OrderRef' => $result['MerchantReference']))->first();
        // SS_Log::log($token, SS_Log::WARN);
        $payment->notify($result);

        return array(
                    'state' => !empty($result['TransactionStatusCode']) ? trim($result['TransactionStatusCode']) : 'Failed',
                    'order_id' => $payment->OrderID
                );
    }
}
