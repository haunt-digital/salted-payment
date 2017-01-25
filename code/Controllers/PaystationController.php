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
                // SS_Log::log("\n\n\n\n\n[PAYSTATION]\n" . $request->getBody() . "\n", SS_Log::WARN);
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

                // foreach ($data as $key => $value)
                // {
                //     SS_Log::log("\n[".$key."] " . $value . "\n", SS_Log::WARN);
                // }

                if ($xmlData->FuturePaymentToken) {
                    $data['futurepaytoken'] = (string) $xmlData->FuturePaymentToken;
                }

                $this->update_payment($data);
            } catch (Exception $e) {
                SS_Log::log("[PAYSTATION]\n" . $request->getBody(), SS_Log::WARN);
            }
            //SS_Log::log($_SERVER['REQUEST_METHOD'] . '::::::' . $xmlData->em, SS_Log::WARN);
        } else {
            // Debugger::inspect($request->getVars());
            /*
            [url] => /salted-payment/paystation-complete
            [ms] => 27b98051bab8c949d92278b060195bf14e192e87
            [ti] => 0086146613-01
            [am] => 7800
            [ec] => 0
            [em] => Transaction successful
            [cardno] => 512345XXXXXXX346
            [cardexp] => 1705
            [merchant_ref] => 92be7f2fbbb2653c6a46fcf761af2713f7b3a003
            */
            $result = $this->update_payment($request->getVars());
            $this->route($result);
        }
    }


    protected function update_payment($data)
    {
        if (!empty($data['ms'])) {

            if (!empty($data['ec']) && $data['ec'] == 34 && !empty(Member::currentUserID())) {
                Paystation::create_card($data['cardno'], $data['cardexp'], $data['futurepaytoken'], Member::currentUserID());
                return $this->route_data('CardSavedOnly', 'Member', Member::currentUserID());
            }

            if ($payment = PaystationPayment::get()->filter(array('MerchantSession' => $data['ms']))->first()) {
                $payment->notify($data);
                return $this->route_data($payment->Status, $payment->OrderClass, $payment->OrderID);
            } else {
                // $payment = new Payment();
                // $payment->PaidByID = Member::currentUserID();
                // $payment->ValidUntil = $property->ListingCloseOn;
                //
                // $payment->Amount->Amount = $amount;
                // $payment->OrderClass = 'PropertyPage';
                // $payment->OrderID = $property->ID;
                // $payment->write();
            }
        }

        return $this->httpError(400, 'malformed data');
    }

    private function create_card($cardno, $cardexp, $fp_token)
    {
        $card = StoredCreditcard::get()->filter(array('CardNumber' => $cardno, 'CardExpiry' => $cardexp))->first();
        if (empty($card)) {
            $card = new StoredCreditcard();
            $card->CardNumber = $cardno;
            $card->CardExpiry = $cardexp;
        }

        $card->FuturePayToken = $fp_token;
        $card->MemberID = $this->PaidByID;
        $card->write();
    }
}
