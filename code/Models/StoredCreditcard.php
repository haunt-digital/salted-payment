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
        'CardNumber'        =>  'Card number',
        'CardExpiry'        =>  'Expiry',
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
}
