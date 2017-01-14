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
        'TransacID'         =>  'Varchar(64)',
        'CardNumber'        =>  'Varchar(32)',
        'CardExpiry'        =>  'Varchar(8)'
    );

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->MerchantSession = sha1(session_id() . '-' . round(microtime(true) * 1000));
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (empty($this->ProcessedAt)) {
            $this->process();
        }
    }

    public function process()
    {
        $order = $this->Order();

        // $result = Poli::process($order->AmountDue, $order->FullRef);
        // Debugger::inspect($result);
        $pay_link = Paystation::process($this->Amount->Amount, $order->FullRef, $this->MerchantSession);
        if (!empty($pay_link)) {
            if ($controller = Controller::curr()) {
                return $controller->redirect($pay_link);
            }
        }
        Debugger::inspect($pay_link);
    }

    public function notify($data)
    {
        if (empty($data['ec'])) {
            $this->TransacID        =   $data['ti'];
            $this->CardNumber       =   $data['cardno'];
            $this->CardExpiry       =   $data['cardexp'];
            $this->Status           =   'Success';
            $this->Message          =   $data['em'];
        } else {
            $this->ExceptionError   =   $data['em'];
            $this->Status           =   'Failure';
        }

        $this->ProcessedAt = date("Y-m-d H:i:s");
        $this->write();
        $this->notify_order();
    }
}
