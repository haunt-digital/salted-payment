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

            if (!empty($data['ec']) && $data['ec'] == 34 && !empty(Member::currentUserID())) {
                Paystation::create_card($data['cardno'], $data['cardexp'], $data['futurepaytoken'], Member::currentUserID());
                return $this->route_data('CardSavedOnly', 'Member', Member::currentUserID());
            }

        }

        return $this->route_data();
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
