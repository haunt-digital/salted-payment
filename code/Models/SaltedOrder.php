<?php
use SaltedHerring\Debugger;
use SaltedHerring\Grid;
use SaltedHerring\SaltedPayment;
use SaltedHerring\SaltedPayment\API\Paystation;
use SaltedHerring\SaltedPayment\API\Poli;
class SaltedOrder extends DataObject
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'isOpen'                =>  'Boolean', //auto fill
        'Amount'                =>  'Money', //Currency part is auto fill
        'MerchantReference'     =>  'Varchar(64)', //auto fill
        'MerchantSession'       =>  'Varchar(64)', //auto fill
        'PaidFromIP'            =>  'Varchar', //auto fill
        'PaidFromProxyIP'       =>  'Varchar', //auto fill
        'PayDate'               =>  'Date',
        'RecursiveFrequency'    =>  'Int',
        'ValidUntil'            =>  'Date'
    );

    /**
     * Default sort ordering
     * @var string
     */
    private static $default_sort = array(
        'ID'                    =>  'DESC'
    );

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = array(
        'Customer'              =>  'Member', //auto fill
        'WillbePaidByCard'      =>  'StoredCreditcard'
    );

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = array(
        'getStatus'             =>  'Open / Close',
        'PayDateDisplay'        =>  'Pay date',
        'Amount'                =>  'Amount',
        'OutstandingBalance'    =>  'Outstanding Balance'
    );

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if ($member = Member::currentUser()) {
            if (!empty($this->Payments())) {
                $fields->addFieldToTab(
                    'Root.Payments',
                    $member->inGroup('administrators') ? Grid::make('Payments', 'Payments', $this->Payments(), false) : Grid::make('Payments', 'Payments', $this->Payments(), false, 'GridFieldConfig_RecordViewer')
                );
            }
        }

        return $fields;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if (empty($this->MerchantReference)) {
            $created = new DateTime('NOW');
            $timestamp = $created->format('YmdHisu');
            $this->MerchantReference = strtolower(sha1(md5($timestamp.'-'.session_id())));
        }
    }

    /**
     * Event handler called before deleting from the database.
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        if ($payments = $this->Payments()) {
            foreach ($payments as $payment) {
                $payment->delete();
            }
        }
    }

    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->isOpen = true;
        $this->Amount->Currency = Config::inst()->get('SaltedPayment', 'DefaultCurrency');
        $this->CustomerID = Member::currentUserID();
        $created = new DateTime('NOW');
        $timestamp = $created->format('YmdHisu');
        $this->MerchantReference = strtolower(sha1(md5($timestamp.'-'.session_id())));
    }

    public function Payments()
    {
        if (empty($this->ID)) {
            return null;
        }

        $payments = SaltedPaymentModel::get()->filter(array('MerchantReference' => $this->MerchantReference));
        return $payments->count() > 0 ? $payments : null;
    }

    // public function PayNow($payment_method)
    // {
    //     return $this->Pay($payment_method);
    // }
    //
    // public function PayLater($payment_method = 'Paystation')
    // {
    //     return $this->Pay($payment_method, true, false);
    // }

    public function Pay($payment_method, $setup_future_payment = false)
    {
        $this->setClientIP();
        $this->MerchantSession = sha1(session_id() . '-' . round(microtime(true) * 1000));
        $pay_link = null;

        switch (strtolower($payment_method)) {

            case 'poli':
                if ($setup_future_payment) {
                    return $this->httpError(400, 'POLi does not support future payment');
                }
                $result = Poli::process($this->Amount->Amount, $this->MerchantReference);
                if (!empty($result['Success']) && !empty($result['NavigateURL'])) {
                    $pay_link = $result['NavigateURL'];
                } elseif (!empty($result['ErrorCode']) && !empty($result['ErrorMessage'])) {
                    SS_Log::log("POLi::::\n" . serialize($result), SS_Log::ERR);
                }

                break;

            case 'paystation':

                $pay_link = Paystation::process($this->Amount->Amount, $this->MerchantReference, $this->MerchantSession, $setup_future_payment);
                if (empty($pay_link)) {
                    SS_Log::log("Paystation::::\n" . serialize($pay_link), SS_Log::ERR);
                }

                break;

            default:

                break;
        }

        if (!empty($pay_link)) {
            $this->write();
            if ($controller = Controller::curr()) {
                return $controller->redirect($pay_link);
            }

            return true;
        }

        return Controller::curr()->httpError(400, 'Payment gateway error');
    }

    public function onSaltedPaymentUpdate($success)
    {
        user_error("Please implement onSaltedPaymentUpdate() on $this->class", E_USER_ERROR);
    }

    public function onValidDurationRunsOut()
    {
        user_error("Please implement onValidDurationRunsOut() on $this->class", E_USER_ERROR);
    }

    /**
     * Set the IP address of the user to this payment record.
     * This isn't perfect - IP addresses can be hidden fairly easily.
     */
    protected function setClientIP()
    {
        $proxy = null;
        $ip = null;

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = null;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $proxy = $ip;
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        // Only set the IP and ProxyIP if none currently set
        if (!$this->PaidFromIP) {
            $this->PaidFromIP = $ip;
        }
        if (!$this->PaidFromProxyIP) {
            $this->PaidFromProxyIP = $proxy;
        }
    }

    public function isFuturePayment()
    {
        if (empty($this->PayDate)) {
            return false;
        }

        $now = new DateTime('NOW');
        $paydate = new DateTime($this->PayDate);

        return $now < $paydate;
    }

    public function getStatus()
    {
        return $this->isOpen ? 'Open' : 'Close';
    }

    public function getSuccessPayment()
    {
        if ($payments = $this->Payments())
        {
            return $payments->filter(array('Status' =>  'Success'))->first();
        }

        return null;
    }

    public function OutstandingBalance()
    {
        $amount = $this->Amount->Amount;
        if ($payments = $this->Payments())
        {
            if ($payment = $payments->filter(array('Status' =>  'Success'))->first()) {
                return '$' . number_format($amount - $payment->Amount->Amount, 2, '.', ',');
            }
        }

        return '$' . number_format($amount, 2, '.', ',');;;
    }

    public function PayDateDisplay()
    {
        if ($this->isOpen) {
            return '- not yet paid -';
        }

        return !empty($this->PayDate) ? $this->PayDate : $this->Created;
    }

    //static functions

    public static function prepare_order()
    {
        $OrderClass = SaltedPayment::get_default_order_class();
        $Order = $OrderClass::get()->filter(array('isOpen' => true))->where('PayDate IS NULL')->first();

        return !empty($Order) ? $Order : new $OrderClass();

    }
}
