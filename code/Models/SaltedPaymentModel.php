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
        'TransacID'         =>  'Varchar(128)',
        'Status'            =>  "Enum('Incomplete,Success,Failure,Pending,Cancelled,CardSavedOnly','Incomplete')",
        'Amount'            =>  'Money',
        'Message'           =>  'Text',
        'IP'                =>  'Varchar',
        'ProxyIP'           =>  'Varchar',
        //Used for store any Exception during this payment Process.
        'ExceptionError'    =>  'Text',
        'MerchantReference' =>  'Varchar(64)',
        'MerchantSession'   =>  'Varchar(64)'
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
        'MethodName'        =>  'Payment method',
        'Amount'            =>  'Amount',
        'IP'                =>  'Paid from',
        'Created'           =>  'Paid at'
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

    public function handleError($e)
    {
        $this->ExceptionError = $e->getMessage();
        $this->write();
    }

}
