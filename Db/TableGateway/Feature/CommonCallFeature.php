<?php

namespace Gzfextra\Db\TableGateway\Feature;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\RowGateway\RowGatewayInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\Feature\AbstractFeature;
use Zend\Paginator\Adapter\Callback;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;


/**
 * Class CommonCallFeature
 *
 * @author  moln.xie@gmail.com
 *
 * @method \Zend\Db\Adapter\Adapter getAdapter
 */
class CommonCallFeature extends AbstractFeature
{
    protected $primary = array();

    public function __construct($primary)
    {
        $this->primary = (array)$primary;
    }

    public function getPrimary()
    {
        return $this->primary;
    }

    public function postInitialize()
    {
        $this->sql     = $this->tableGateway->sql;
        $this->table   = $this->tableGateway->getTable();
        $this->adapter = $this->tableGateway->getAdapter();
    }

    /**
     *
     * @param $id
     *
     * @return array|\ArrayObject|null
     */
    public function find($id)
    {
        $where = array();
        foreach ($this->primary as $key => $primary) {
            $where[$primary] = func_get_arg($key);
        }

        return $this->tableGateway->select($where)->current();
    }


    /**
     * Fetch Paginator
     *
     * @param DbSelect|Where|\Closure|string|array $where
     *
     * @return Paginator
     */
    public function fetchPaginator($where = null)
    {
        if ($where instanceof DbSelect) {
            $adapter = $where;
        } else {
            $select = $this->sql->select();

            if ($where instanceof \Closure) {
                $where($select);
            } elseif ($where !== null) {
                $select->where($where);
            }

            if (count($select->getRawState(Select::GROUP))) {
                $adapter = new DbSelect($select, $this->getAdapter());
            } else {
                $count   = null;
                $adapter = new Callback(
                    function ($offset, $itemCountPerPage) use ($select) {
                        $select->offset($offset);
                        $select->limit($itemCountPerPage);

                        $statement = $this->sql->prepareStatementForSqlObject($select);
                        $result    = $statement->execute();

                        $resultSet = new ResultSet();
                        $resultSet->initialize($result);
                        return $resultSet;
                    }, function () use ($select, &$count) {
                        if ($count === null) {
                            $select = clone $select;
                            $select->columns(array('Gzfextra_Db_Count' => new Expression('count(1)')));
                            $result = $this->sql->prepareStatementForSqlObject($select)->execute()->current();
                            $count  = $result['Gzfextra_Db_Count'];
                        }
                        return $count;
                    }
                );
            }
        }

        return new Paginator($adapter);
    }


    /**
     * Fetch Count
     *
     * @param array|Where $where
     *
     * @return int
     */
    public function fetchCount($where = null)
    {
        $select = $this->sql->select();

        if ($where instanceof \Closure) {
            $where($select);
        } elseif ($where !== null) {
            $select->where($where);
        }

        $select->columns(array('Gzfextra_Db_Count' => new Expression('count(1)')));
        $result = $this->sql->prepareStatementForSqlObject($select)->execute()->current();
        return $result['Gzfextra_Db_Count'];
    }


    /**
     * Delete row by Primary.
     *
     * @param $key
     * @return int
     */
    public function deletePrimary($key)
    {
        return $this->tableGateway->delete(array(current($this->primary) => $key));
    }


    /**
     * @param array $row
     *
     * @throws \RuntimeException
     * @return \Zend\Db\RowGateway\RowGateway;
     */
    public function create(array $row = null)
    {
        $result = clone $this->tableGateway->getResultSetPrototype()->getArrayObjectPrototype();
        if (!$result instanceof RowGatewayInterface) {
            throw new \RuntimeException('ArrayObject Prototype is not instanceof RowGatewayInterface');
        }
        $row && $result->populate($row + $result->toArray(), isset($row[current($this->getPrimary())]));
        return $result;
    }


    /**
     * @param $data
     * @return int
     * @throws \RuntimeException
     */
    public function save($data)
    {
        $insert = false;
        $where  = array();

        $temp = $this->columns ? array_intersect_key($data, array_flip($this->columns)) : $data;
        if ($temp instanceof \ArrayObject) {
            $temp = $temp->getArrayCopy();
        } else if (method_exists($temp, 'toArray')) {
            $temp = $temp->toArray();
        }

        if (empty($this->primary)) {
            throw new \RuntimeException('Empty primary, can\'t use save() method.');
        }

        foreach ($this->primary as $primary) {
            if (empty($temp[$primary])) {
                $insert = true;
                if (isset($temp[$primary])) unset($temp[$primary]);
                break;
            }
            $where[$primary] = $temp[$primary];
            unset($temp[$primary]);
        }

        if ($insert) {
            $result = $this->tableGateway->insert($temp);

            if (count($this->primary) == 1 && $id = $this->tableGateway->getLastInsertValue()) {
                $data[$primary] = $this->tableGateway->getLastInsertValue();
            }
        } else {
            $result = $this->tableGateway->update((array)$temp, $where);
        }
        return $result;
    }

    public function getMagicMethodSpecifications()
    {
        return array(
            'create',
            'deletePrimary',
            'fetchCount',
            'fetchPaginator',
            'find',
            'save',
        );
    }
}