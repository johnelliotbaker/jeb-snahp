<?php
namespace jeb\snahp\event;

use jeb\snahp\core\base;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class registration_listener extends base implements EventSubscriberInterface
{
    protected $table_prefix;
    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.ucp_register_data_after' => [
                ['validate_invite', 0],
            ],
            'core.user_add_after' => [
                ['update_invite_after_user_addition', 0],
            ],
        ];
    }

    public function validate_invite($event)
    {
        prn('validate');
    }

    public function update_invite_after_user_addition($event)
    {
        prn('registered');
    }

}
