<?php

namespace jeb\snahp\Apps\UserFlair;

const FIELDS = [
    "ADOBE_INFINITY" => ["title", "imgURL", "link"],
    "ANIME_PACK" => ["title", "imgURL", "link"],
    "ATTENBOROUGH" => ["title", "imgURL", "link"],
    "AUDIO_EXPERT" => ["title", "imgURL"],
    "DJ" => ["title", "imgURL"],
    "DRUNKEN_MASTER" => ["title", "imgURL", "link"],
    "ENCODER" => ["tag"],
    "FORMULA_ONE" => ["title", "imgURL", "link"],
    "GAME_MACHINE" => ["title", "imgURL", "link"],
    "GUNDAM" => ["title", "imgURL", "link"],
    "JAMES_BOND" => ["title", "imgURL", "link"],
    "KOREAN_PACK" => ["title", "imgURL", "link"],
    "MARVEL" => ["title", "imgURL", "link"],
    "ONE_PIECE" => ["title", "imgURL", "link"],
    "THE_ORACLE" => ["title", "imgURL", "link"],
    "TIDAL" => ["title", "imgURL", "link"],
    "TREKKI" => ["title", "imgURL", "link"],
    "UPDATER" => ["title", "imgURL", "link"],
    "UPSCALE" => ["title", "imgURL", "link"],
    "ENCODER_TWO" => ["tag", "tagTwo", "imgURL"],
    "WWE" => ["title", "imgURL", "link"],
    //     // "CROUCHING_TIGER" =>  ['title', 'imgURL', 'link'],
    //     // "HIDDEN_DRAGON" =>  ['title', 'imgURL'],
];

// const FIELDS = [
//     "ADOBE_INFINITY" =>  [],
//     "ANIME_PACK" =>  [],
//     "ATTENBOROUGH" =>  [],
//     "AUDIO_EXPERT" =>  [],
//     "DJ" =>  [],
//     "DRUNKEN_MASTER" =>  [],
//     "ENCODER" =>  [],
//     "FORMULA_ONE" =>  [],
//     "GAME_MACHINE" =>  [],
//     "GUNDAM" =>  [],
//     "JAMES_BOND" =>  [],
//     "MARVEL" =>  [],
//     "ONE_PIECE" =>  [],
//     "THE_ORACLE" =>  [],
//     "TIDAL" =>  [],
//     "TREKKI" =>  [],
//     "UPDATER" =>  [],
//     "UPSCALE" =>  [],
//     "ENCODER_TWO" =>  [],
//     "WWE" =>  [],
//     // "CROUCHING_TIGER" =>  [],
//     // "HIDDEN_DRAGON" =>  [],
// ];

class TypeResetter
{
    public function __construct($itemdata, $typeModel)
    {
        $this->itemdata = $itemdata;
        $this->typeModel = $typeModel;
    }

    public function reset()
    {
        \R::freeze(false);
        $this->typeModel->wipe();
        $itemdata = $this->itemdata;
        $names = $this->getNames();
        foreach ($names as $name) {
            $inputdata = [
                "name" => strtoupper($name),
                "data" => json_encode($this->getData($name)),
            ];
            $this->typeModel->create($inputdata);
        }
    }

    // public function renameToNewStyle($data)
    // {
    //     // prefer camel case to snake case
    //     $renameMap = [
    //         'img_url' => 'imgURL',
    //         'url' => 'link',
    //     ];
    //     foreach ($data as $key => $value) {
    //         if (array_key_exists($key, $renameMap)) {
    //             [$oldname, $newname] = [$key, $renameMap[$key]];
    //             $data[$newname] = $data[$oldname];
    //             unset($data[$oldname]);
    //         }
    //     }
    //     return $data;
    // }

    public function getNames()
    {
        return array_keys($this->itemdata);
    }

    public function getData($type)
    {
        $this->styleName = "prosilver";
        $data = $this->itemdata[$type];
        $data = overrideTypeData($data);
        $data = renameToNewStyle($data);
        $data = removeFields($data, ["fields", "template", "override"]);
        $data["fields"] = [
            "required" => FIELDS[strtoupper($type)],
        ];
        return $data;
    }
}

class FlairResetter
{
    public function __construct($userdata, $itemdata, $flairModel)
    {
        $this->itemdata = $itemdata;
        $this->userdata = $userdata;
        $this->flairModel = $flairModel;
    }

    public function reset()
    {
        \R::freeze(false);
        $this->flairModel->wipe();
        $userdatas = $this->userdata;
        $itemdata = $this->itemdata;
        foreach ($userdatas as $userId => $userdata) {
            foreach ($userdata["queue"] as $name) {
                $fields = [];
                $defaultData = $itemdata[$name]["data"]
                    ? overrideTypeData($itemdata[$name])["data"]
                    : [];
                $userSpecificData = $userdata["data"][$name]
                    ? $userdata["data"][$name]
                    : [];
                $flair = array_merge($defaultData, $userSpecificData);
                $flair = renameToNewStyle($flair);
                $inputdata = [
                    "type" => strtoupper($name),
                    "user" => $userId,
                    "data" => json_encode($flair),
                ];
                $this->flairModel->create($inputdata);
            }
        }
        $this->flairModel->addIndex("user", "user");
    }
}

function overrideTypeData($typedata)
{
    $data = &$typedata["data"];
    if (isset($typedata["override"]["style_name"])) {
        $override = $typedata["override"]["style_name"];
        foreach ($override as $styleName => $value) {
            $imgURL[$styleName] = $value["img_url"];
        }
        $data["img_url"] = $imgURL;
    }
    return $typedata;
}

function renameToNewStyle($data)
{
    // prefer camel case to snake case
    $renameMap = [
        "img_url" => "imgURL",
        "url" => "link",
        "tagname" => "tag",
        "group_title" => "encoderGroupName",
        "img_class" => "imgClass",
        "internal" => "encoderGroupTag",
    ];
    foreach ($data as $key => $value) {
        if (array_key_exists($key, $renameMap)) {
            [$oldname, $newname] = [$key, $renameMap[$key]];
            $data[$newname] = $data[$oldname];
            unset($data[$oldname]);
        }
    }
    return $data;
}
