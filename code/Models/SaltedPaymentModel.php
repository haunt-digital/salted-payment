<?php

class SaltedPaymentModel extends DataObject
{
    protected $PaymentMethod = 'SaltedPayment';
    /**
     * Incomplete (default): Payment created but nothing confirmed as successful
     * Success: Payment successful
     * Failure: Payment failed during process
     * Pending: Payment awaiting receipt/bank transfer etc
     */
    protected static $db = array(
        'Status'            =>  "Enum('Incomplete,Success,Failure,Pending,Cancelled','Incomplete')",
        'Amount'            =>  'Money',
        'Message'           =>  'Text',
        'IP'                =>  'Varchar',
        'ProxyIP'           =>  'Varchar',
        'ProcessedAt'       =>  'SS_Datetime',
        //Used for store any Exception during this payment Process.
        'ExceptionError'    =>  'Text',
        'OrderClass'        =>  'Varchar',
        'OrderID'           =>  'Int',
        'OrderRef'          =>  'Varchar(64)'
    );

    protected static $has_one = array(
        'PaidBy'            =>  'Member'
    );

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    protected static $summary_fields = array(
        'Status'            =>  'Status',
        'MethodName'         =>  'Payment method',
        'Amount'            =>  'Amount',
        'IP'                =>  'Paid from',
        'ProcessedAt'       =>  'Paid at'
    );

    public function MethodName()
    {
        return $this->PaymentMethod;
    }

    /**
     * Default sort ordering
     * @var string
     */
    private static $default_sort = array(
        'Created'           =>  'DESC'
    );

    /**
     * Make payment table transactional.
     */
    public static $create_table_options = array(
        'MySQLDatabase'     =>  'ENGINE=InnoDB'
    );

    /**
     * The currency code used for payments.
     * @var string
     */
    protected static $site_currency = 'NZD';

    /**
     * Set the currency code that this site uses.
     * @param string $currency Currency code. e.g. "NZD"
     */
    public static function set_site_currency($currency)
    {
        self::$site_currency = $currency;
    }

    /**
     * Return the site currency in use.
     * @return string
     */
    public static function site_currency()
    {
        return self::$site_currency;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if (empty($this->Amount->Currency)) {
            $this->Amount->Currency = Config::inst()->get('SaltedPayment', 'DefaultCurrency');
        }

        if (!empty(Member::currentUserID())) {
            $this->PaidByID = Member::currentUserID();
        }

        if (empty($this->OrderClass)) {
            $this->OrderClass = Config::inst()->get('SaltedPayment', 'DefaultOrderClass');
        }

        if ($order = $this->Order()) {
            $this->OrderRef = $order->FullRef;
        }
    }

    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->OrderClass = Config::inst()->get('SaltedPayment', 'DefaultOrderClass');
        $this->Amount->Currency = self::site_currency();
        $this->setClientIP();
    }

    /**
     * Set the IP address of the user to this payment record.
     * This isn't perfect - IP addresses can be hidden fairly easily.
     */
    public function setClientIP()
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
        if (!$this->IP) {
            $this->IP = $ip;
        }
        if (!$this->ProxyIP) {
            $this->ProxyIP = $proxy;
        }
    }

    public function handleError($e)
    {
        $this->ExceptionError = $e->getMessage();
        $this->write();
    }

    public function process()
    {
        user_error("Please implement process() on $this->class", E_USER_ERROR);
    }

    public function notify($data)
    {
        user_error("Please implement notify() on $this->class", E_USER_ERROR);
    }

    public function Order()
    {
        if (!empty($this->OrderClass) && !empty($this->OrderID)) {
            $parent_class = get_parent_class($this->OrderClass);
            if ($parent_class == 'Page' || $parent_class == 'SiteTree') {
                return Versioned::get_by_stage($this->OrderClass, 'Stage')->byID($this->OrderID);
            }

            return DataObject::get_by_id($this->OrderClass, $this->OrderID);
        }

        return null;
    }

    public function notify_order()
    {
        if (!empty($this->OrderID) && !empty($this->OrderClass)) {
            $order = $this->Order();
            try {
                $order->onSaltedPaymentUpdate($this->Status == 'Success' ? true : false);
            } catch (Exception $e) {
                SS_Log::log('Class: ' . $this->OrderClass . ' has no method: onSaltedPaymentUpdate', SS_Log::WARN);
            }
        }
    }

}
