<?php
/**
 * 计划任务worker
 */
namespace anxu\Crontab;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\Exception;

class Crontab extends Component
{
    /**
     *  默认job类
     * @var string
     */
    private $defaultJobClass = 'anxu\Crontab\Job';

    /**
     * 定时任务列表
    * @var array
    */
    private $jobs=[];

    /**
    * 是否多进程
    * @var boolean
    */
    public $multiprocess=true;
    

    /**
     * 当前进程数
    * @var init
    */
    private $currentProcess =0;

    /**
     *最大进程
    * @var init
    */
    public $maxProcess=2;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     *
     * 添加任务
     * [
     *  [
     *      'class'=>'anxu\Crontab\Job'
     *       ......
     *  ]
     * ]
     */
    public function add(array $jobs)
    {
        foreach ($jobs as $job) {
            $this->jobs[] = $this->createJob($job);
        }
    }

    /**
     *执行计划任务
     */
    public function run()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            throw new NotSupportedException('Window is not supported');
        }

        if ($this->multiprocess) {
            foreach ($this->jobs as $job) {
                $pid = pcntl_fork();
                if ($pid ==0) {
                    cli_set_process_title("Job");//子进程名
                    $job->run();
                    exit();
                } elseif ($pid ==-1) {
                    throw new Exception('fork process failed');
                } else {
                    $this->currentProcess++;
                    if ($this->currentProcess >= $this->maxProcess) {
                        pcntl_wait($status, WUNTRACED);
                        $this->currentProcess--;
                    }
                }
            }
        } else {
            foreach ($this->jobs as $job) {
                $job->run();
            }
        }
    }

    /**
     * 创建任务job实例
     * @param array
     * @return Job
     */
    protected function createJob(array $job)
    {
        if (!isset($job['class']) || empty($job['class'])) {
            $job['class'] = $this->defaultJobClass;
        }
        try {
            $jobObject = Yii::createObject($job);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (!$jobObject instanceof $this->defaultJobClass) {
            throw new InvalidConfigException('"class" must be implements "anxu\Crontab\BaseJob" interface');
        }
        return $jobObject;
    }
}
