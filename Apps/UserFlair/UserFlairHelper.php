<?php
namespace jeb\snahp\Apps\UserFlair;

const EXCLUDED_FIELDS = [
    'fields', 'template', 'template_type', 'description',
];

class UserFlairHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;

    protected $db;/*{{{*/
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $user,
        $template,
        $tbl,
        $sauth,
        $typeModel,
        $flairModel
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->tbl = $tbl;
        $this->rxnTbl = $tbl['UserFlair'];
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
        $this->typeModel = $typeModel;
        $this->flairModel = $flairModel;
        $this->styleName = 'prosilver';
        $this->styleType = 'light';
        $this->cache = [];
        $this->setupStyleInfo();
    }/*}}}*/

    public function getFlairTypes()/*{{{*/
    {
        if (!isset($this->flairTypes)) {
            foreach ($this->typeModel->getQueryset() as $type) {
                $this->flairTypes[$type->name] = $type;
            }
        }
        return $this->flairTypes ? $this->flairTypes : [];
    }/*}}}*/

    public function getData($bean)/*{{{*/
    {
        return json_decode(($bean->data), true);
    }/*}}}*/

    public function getTypeData($bean)/*{{{*/
    {
        // Not sure why flair type has two levels of "data"
        $data = $this->getData($bean);
        if (isset($data['data'])) {
            $datadata = $data['data'];
            unset($data['data']);
        }
        return array_merge($data, $datadata);
    }/*}}}*/

    public function wipeFlairTable()/*{{{*/
    {
        $this->flairModel->wipe();
    }/*}}}*/

    public function wipeTypeTable()/*{{{*/
    {
        $this->typeModel->wipe();
    }/*}}}*/

    public function makeHtml($user)/*{{{*/
    {
        $userId = (int) $user['id'];
        if (isset($this->cache[$userId])) {
            return $this->cache[$userId];
        }
        $flairs = $this->flairModel->getQueryset('user=?', [$userId]);
        $res = [];
        $flairTypes = $this->getFlairTypes();
        foreach ($flairs as $flair) {
            $flairType = $flair->type;
            if (!array_key_exists($flairType, $flairTypes)) {
                // Skip if user specific data is an unknown type
                continue;
            }
            $flairData = $this->getData($flair);
            $flairTypeData = $this->getTypeData($flairTypes[$flairType]);
            $flairData = array_merge($flairTypeData, $flairData);

            $fields = $flairData['fields']['required'];
            if (!hasRequiredFields($flairData, $fields)) {
                continue;
            }
            $flairData = removeFields($flairData, EXCLUDED_FIELDS);
            $flairData = $this->chooseStyleData($flairData);
            $flairData = chooseRandomData($flairData);
            $res['results'][$flairType] = $flairData;
        }
        if (!$res) {
            return '';
        }
        $res['user'] = $user;
        $res = json_encode($res);
        // Embedding data as data attribute. Escape single quotes.
        $res = str_replace("'", "&#39;", $res);
        // $res = htmlspecialchars($res, ENT_QUOTES);
        $html = "<div class='rx_user_flair' data-data='${res}'></div>";
        $this->cache[$userId] = $html;
        return $html;
    }/*}}}*/

    public function chooseStyleData($data)/*{{{*/
    {
        // Every style is guaranteed to include this style
        $FIXED_STYLE_NAME = 'prosilver';
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[$FIXED_STYLE_NAME])) {
                $data[$key] = $value[$this->styleName];
            }
        }
        return $data;
    }/*}}}*/

    public function setupStyleInfo()/*{{{*/
    {
        $userStyle = $this->user->data['user_style'];
        $sql = 'SELECT style_name FROM ' . STYLES_TABLE . '
            WHERE style_id=' . $userStyle;
        $result = $this->db->sql_query($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $styleName = $row['style_name'];
        switch ($styleName) {
        case 'Acieeed!':
            $this->styleType = 'dark';
            $this->styleName = 'acieeed!';
            break;
        case 'Basic':
            $this->styleType = 'light';
            $this->styleName = 'basic';
            break;
        case 'Digi Orange':
            $this->styleType = 'dark';
            $this->styleName = 'digi_orange';
            break;
        case 'Hexagon':
            $this->styleType = 'dark';
            $this->styleName = 'hexagon';
            break;
        }
    }/*}}}*/
}

function hasRequiredFields($data, $fields)/*{{{*/
{
    if (!$fields) {
        return true;
    }
    foreach ($fields as $field) {
        if (!isset($data[$field])) {
            return false;
        }
    }
    return true;
}/*}}}*/

function chooseRandomData($data)/*{{{*/
{
    // If field is array, choose random element
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = $value[array_rand($value)];
        }
    }
    return $data;
}/*}}}*/

function removeFields($data, $fields)/*{{{*/
{
    foreach ($fields as $field) {
        unset($data[$field]);
    }
    return $data;
}/*}}}*/
