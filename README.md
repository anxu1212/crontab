Yii2 的插件，管理所有后台定时任务，不用修改 crontab

### 注意
无法在window系统服务器中运行

# 用法
通过 `composer` 安装
```
composer require anxu/crontab:"*"
```

Yii2 console 脚本，示例如下：
```php

namespace app\commands;

use yii\console\Controller;
use anxu\Crontab\Crontab;

class DemoController extends Controller
{
    public function actionIndex()
    {
        // 从数据库获取执行的任务
        $jobs=[
            [
                'name'=>'1',
                'schedule'=>'* * * * *',
                'command'=>'date',
                'output'=>'log/log.log'
            ],[
                'name'=>'2',
                'schedule'=>'* * * * *',
                'command'=>'echo "test2"',
                'output'=>'log/log.log'
            ]
        ];

        $a  = new Crontab();
        $a->add($jobs);
        $a->run();
    }
}

```

将上面的脚本加入`crontab`中，如下所示：
```
* * * * * /path/to/project/yii demo/index 1>> /dev/null 2>&1
```

最后，启动crontab

| 属性 | 说明 | 备注|
|-----|------|----|
|`name`| 任务名字|必须，唯一；如果重复则是当作相同任务|
|`schedule`|任务执行时间|必须，支持`"* * * * *"`也支持`"Y-m-d H:i:s"`|
|`command`|命令 |必须， shell 命令|
|`output`|命令输入路径|如果命令有输出，则可以指定输出位置|

> "Y-m-d H:i:s" 格式，后面的 秒s  会被忽略；