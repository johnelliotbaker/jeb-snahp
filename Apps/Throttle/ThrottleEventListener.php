<?php
namespace jeb\snahp\Apps\Throttle;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ThrottleEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $helper;
    public function __construct(/*{{{*/
        $user,
        $config,
        $sauth,
        $helper
    ) {
        $this->user = $user;
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.user_setup_after' => [
                ['throttle', 10],
            ],
        ];
    }/*}}}*/

    public function throttle($event)/*{{{*/
    {
        $enable_master = $this->config['snp_throttle_enable_master'] ?? 0;
        if (!$enable_master) {
            return;
        }
        if ($this->sauth->user_belongs_to_groupset($this->userId, 'TU+') || $this->sauth->is_dev() || $this->userId === ANONYMOUS) {
            return;
        }
        $enable_logging = $this->config['snp_throttle_enable_logging'] ?? 0;
        $enable_throttle = $this->config['snp_throttle_enable_throttle'] ?? 0;
        $cfg = [
            'count' => 25,
            'interval' => 10,
            'ban_duration' => 30,
            'enable_master' => $enable_master,
            'enable_logging' => $enable_logging,
            'enable_throttle' => $enable_throttle,
        ];
        $this->helper->throttleUser($this->userId, $cfg);
    }/*}}}*/
}
