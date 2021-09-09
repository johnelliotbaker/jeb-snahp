<?php
namespace jeb\snahp\Apps\Emoticon;

class EmoticonHelper
{
    public function __construct(
        $db,
        $template,
        $tbl,
        $definition,
        $sauth
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->template = $template;
        $this->definition = $definition;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->userId = $sauth->userId;
    }

    public function embedEmoticon($userId)
    {
        // /var/www/forum/ext/jeb/snahp/styles/all/template/emotes/base.html
        // <span id="rx_emoticon" {EMOTICON_PROPS}></span>
        $data = $this->getEmoticon($userId);
        $attributeData = convertArrayToHTMLAttribute($data);
        $tplData = ['EMOTICON_PROPS' => "data-data=\"$attributeData\""];
        $this->template->assign_vars($tplData);
    }

    public function getEmoticon($userId)
    {
        $data = [];
        $groupId = $this->sauth->user->data['group_id'];
        $groupData = $this->selectGroup($groupId);
        $allowedTypes = unserialize($groupData['snp_emo_allowed_types']);
        foreach ($this->definition as $key=>$entry) {
            if (in_array($entry['type'], $allowedTypes)) {
                $data[] = [ 'name' => $key ] + $entry;
            }
        }
        return $data;
    }


    public function selectGroup($gid)
    {
        $sql = 'SELECT * FROM ' . GROUPS_TABLE . " WHERE group_id=$gid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }
}
