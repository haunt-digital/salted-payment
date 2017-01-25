<?php
use SaltedHerring\Debugger as Debugger;
use SaltedHerring\SaltedPayment\API\Paystation;
class PaystationPayment extends SaltedPaymentModel
{
    protected $PaymentMethod = 'Paystation';
    /**
     * Database fields
     * @var array
     */
    protected static $db = array(
        'MerchantSession'   =>  'Varchar(64)',
        'CardNumber'        =>  'Varchar(32)',
        'CardExpiry'        =>  'Varchar(8)',
        'ScheduleFuturePay' =>  'Boolean',
        'NextPayDate'       =>  'Date',
        'PaymentFrequency'  =>  'Int'
    );

    /**
     * Define the default values for all the $db fields
     * @var array
     */
    private static $defaults = array(
        'ScheduleFuturePay' =>  false
    );

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (empty($this->MerchantSession)) {
            $this->MerchantSession = sha1(session_id() . '-' . round(microtime(true) * 1000));
        }
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (empty($this->ProcessedAt) && ($this->Status == 'Incomplete' || empty($this->Status))) {
            $this->process();
        }
    }

    public function process()
    {
        if (empty($this->ID)) {
            return;
        }

        $order = $this->Order();
        $pay_link = Paystation::process($this->Amount->Amount, $order->FullRef, $this->MerchantSession, $this->ScheduleFuturePay, true, $order->ClassName);
        if (!empty($pay_link)) {
            if ($controller = Controller::curr()) {
                return $controller->redirect($pay_link);
            }
        }
    }

    public function notify($data)
    {
        if ($this->Status == 'Incomplete' || empty($this->Status)) {
            if (empty($data['ec']) || $data['ec'] == '0') {
                $this->TransacID            =   $data['ti'];
                $this->CardNumber           =   $data['cardno'];
                $this->CardExpiry           =   $data['cardexp'];
                $this->Status               =   'Success';
                $this->Message              =   $data['em'];
            } else {
                $this->ExceptionError       =   $data['em'];
                if ($data['ec'] == 34) {
                    $this->Status           =   'CardSavedOnly';
                } else {
                    $this->Status           =   'Failure';
                }
            }

            $this->ProcessedAt = date("Y-m-d H:i:s");
            $this->write();
            if (!empty($data['futurepaytoken']) && $this->ScheduleFuturePay) {
                Paystation::create_card($data['cardno'], $data['cardexp'], $data['futurepaytoken'], $this->PaidByID);
                $this->create_next_payment($data['futurepaytoken']);
            }
            $this->notify_order();
        }
    }

    protected function create_next_payment($fp_token, $scheduled_payment = null)
    {
        $scheduled_payment = empty($scheduled_payment) ? new PaystationPayment() : $scheduled_payment;
        $scheduled_payment->ScheduleFuturePay = true;
        $scheduled_payment->Status = 'Pending';
        $scheduled_payment->Amount->Amount = $this->Amount->Amount;
        $scheduled_payment->OrderClass = $this->OrderClass;
        $scheduled_payment->OrderID = $this->OrderID;
        $scheduled_payment->PaymentFrequency = $this->PaymentFrequency;
        $scheduled_payment->PaidByID = $this->PaidByID;
        $today = date("Y-m-d 00:00:00");
        $scheduled_payment->NextPayDate = date('Y-m-d', strtotime($today. ' + ' . $scheduled_payment->PaymentFrequency . ' days'));
        $scheduled_payment->write();
    }

    public function slient_process($enforce = false)
    {
        // todo
        /*
        1. get token from the stored creditcard. if not found, return
        2. compose link
        */
    }
}
