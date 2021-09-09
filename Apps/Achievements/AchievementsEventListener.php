<?php
namespace jeb\snahp\Apps\Achievements;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AchievementsEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $helper;
    public function __construct(
        $user,
        $config,
        $sauth,
        $helper
    ) {
        $this->user = $user;
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->user_id = $this->user->data['user_id'];
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.viewtopic_cache_user_data' => [
                ['setUserAchievementData', 1],
            ],
            'core.viewtopic_modify_post_row' => [
                ['setPosterAchievements', 1],
            ],
        ];
    }

    public function setPosterAchievements($event)
    {
        $userPosterData = $event['user_poster_data'];
        $postRow = $event['post_row'];
        $postRow['ACHIEVEMENTS'] = $userPosterData['achievements'];
        $event['post_row'] = $postRow;
    }

    public function setUserAchievementData($event)
    {
        $posterId = (int) $event['poster_id'];
        $userCacheData = $event['user_cache_data'];
        $userCacheData['achievements'] = $this->helper->getAchievements($posterId);
        $event['user_cache_data'] = $userCacheData;
    }
}
