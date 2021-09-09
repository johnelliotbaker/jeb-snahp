<?php
namespace jeb\snahp\Apps\Snowfall;

class SnowfallHelper
{
    const CONFIG_NAME = "snp_snowfall_data";
    const SNOWFALL_DURATION = 1800; // 30 minutes
    const SNOWFALL_PRODUCT_CLASS_NAME = "snowfall";

    protected $configText;
    protected $template;
    protected $sauth;
    protected $productClass;
    protected $userInventory;
    public function __construct(
        $db,
        $configText,
        $template,
        $sauth,
        $productClass,
        $userInventory
    ) {
        $this->db = $db;
        $this->configText = $configText;
        $this->template = $template;
        $this->sauth = $sauth;
        $this->productClass = $productClass;
        $this->userInventory = $userInventory;
        $this->userId = (int) $sauth->userId;
        $this->data = $this->getData();
        $this->isOP = (int) $this->data["user"]["id"] === $this->userId;
    }

    public function reset()
    {
        $this->setData([]);
        return $success;
    }

    public function changeColor($color)
    {
        if (!$this->isOP) {
            throw new \Exception("You are not worthy.");
        }
        $success = preg_match("/[0-9a-f]{6}/", $color);
        if ($success) {
            $data = $this->data;
            $data["color"] = "#" . $color;
            $this->setData($data);
        }
        return $success;
    }

    public function changeText($text)
    {
        if (!$this->isOP) {
            throw new \Exception("You are not worthy.");
        }
        $data = $this->data;
        $data["text"] = $text;
        $this->setData($data);
        return true;
    }

    public function activate()
    {
        if ($this->isActive()) {
            throw new \Exception("Snowfall is active already.", 400);
        }
        if (!$this->canActivate()) {
            // Also checks to see if product class is available
            throw new \Exception(
                "You do not have the power to change weather.",
                400
            );
        }
        $user = $this->getUserData($this->userId);
        $data = [
            "user" => [
                "id" => $user["user_id"],
                "color" => $user["user_colour"],
                "username" => $user["username"],
            ],
            "text" => "",
            "color" => "#f5f5f5",
            "start" => time(),
            "end" => time() + self::SNOWFALL_DURATION,
        ];
        $this->setData($data);
        $pcdata = $this->getProductClassData();
        $pcid = (int) $pcdata["id"];
        $this->userInventory->doRemoveItemWithLogging(
            $pcid,
            1,
            $this->userId,
            "Activated Snow Fall"
        );
    }

    public function setTemplateVars()
    {
        $data = $this->data;
        $data["isOP"] = $this->isOP;
        $attributeData = convertArrayToHTMLAttribute($data);
        if ($this->isActive()) {
            $tplData = ["SNOWFALL_PROPS" => "data-data=\"$attributeData\""];
            if (
                $this->sauth->user_belongs_to_groupset(
                    (int) $data["user"]["id"],
                    "Staff"
                )
            ) {
                $data["fromStaff"] = true;
            }
            if (isset($data["text"])) {
                $tplData["SNOWFALL_DATA"] = $data;
            }
            $this->template->assign_vars($tplData);
        } else {
            $this->template->assign_vars([
                "SNOWFALL_CAN_ACTIVATE" => $this->canActivate(),
            ]);
        }
    }

    public function setData($data)
    {
        return $this->configText->set(self::CONFIG_NAME, serialize($data));
    }

    public function getData()
    {
        return unserialize($this->configText->get(self::CONFIG_NAME));
    }

    public function getUserData($userId)
    {
        $userId = (int) $userId;
        $sql = "SELECT user_id, user_colour, username FROM phpbb_users WHERE user_id=${userId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function canActivate()
    {
        if ($this->isActive()) {
            return false;
        }
        if ($this->sauth->is_dev()) {
            return true;
        }
        if ($this->hasSnowfall()) {
            return true;
        }
    }

    public function hasSnowfall()
    {
        if ($productClassData = $this->getProductClassData()) {
            $pcid = (int) $productClassData["id"];
            $invData = $this->userInventory->get_single_inventory(
                "product_class_id=${pcid}"
            );
            if ($invData) {
                return (int) $invData["quantity"];
            }
        }
        return false;
    }

    public function isActive()
    {
        $data = $this->data;
        if (!isset($data["start"]) || !isset($data["end"])) {
            return; // The data hasn't been set.
        }
        [$start, $end] = [$data["start"], $data["end"]];
        $time = time();
        return $time >= $start && $time <= $end;
    }

    public function getProductClassData()
    {
        if (!$this->productClassData) {
            $product_class_name = self::SNOWFALL_PRODUCT_CLASS_NAME;
            $this->productClassData = $this->productClass->get_product_class_by_name(
                $product_class_name
            );
        }
        return $this->productClassData;
    }
}
