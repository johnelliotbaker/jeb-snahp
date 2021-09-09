<?php
namespace jeb\snahp\Apps\ReportTool;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportToolEventListener implements EventSubscriberInterface
{
    public function __construct($user, $helper)
    {
        $this->user = $user;
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['embedReportTime', 1],
            ],
        ];
    }

    public function embedReportTime($event)
    {
        $row = $event['row'];
        if ($row['post_reported']) {
            $reportTime = $this->helper->getReportedTime($row['post_id']);
            if ($reportTime !== null) {
                $reportTimeString = $this->user->format_date($reportTime);
                $postRow = $event['post_row'];
                $postRow['S_REPORTED_TIME'] = $reportTimeString;
                $event['post_row'] = $postRow;
            }
        }
    }
}
