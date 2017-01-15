<?php
use SaltedHerring\Debugger;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Utilities;
use SaltedHerring\SaltedPayment\API\Paystation;
class PaystationController extends SaltedPaymentController
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


    protected function update_payment($data)
    {
        if (!empty($data['ms'])) {
            if ($payment = PaystationPayment::get()->filter(array('MerchantSession' => $data['ms']))->first()) {
                $payment->notify($data);
                return $this->route_data($payment->Status, $payment->OrderClass, $payment->OrderID);
            }
        }

        return $this->route_data();
    }
}
