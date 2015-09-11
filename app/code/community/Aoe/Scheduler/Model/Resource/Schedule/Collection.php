<?php

class Aoe_Scheduler_Model_Resource_Schedule_Collection extends Mage_Cron_Model_Resource_Schedule_Collection
{
    /**
     * Event name prefix for events that are dispatched by this class
     *
     * @var string
     */
    protected $_eventPrefix = 'aoe_scheduler_schedule_collection';

    /**
     * Event parameter name that references this object in an event
     *
     * In an observer method you can use $observer->getData('collection') or $observer->getData('data_object') to get this object
     *
     * @var string
     */
    protected $_eventObject = 'collection';
}
