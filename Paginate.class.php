<?php
/**
 * Created by PhpStorm.
 * User: dongdong
 * Date: 2015/6/30
 * Time: 10:00
 */

namespace WangDong;


use Iterator;
use Think\View;

class Paginate implements Iterator {

    protected $data = array();

    protected $parameter = array();

    public $total = 0;

    public $rows = 20;
    public $firstRow; // 起始行数

    public $nowPage = 1;

    protected $view;

    public $p = 'p';

    public function __construct($totalRows, $listRows=20, $parameter = array()) {
        C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */
        $this->total  = $totalRows; //设置总记录数
        $this->rows   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->maxPage =  ceil($this->total/$this->rows);
        //$this->nowPage > $this->maxPage and $this->nowPage = $this->maxPage;

        $this->firstRow   = $this->rows * ($this->nowPage - 1);
        $this->view = new View();
    }

    public function show($templateFile='paginate',$content='',$prefix=''){
        $this->view->assign(array(
            'total' => $this->total,
            'rows' => $this->rows,
            'nowPage' => $this->nowPage,
            'parameter' => $this->parameter,
            'maxPage' => $this->maxPage,
            'p' => $this->p
        ));
        return $this->view->fetch($templateFile,$content,$prefix);
    }

    public function setData($data){
        $this->data = $data;
        return $this;
    }

    public function append($parameter){
        $this->parameter = array_merge($this->parameter,$parameter);
        return $this;
    }

    public function toArray(){
        return $this->data;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->data);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->current());
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $current = $this->current();
        return !empty($current);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->data);
    }
}