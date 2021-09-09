<?php
namespace jeb\snahp\core\market;

class product_class
{
    protected $db;
    protected $user;
    protected $container;
    protected $sauth;
    public function __construct($db, $tbl)
    {
        $this->db = $db;
        $this->tbl = $tbl;
    }

    public function update_product_class($id, $data)
    {
        if (!$this->get_product_class($id)) {
            return false;
        }
        $update_strn = $this->db->sql_build_array("UPDATE", $data);
        $sql =
            "UPDATE " .
            $this->tbl["mrkt_product_classes"] .
            " SET $update_strn  WHERE id=${id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }

    public function get_product_class($id)
    {
        $sql =
            "SELECT * FROM " .
            $this->tbl["mrkt_product_classes"] .
            " WHERE id=${id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function get_product_classes()
    {
        $sql = "SELECT * FROM " . $this->tbl["mrkt_product_classes"];
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function get_product_class_by_name($product_class_name)
    {
        $product_class_name = $this->db->sql_escape($product_class_name);
        $where = "name='${product_class_name}'";
        $sql =
            "SELECT * FROM " .
            $this->tbl["mrkt_product_classes"] .
            " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }
}
