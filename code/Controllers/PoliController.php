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
                return $this->route($result);
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
        $payment = $this->existing_check($result['TransactionRefNo'], $result['MerchantReference']);

        if (empty($payment)) {
            // SS_Log::log("[" . $_SERVER['REQUEST_METHOD'] . "]POLi::::\n" . serialize($result), SS_Log::WARN);
            $payment = SaltedPaymentModel::get()->filter(array('OrderRef' => $result['MerchantReference']))->where('Status IS NULL')->first();

            if (empty($payment)) {

                $order_class = $result['MerchantReferenceData'];
                if ($order = DataObject::get_one($order_class, array('FullRef' => $result['MerchantReference']))) {
                    $payment = new PoliPayment();
                    $payment->PaidByID = $order->CustomerID;
                    $payment->OrderClass = $order_class;
                    $payment->OrderID = $order->ID;
                    $payment->Amount->Amount = $result['AmountPaid'];
                    $payment->ProcessedAt = $result['EndDateTime'];
                    $payment->write();
                } else {
                    SS_Log::log("[" . $_SERVER['REQUEST_METHOD'] . "]POLi::::\n" . serialize($result), SS_Log::WARN);
                    return $this->httpError(500, 'Order does not exist!');
                }
            }
        }
        $payment->notify($result);
        return $this->route_data($payment->Status, $payment->OrderClass, $payment->OrderID);
    }
}
