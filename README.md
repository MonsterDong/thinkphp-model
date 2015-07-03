ThinkPHP3.2 关系模型
================================
如何使用
----------------------------------------
        将下载的目录放置ThinkPHP/Library目录下即可
关系定义
---------------------------------
###一对一,hasOne
        例如：每个用户都有自己的个人信息
        public function profile(){
            return $this->hasOne('UserProfile','user_id');
        }
###一对一，belongsTo
        例如：每一条回复都属于某一个话题
        public function topic(){
            return $this->belongsTo('Topic','topic_id');
        }
###一对多，hasMany
        例如：一个话题有多条回复
        public function reply(){
            return $this->hasMany('TopicReply','topic_id');
        }
###多对多,manyToMany
        参数说明：1.关联的关系模型、2.关系表中主表的关联字段、3.关系表、4.关系表中关联表的关联字段
        public function test4(){
            return $this->manyToMany('Test4','test1_id','test1_test4','test4_id');
        }
查询
--------------------------------------------------------
###查询一条数据使用find方法
        由于在find方法里面做了一些处理，之后讲到的isDirty和getDirty,
        以及save方法的优化是基于getDirty方法的，
        所以对于一条数据的查询请使用find方法
        D('User')->where(['id'=>1])->find();
###查询多条数据select方法
        D('User')->limit(10)->select();
延时加载
-----------------------------------------------
        和其名字一样，一开并不查询关联数据，只有到你真正需要时才去查询
###基本用法
        $user = D('User');
        $user->where(['id'=>1])->find();
        $user->profile
###高级用法
        高级用法需要借助has方法,例如：用户发表了多个话题，我希望获取最新的10条
        $user = D('User');
        $user->where(['id'=>1])->find();
        $user->has('topic',function($query){
            $query->limit(10)->order('created_at desc');
        });
预载入
-------------------------------------------------
        预载入功能主要是通过with方法来实现的
###基本使用
        D('User')->with('profile')->where(['id'=>1])->find();
###预加载多个关联
        D('User')->with('profile','company')->where(['id'=>1])->find();
###预加载高级用法
        查询前10条用户和用户发表的话题,并且话题状态为2,
        如果要对话题分页可以使用个小技巧,如这里假设只查询每个用户满足条件的10条数据，
        就可以limit(10*10)
        实际情况很少这么用,这里只是为了说明使用方式
        D('User')->with(['topic',function($query){
            $query->where(['status'=>2])->limit(100)->select();
        }])->limit(10)->select();
排除字段不查
-----------------------------------------------
        D('User')->except('password')->where(['id'=>1])->find();
        排除多个字段
        D('User')->except('password,username')->where(['id'=>1])->find();
        或者
        D('User')->except(['password,username'])->where(['id'=>1])->find();
自动处理时间戳
--------------------------------------------------
        默认开启的
        默认约束,创建时间字段名为:created_at,更新时间字段名为:updated_at
        关闭自动处理:protected $timestamp = false;
分页
--------------------------------------------------
###paginate方法
        paginate方法接受3个参数:1.一页显示的行数、2.记录总数、3.扩展参数
        记录总数：当记录总数为空时，自动获取当前查询总数
        扩展参数：当为空时，自动获取所有get参数

        $paginate = D('User')->paginate(10);

        //前端调用
        foreach($paginate as $user){
            echo $user['username']
        }
        //分页显示
        $paginate->show('Public/paginate');
whereIn
-----------------------------------------------------
        提供一个便捷的in查询方法
        D('User')->whereIn('id',[1,2,3,])->select();
mapWhere
-----------------------------------------------------
        使where查询也可以支持,$_map属性定义的映射关系
保存save优化
-----------------------------------------------------
        如果使用OO方式编写代码时，会使用getDirty来获取脏字段进行数据库更新
        $user = D('User');
        $user->where(['id'=>1])->find();
        $user->password = md5('123456');
        $user->save();
        在这里update set时只会设置password





