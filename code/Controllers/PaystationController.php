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
            // SS_Log::log($_SERVER['REQUEST_METHOD'] . '::::::' . $request->getBody(), SS_Log::WARN);
            try {
                $xmlData = new SimpleXMLElement($request->getBody());
                // SS_Log::log("\n[PAYSTATION]\nMS: " . $xmlData->MerchantSession . "\n", SS_Log::WARN);
                SS_Log::log("\n\n\n\n\n[PAYSTATION]\n" . $request->getBody() . "\n", SS_Log::WARN);
                $data = array(
                    'ms'            =>  (string) $xmlData->MerchantSession,
                    'ti'            =>  (string) $xmlData->TransactionID,
                    'am'            =>  (string) $xmlData->PurchaseAmount,
                    'ec'            =>  (string) $xmlData->ec,
                    'em'            =>  (string) $xmlData->em,
                    'cardno'        =>  (string) $xmlData->CardNo,
                    'cardexp'       =>  (string) $xmlData->CardExpiry,
                    'merchant_ref'  =>  (string) $xmlData->MerchantReference
                );

                if ($xmlData->FuturePaymentToken) {
                    $data['futurepaytoken'] = (string) $xmlData->FuturePaymentToken;
                }

                $this->handle_postback($data);
            } catch (Exception $e) {
                SS_Log::log("[PAYSTATION]\n" . $request->getBody(), SS_Log::WARN);
            }
            //SS_Log::log($_SERVER['REQUEST_METHOD'] . '::::::' . $xmlData->em, SS_Log::WARN);
        } else {
            //ms=e94f0ab63384141293ae8afedb796504678bbd11&ti=0086432220-01&am=5000&ec=0&em=Transaction+successful&futurepaytoken=6cc366n144af0lnemc4e9uk415dpiv17dx6iii0vz7z3f1p8y5748gf12nhxpu88&cardno=512345XXXXXXX346&cardexp=1705&merchant_ref=5a93994930110421e600aff80ad8bcb8942aa677
            $result = $this->handle_postback($request->getVars());
            $this->route($result);
        }
    }


    protected function handle_postback($data)
    {
        if (!empty($data['ms'])) {
            // if it is just to setup a creditcard
            if (empty($data['merchant_ref'])) {
                if (!empty($data['ec']) && $data['ec'] == 34 && !empty(Member::currentUserID())) {
                    Paystation::create_card($data['cardno'], $data['cardexp'], $data['futurepaytoken'], Member::currentUserID());
                    return $this->route_data('CardSavedOnly', Member::currentUserID());
                }
            } else {
                if ($Order = $this->getOrder($data['merchant_ref'])) {
                    if ($payments = $Order->Payments()) {
                        $payment = $payments->filter(array('MerchantSession' => $data['ms']))->first();
                    }

                    if ($Order->isOpen) {
                        if (empty($payment)) {
                            $payment = new PaystationPayment();
                            $payment->MerchantReference = $Order->MerchantReference;
                            $payment->MerchantSession = $Order->MerchantSession;
                            $payment->PaidByID = $Order->CustomerID;
                            $payment->Amount->Currency = $Order->Amount->Currency;
                            $payment->IP = $Order->PaidFromIP;
                            $payment->ProxyIP = $Order->PaidFromProxyIP;

                            if (empty($data['ec']) || $data['ec'] == '0') {

                                if (!empty($Order->RecursiveFrequency)) {
                                    $today = date("Y-m-d 00:00:00");
                                    $Order->ValidUntil                  =   date('Y-m-d', strtotime($today. ' + ' . $Order->RecursiveFrequency . ' days'));
                                }

                                $Order->isOpen                          =   false;
                                $Order->write();

                                $payment->TransacID                     =   $data['ti'];
                                $payment->Amount->Amount                =   ((float) $data['am']) * 0.01;
                                $payment->CardNumber                    =   $data['cardno'];
                                $payment->CardExpiry                    =   $data['cardexp'];
                                $payment->Status                        =   'Success';
                                $payment->Message                       =   $data['em'];

                                if (!empty($data['futurepaytoken'])) {
                                    $card = Paystation::create_card($data['cardno'], $data['cardexp'], $data['futurepaytoken'], $payment->PaidByID);
                                    $OrderClass                         =   SaltedPayment::get_default_order_class();
                                    $futureOrder                        =   $Order->duplicate(false);
                                    $futureOrder->MerchantReference     =   null;
                                    $futureOrder->MerchantSession       =   null;
                                    $futureOrder->isOpen                =   true;
                                    $futureOrder->ValidUntil            =   null;
                                    $futureOrder->WillbePaidByCardID    =   $card->ID;
                                    $futureOrder->PayDate               =   date('Y-m-d', strtotime($Order->ValidUntil. ' + 1 day'));
                                    $futureOrder->write();
                                }

                            } else {

                                $payment->ExceptionError                =   $data['em'];
                                if ($data['ec'] == 34) {
                                    $payment->Status                    =   'CardSavedOnly';
                                } else {
                                    $payment->Status                    =   'Failure';
                                }

                            }

                            $payment->write();
                        }

                    }

                    $Order->onSaltedPaymentUpdate($payment->Status);
                    return $this->route_data($payment->Status, $Order->ID);
                }
            }

            return $this->httpError(400, 'Order not found');

        }

        return $this->httpError(400, 'malformed data');
    }


}
