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
        'CardExpiry'        =>  'Varchar(8)'
    );
}
