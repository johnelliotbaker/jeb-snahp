<?php
namespace jeb\snahp\Apps\Core\Db;

class QuerySet implements \IteratorAggregate, \Countable, \ArrayAccess
{
    public function __construct($db, $sqlArray)
    {
        $this->db = $db;
        $this->sqlArray = $sqlArray;
    }

    public function setSqlArray($sqlArray)
    {
        $this->sqlArray = $sqlArray;
    }

    public function slice($offset, $length, $many=true, $cacheTimeout=0)
    {
        $sql = $this->buildSql();
        $result = $this->db->sql_query_limit($sql, $length, $offset, $cacheTimeout);
        if ($many) {
            $data = $this->db->sql_fetchrowset($result);
        } else {
            $data = $this->db->sql_fetchrow($result);
        }
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function buildSql()
    {
        return $this->db->sql_build_query('SELECT', $this->sqlArray);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this);
    }

    public function cloneSqlArray()
    {
        return unserialize(serialize($this->sqlArray));
    }

    public function offsetExists($offset)
    {
        return !!$this->offsetGet($offset);
    }

    public function offsetGet($offset)
    {
        return $this->slice($offset, 1, false);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    public function count()
    {
        $sqlCountArray = $this->cloneSqlArray();
        $sqlCountArray['SELECT'] = 'COUNT(*) as total';
        $sql = $this->db->sql_build_query('SELECT', $sqlCountArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row['total'];
    }

    public function filterInSet($column, $set, $negate=false)
    {
        if (!$set) {
            return;
        }
        $where = $this->sqlArray['WHERE'] ?? '1=1';
        $where = [$where];
        $where[] = $this->db->sql_in_set($column, $set, $negate);
        $where = implode($where, ' AND ');
        $this->sqlArray['WHERE'] = $where;
    }
}
