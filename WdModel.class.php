<?php namespace WangDong;
/**
 * 基模型，增加了关联的处理
 * User: dongdong
 * Date: 2015/6/1
 * Time: 12:16
 */
use Think\Model;

class WdModel extends Model {

    protected $with = array(); //存储预加载

    protected $relation = array('hasOne','belongsTo','hasMany','manyToMany'); //允许的关联关系

    protected $excepts = array(); //存储排除不查的字段

    protected $orginal = array(); //存放模型原始数据

    private $hash = array(); //用于临时存放多对多关系的关联信息

    protected $timestamp = true;

    private $node_name; //当前关系节点的名称


    /**
     * 预加载方法
     * @return $this
     */
    public function with(){
        foreach(func_get_args() as $arg){
            if(is_string($arg)){
                $arg = array($arg,null);
            }
            if(is_array($arg)){
                $arg = array($arg[0],is_callable($arg[1]) ? $arg[1] : null);
            }
            if(!isset($this->with[$arg[0]])){
                $this->with[$arg[0]] = $arg;
            }
        }
        return $this;
    }

    /**
     * 延时加载关系模型数据
     * @param $name
     * @param null $callback
     * @return null
     */
    public function has($name,$callback=null){
        $relation = $this->getRelation(array($name,$callback));
        $relation_data = $this->getRelationData($relation,array($this->data));
        if(count($relation_data) == 1){
            return $relation_data[0];
        }
        return $relation_data;
    }

    /**
     * 延时加载快捷方法
     * @param string $name
     * @return mixed|null
     */
    public function __get($name){
        $val = parent::__get($name);
        if(!is_null($val)){
            return $val;
        }
        return $this->has($name);
    }

    /**
     * 获取修改的字段
     * @return array
     */
    public function getDirty(){
        foreach($this->getDbFields() as $key=>$field) {
            if($this->isDirty($field))
            {
                $i[$field] = $this->data[$field];
            }
        }
        return empty($i) ? null : $i;
    }

    public function save($data='',$options=array()){
        if(empty($data)){
            $data = $this->getDirty();
        }
        if($this->timestamp){
            $data['updated_at'] = date('Y-m-i H:i:s');
            $this->data['updated_at'] = $data['updated_at'];
        }
        return parent::save($data,$options);
    }

    /**
     * 检测字段是否修改
     * @param $attr
     * @return bool
     */
    public function isDirty($attr) {
        if(!isset($this->data[$attr])){
            return false;
        }
        return $this->data[$attr] !== $this->orginal[$attr];
    }

    protected function _after_find(&$result,$options){
        if(!empty($result)){
            $this->orginal = $result;
            $resultSet = array($result);
            $this->handleRelation($resultSet);
            $result = $resultSet[0];
        }
    }

    /**
     * 处理关系
     * @param $resultSet
     */
    protected function handleRelation(&$resultSet){
        foreach($this->with as $with){
            $relation = $this->getRelation($with);
            $relation_data = $this->getRelationData($relation,$resultSet);
            $resultSet = $this->result_merge($resultSet,$relation_data,$relation);
        }
    }

    protected function _after_select(&$resultSet,$options){
        if(!empty($resultSet)){
            $this->handleRelation($resultSet);
        }
    }

    /**
     * 合并结果
     * @param $resultSet 原始的结果集
     * @param $relation_data 关联模型的记过集
     * @param $relation 关系
     * @return mixed
     */
    protected function result_merge($resultSet,$relation_data,$relation){
        foreach($resultSet as &$result){
            foreach($relation_data as $val){
                if(in_array($relation['type'],array('hasOne','hasMany'))){
                    if($result[$this->getPk()] == $val[$relation['relation_field']]){
                        if('hasOne' == $relation['type']){
                            $result[$this->node_name] = $val;
                            break;
                        }else{
                            $result[$this->node_name][] = $val;
                        }
                    }
                }elseif('belongsTo' == $relation['type']){
                    if($result[$relation['relation_field']] == $val[$relation['model']->getPk()]){
                        $result[$this->node_name] = $val;
                        break;
                    }
                }elseif('manyToMany' == $relation['type']){
                    $many = $this->hash[$result[$this->getPk()]];
                    if(in_array($val[$relation['model']->getPk()],$many)){
                        $result[$this->node_name][] = $val;
                    }
                }
            }
        }
        return $resultSet;
    }

    /**
     * 获取某一个字段的值
     * @param $resultSet
     * @param $field
     * @return array
     */
    public function getFieldValues($resultSet,$field){
        $tmp = array();
        foreach($resultSet as $result){
            $tmp[] = $result[$field];
        }
        return array_unique($tmp);
    }

    /**
     * 获取关联模型的结果
     * @param $relation
     * @param $resultSet
     * @return null
     */
    protected function getRelationData($relation,$resultSet){
        $model = $relation['model'];
        switch($relation['type']){
            case 'hasOne':
            case 'hasMany':
                $map = array($relation['relation_field']=>array('in',$this->getFieldValues($resultSet,$this->getPk())));
                return $model->where($map)->select();
            case 'belongsTo':
                $map = array($model->getPk()=>array('in',$this->getFieldValues($resultSet,$relation['relation_field'])));
                return $model->where($map)->select();
            case 'manyToMany':
                $relation_model = M($relation['relation_table']);
                $map = array(
                    $relation['relation_field'] => array('in',$this->getFieldValues($resultSet,$this->getPk()))
                );
                $data = $relation_model->where($map)->select();

                foreach($data as $val){
                    $this->hash[$val[$relation['relation_field']]][] = $val[$relation['relation_field2']];
                    $ids[] = $val[$relation['relation_field2']];
                }

                $map = array(
                    $model->getPk()=>array('in',$ids)
                );
                return $model->where($map)->select();
        }
        return null;
    }

    /**
     * 获取关系
     * @param $name
     * @return mixed|null
     */
    private function getRelation($with){
        list($name,$callback) = $with;
        if(method_exists($this,$name)) {
            $this->node_name = $name;
            $relation = call_user_func(array($this,$name));
            if(is_callable($callback)){
                call_user_func($callback,$relation['model']);
            }
            return $relation;
        }
        return null;
    }

    /**
     * 扩展CALL，添加关联方法
     * @param string $method
     * @param array $args
     * @return array|mixed
     */
    public function __call($method,$args) {
        if(in_array($method,$this->relation)){
            return array(
                'model' => D($args[0]),
                'type' => $method,
                'relation_field' => $args[1],
                'relation_table' => $args[2],
                'relation_field2' => $args[3]
            );
        }
        return parent::__call($method,$args);
    }

    /**
     * 快捷方法
     * @param $field
     * @param $values
     * @return mixed
     */
    public function whereIn($field,$values){
        return $this->where(array($field=>array('in',$values)));
    }

    /**
     * 在查询的时候排除某个字段不查
     * @return $this
     */
    public function except(){
        foreach(func_get_args() as $field){
            if(is_string($field) && !in_array($field,$this->excepts)){
                if(strpos($field,',') !== false){
                    $field = explode(',',$field);
                }else{
                    $field = array($field);
                }
            }
            if(is_array($field)){
                $this->excepts = array_merge($field,$this->excepts);
            }
        }
        return $this;
    }

    /**
     * 添加过滤规则，让其支持except
     * @param $options
     */
    protected function _options_filter(&$options) {
        if(count($this->excepts) > 0){
            if(!isset($options['field'])){
                $options['field'] = $this->getDbFields();
            }
            foreach($options['field'] as $i=>$field){
                if(in_array($field,$this->excepts)){
                    unset($options['field'][$i]);
                }
            }
        }
    }

    /**
     * 字段别名映射
     * @param $field
     * @return mixed
     */
    public function map($field){
        if(isset($this->_map[$field])){
            $field = $this->_map[$field];
        }
        return $field;
    }

    /**
     * 可以用来处理别名的WHERE
     * @param array $conditions
     * @return mixed
     */
    public function mapWhere(array $conditions){
        if(is_array($conditions)){
            foreach($conditions as $key=>$val){
                if(array_key_exists($key,$this->_map)){
                    array_unshift($conditions,array($this->map($key)=>$val));
                    unset($conditions[$key]);
                }
            }
        }
        return $this->where($conditions,null);
    }

    public function add($data='',$options=array(),$replace=false) {
        if(empty($data)){
            $data = $this->data;
        }
        if($this->timestamp){
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->data['created_at'] = $data['created_at'];
            $this->data['updated_at'] = $data['updated_at'];
        }
        return parent::add($data,$options,$replace);
    }

    /**
     * 分页处理
     * @param $totalRows
     * @param int $listRows
     * @param array $parameter
     * @return PaginateService
    */
    public function paginate($listRows=20,$totalRows=0, $parameter = array()){
        if($totalRows <= 0){
            $t = clone $this;
            $totalRows = $t->count();
        }
        if($totalRows <= 0){
            return null;
        }
        $paginate = new Paginate($totalRows,$listRows,$parameter);
        $resultSet = $this->limit($paginate->firstRow.','.$paginate->rows)->select();
        $paginate->setData($resultSet);
        return $paginate;
    }
} 