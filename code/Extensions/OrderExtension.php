<?php

class OrderExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FullRef'       =>  'Varchar(64)'
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
        if (empty($this->owner->CustomerID)) {
            $this->owner->CustomerID = Member::currentUserID();
        }
    }

    public function Payments()
    {
        if (empty($this->owner->ID)) {
            return null;
        }
        $payments = SaltedPaymentModel::get()->filter(array('OrderID' => $this->owner->ID, 'OrderClass' => $this->owner->ClassName));
        return $payments->count() > 0 ? $payments : null;
    }
}
