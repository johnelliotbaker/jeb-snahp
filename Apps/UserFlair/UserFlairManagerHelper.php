<?php

namespace jeb\snahp\Apps\UserFlair;

use \R as R;

class UserFlairManagerHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;

    protected $db;
    protected $sauth;
    protected $Type;
    protected $Flair;
    public function __construct($db, $sauth, $Type, $Flair)
    {
        $this->db = $db;
        $this->sauth = $sauth;
        $this->Type = $Type;
        $this->Flair = $Flair;
        $this->userId = $sauth->userId;
    }

    public function getFlairs()
    {
        $flairs = $this->Flair->getQueryset("");
        return $flairs;
    }

    public function fixTypedataUnderscores()
    {
        $types = $this->Type->getQueryset("", []);
        foreach ($types as $key => $type) {
            $outerdata = json_decode($type->data);
            if ($val = $outerdata->data->img_url) {
                $outerdata->data->imgURL = $val;
                unset($outerdata->data->img_url);
                $type->data = json_encode($outerdata);
            }
            if ($val = $outerdata->data->img_css) {
                $outerdata->data->imgCSS = $val;
                unset($outerdata->data->img_css);
                $type->data = json_encode($outerdata);
            }
            R::store($type);
        }
    }
}
