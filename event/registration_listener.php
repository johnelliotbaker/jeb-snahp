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
            'core.user_add_modify_data' => [
                ['validate_invite', 0],
            ],
            'core.user_add_after' => [
                ['update_invite_after_user_addition', 0],
            ],
        ];
    }

    public function validate_invite($event)
    {
        $keyphrase = $this->request->variable('invitation_code', '');
        if (!$keyphrase)
        {
            trigger_error('Invitation code is required.');
        }
        $invite_data = $this->select_invite($where="keyphrase='$keyphrase'");
        if (!$invite_data)
        {
            trigger_error('Sorry, that invitation code is invalid.');
        }
        $invite_data = $invite_data[0];
        if (!$invite_data['b_active'])
        {
            trigger_error('Sorry, that invitation code has been deactivated.');
        }
    }

    public function update_invite_after_user_addition($event)
    {
        $user_id = $event['user_id'];
        $keyphrase = $this->request->variable('invitation_code', '');
        $this->redeem_invite($keyphrase, $user_id);
    }

}
