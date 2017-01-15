<?php

class StoredCreditcard extends DataObject
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'CardNumber'        =>  'Varchar(32)',
        'CardExpiry'        =>  'Varchar(8)',
        'FuturePayToken'    =>  'Varchar(64)',
        'isPrimary'         =>  'Boolean'
    );

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = array(
        'getCardType'       =>  'Card type',
        'CardNumber'        =>  'Card number',
        'formated_expiry'   =>  'Expiry',
        'isPrimary'         =>  'Primary'
    );

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = array(
        'Member'            =>  'Member'
    );

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->Member()->Creditcards()->count() == 0) {
            $this->isPrimary = true;
        } elseif ($this->Member()->Creditcards()->filter(array('isPrimary' => true))->count() == 0) {
            $this->isPrimary = true;
        }
    }

    public function getYear()
    {
        $expiry = $this->CardExpiry;
        $year = substr($expiry, 0, 2);

        return $year;
    }

    public function getMonth()
    {
        $expiry = $this->CardExpiry;
        $month = substr($expiry, 2, 2);

        return $month;
    }

    public function formated_expiry($separator = '/')
    {
        $expiry = $this->CardExpiry;
        $year = substr($expiry, 0, 2);
        $month = substr($expiry, 2, 2);

        return $month . $separator . $year;
    }

    public function isMastercard()
    {
        $prefix = substr($this->CardNumber, 0, 2);
        $prefix = (int) $prefix;
        return $prefix >= 50 && $prefix <= 55;

    }

    public function isVisa()
    {
        return substr($this->CardNumber, 0, 1) == 4;
    }

    public function getCardType()
    {
        if ($this->isMastercard()) {
            return 'master';
        }

        if ($this->isVisa()) {
            return 'visa';
        }

        return 'unknown';
    }

}
