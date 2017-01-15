<?php
use SaltedHerring\Debugger;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Utilities;
use SaltedHerring\SaltedPayment\API\Poli;
class PoliController extends SaltedPaymentController
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

    protected function update_payment($data)
    {
        $result = Poli::fetch($data);
        $payment = SaltedPaymentModel::get()->filter(array('OrderRef' => $result['MerchantReference']))->first();
        // SS_Log::log($token, SS_Log::WARN);
        $payment->notify($result);
        return $this->route_data($payment->Status, $payment->OrderClass, $payment->OrderID);
    }
}
