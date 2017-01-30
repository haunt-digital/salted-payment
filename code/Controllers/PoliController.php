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
                $result = $this->handle_postback($token);
                return $this->route($result);
            }
        }

        $token = $request->postVar('Token');
        if (empty($token)) {
            $token = $request->getVar('token');
        }

        if (empty($token)) {
            return $this->httpError(400, 'Token is missing');
        }

        $this->handle_postback($token);
    }

    protected function handle_postback($data)
    {
        $result = Poli::fetch($data);
        // Debugger::inspect($result);
        if ($Order = $this->getOrder($result['MerchantReference'])) {
            if ($Order->isOpen) {

                if (!empty($Order->RecursiveFrequency)) {
                    $today = date("Y-m-d 00:00:00");
                    $Order->ValidUntil = date('Y-m-d', strtotime($today. ' + ' . $Order->RecursiveFrequency . ' days'));
                }

                if ($result['TransactionStatusCode'] == 'Status') {
                    $Order->isOpen = false;
                    $Order->write();
                }

                $payment = new PoliPayment();
                $payment->MerchantReference = $Order->MerchantReference;
                $payment->PaidByID = $Order->CustomerID;
                $payment->Amount->Currency = $Order->Amount->Currency;
                $payment->IP = $Order->PaidFromIP;
                $payment->ProxyIP = $Order->PaidFromProxyIP;
                $payment->Amount->Amount = $result['AmountPaid'];
                $payment->notify($result);

            } elseif ($payments = $Order->Payments()) {
                $payment = $payments->filter(array('MerchantReference' => $result['MerchantReference'], 'TransacID' => $result['TransactionRefNo']))->first();
                if (empty($payment)) {
                    return $this->httpError(400, 'cannot find the payment with the given merchant reference');
                }
            }

            $Order->onSaltedPaymentUpdate($payment->Status);
        }
        return $this->route_data($payment->Status, $Order->ID);
    }
}
