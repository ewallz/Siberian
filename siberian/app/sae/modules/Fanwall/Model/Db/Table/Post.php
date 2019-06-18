<?php

namespace Fanwall\Model\Db\Table;

use Fanwall\Model\Post as ModelPost;
use Core_Model_Db_Table as DbTable;
use Zend_Db_Expr as DbExpr;

/**
 * Class Post
 * @package Fanwall\Model\Db\Table
 */
class Post extends DbTable
{

    /**
     * @var string
     */
    protected $_name = "fanwall_post";
    /**
     * @var string
     */
    protected $_primary = "post_id";

    /**
     * @param array $values
     * @param null $order
     * @param array $params
     * @return ModelPost[]
     * @throws \Zend_Exception
     */
    public function findAllWithCustomer($values = [], $order = null, $params = [])
    {
        $select = $this->_db
            ->select()
            ->from("fanwall_post")
            ->join(
                "customer",
                "customer.customer_id = fanwall_post.customer_id",
                [
                    "firstname",
                    "lastname",
                    "nickname",
                    "author_image" => new DbExpr("customer.image"),
                ]);

        foreach ($values as $condition => $value) {
            $select->where($condition, $value);
        }

        if ($order !== null) {
            $select->order($order);
        }

        if (array_key_exists("limit", $params) &&
            array_key_exists("offset", $params)) {
            $select->limit($params["limit"], $params["offset"]);
        }

        return $this->toModelClass($this->_db->fetchAll($select));
    }

    /** ========================= DEPRECATED AFTER THIS LINE ======================== */

    /**
     * @param $valueId
     * @return \Zend_Db_Table_Rowset_Abstract
     */
    public function findByPos($valueId)
    {

        $select = $this->_prepareSelect($valueId);
        $select->order("created_at DESC");

        return $this->fetchAll($select);
    }

    /**
     * @param $valueId
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    public function findLast($valueId)
    {
        $select = $this->_prepareSelect($valueId);
        $select
            ->where("is_visible = 1")
            ->order("c.created_at DESC")
            ->limit(1);

        return $this->fetchRow($select);
    }

    /**
     * @param $valueId
     * @return \Zend_Db_Table_Rowset_Abstract
     */
    public function findLastest($valueId)
    {
        $select = $this->_prepareSelect($valueId);
        $select
            ->where("is_visible = 1")
            ->order("c.created_at DESC")
            ->limit(10);
        return $this->fetchAll($select);
    }

    /**
     * @param $valueId
     * @return \Zend_Db_Table_Rowset_Abstract
     */
    public function findAllWithPhoto($valueId)
    {
        $select = $this->_prepareSelect($valueId);
        $select
            ->where("is_visible = 1")
            ->where("image IS NOT NULL")
            ->order("c.created_at DESC")
            ->limit(10);
        return $this->fetchAll($select);
    }

    /**
     * @param $valueId
     * @param $offset
     * @return \Zend_Db_Table_Rowset_Abstract
     */
    public function findAllWithLocation($valueId, $offset)
    {
        $select = $this->_prepareSelect($valueId);
        $select
            ->where("is_visible = 1")
            ->where("latitude IS NOT NULL")
            ->where("longitude IS NOT NULL")
            ->order("c.created_at DESC")
            ->limit(ModelPost::DISPLAYED_PER_PAGE, $offset);
        return $this->fetchAll($select);
    }

    /**
     * @param $valueId
     * @return \Zend_Db_Table_Rowset_Abstract
     */
    public function findAllWithLocationAndPhoto($valueId)
    {
        $select = $this->_prepareSelect($valueId);
        $select
            ->where("is_visible = 1")
            ->where("image IS NOT NULL")
            ->where("latitude IS NOT NULL")
            ->where("longitude IS NOT NULL")
            ->order("c.created_at DESC")
            ->limit(10);
        return $this->fetchAll($select);
    }

    /**
     * @param $valueId
     * @param $start
     * @param $count
     * @return \Zend_Db_Table_Rowset_Abstract
     */
    public function pullMore($valueId, $start, $count)
    {
        $select = $this->_prepareSelect($valueId);
        $select
            ->where("is_visible = 1")
            ->where("post_id < ?", $start)
            ->order("c.created_at DESC")
            ->limit($count);
        return $this->fetchAll($select);
    }

    /**
     * @param $valueId
     * @return \Zend_Db_Select
     */
    protected function _prepareSelect($valueId)
    {

        $select = $this->select()
            ->from(["c" => $this->_name])
            ->where($this->_db->quoteInto("c.value_id = ?", $valueId));

        return $select;

    }

}