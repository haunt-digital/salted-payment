<?php
use SaltedHerring\Debugger;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Utilities;
use SaltedHerring\SaltedPayment\API\Paystation;
class PaystationController extends ContentController
{
    public function index($request)
    {
        if ($request->isPost()) {
            SS_Log::log($_SERVER['REQUEST_METHOD'] . '::::::' . $request->getBody(), SS_Log::WARN);
        } else {
            $result = $this->update_payment($request->getVars());
            $this->route($result);
        }
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

    private function update_payment($data)
    {
        if (!empty($data['ms'])) {
            if ($payment = PaystationPayment::get()->filter(array('MerchantSession' => $data['ms']))->first()) {
                $payment->notify($data);

                return array(
                            'state'     =>  empty($data['ec']) ? 'Completed' : 'Failed',
                            'order_id'  =>  $payment->OrderID
                        );
            }
        }

        return array(
                    'state'     =>  'Failed',
                    'order_id'  =>  null
                );
    }
}
