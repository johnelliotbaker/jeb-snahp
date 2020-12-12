<?php

namespace jeb\snahp\Apps\Xmas\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/utility.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\JsonField;

use \R as R;

class Vote extends Model
{
    const TABLE_NAME = 'phpbb_snahp_xmas_vote';

    protected $fields;
    protected $requiredFields = ['tile'];

    public function __construct()
    {
        parent::__construct();
        // TODO:: REMOVE
        R::freeze(false);
        $this->fields = [
            // 'period' => new IntegerField(),
            'created' => new IntegerField(['default' => time()]),
            'user' => new IntegerField(),
            'tile' => new IntegerField(),
        ];
    }

    public function simulate($period)/*{{{*/
    {
        $this->wipe();
        clearVotes();
        $schedule = getXmasConfig('schedule', 0);
        $start    = $schedule['start'];
        $duration = $schedule['duration'];
        $end      = $schedule['end'];
        $division = $schedule['division'];
        if (!$period) {
            $period = $division;
        }
        $end      = $start + $duration * $period / $division;
        $users = 100;
        $interval = (int) $duration / $division;
        foreach (range($start, $end-$interval, $interval) as $time) {
            $availableVotes = getAvailableVotes($time);
            $timeIndex = getTimeIndex($time, $start, $duration, $division);
            print_r(implode(' ', $availableVotes));
            print_r("<br>");
            print_r($timeIndex);
            print_r("<br><br>");
            foreach (range(60, $users+60-1) as $user) {
                $data = [
                'user' => $user,
                'tile' => choice($availableVotes),
                'period' => $timeIndex,
                'created' => $time,
                ];
                $this->create($data);
            }
        }
    }/*}}}*/
}
