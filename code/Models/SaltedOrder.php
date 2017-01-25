<?php
use SaltedHerring\Debugger;

class SaltedOrder extends DataObject
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FullRef'           =>  'Varchar(64)',
        'MerchantSession'   =>  'Varchar(64)'
    );

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = array(
        'Customer'      =>  'Member'
    );

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (empty($this->CustomerID)) {
            $this->CustomerID = Member::currentUserID();
        }
    }

    public function Payments()
    {
        if (empty($this->ID)) {
            return null;
        }
        $payments = SaltedPaymentModel::get()->filter(array('OrderID' => $this->ID, 'OrderClass' => $this->ClassName));
        return $payments->count() > 0 ? $payments : null;
    }

    public function InitialisePayment()
    {
        
    }
}
