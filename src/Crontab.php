<?php

namespace anxu\Crontab;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;

/**
* Crontab class
*/
class Crontab extends Component
{

    private $defaultJobClass = 'anxu\Crontab\Job';

    private $jobs = [];
    private $currentProcess =0;

    public $maxProcess=2;
    public $isMuteProcess=false;



    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }


    /**
     * Add a job.
     * [
     *  [
     *      'class'=>'anxu\Crontab\Job'
     *      'name'=>'',
     *      'schedule'=>'',
     *      'command'=>'',
     *      'maxRuntime'=>''
     *  ]
     * ]
     *
     * @param array $job
     *
     * @throws Exception
     */
    public function add(array $jobs)
    {
        foreach ($jobs as $job) {
            $this->jobs[$job['name']] = ArrayHelper::merge(['class'=>$this->defaultJobClass], $job);
        }
    }

    /**
     * Run all jobs.
     */
    public function run()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            throw new NotSupportedException('Window is not supported');
        }
        if ($this->isMuteProcess) {
            $this->multiProcessRun();
        } else {
            $this->commonRun();
        }
    }

    /**
     * Run all jobs.
     */
    public function commonRun()
    {
        foreach ($this->jobs as $job) {
            $jobObj = $this->createJob($job);
            $jobObj->run();
        }
    }
    /**
     * Multi-process Run all jobs.
     */
    public function multiProcessRun()
    {
        foreach ($this->jobs as $job) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new Exception('fork process failed');
            } elseif ($pid) {
                $this->currentProcess++;
                if ($this->currentProcess >= $this->maxProcess) {
                    pcntl_wait($status, WUNTRACED);
                    $this->currentProcess--;
                }
            } else {
                cli_set_process_title("phpCrontab");//子进程名
                $jobObj = $this->createJob($job);
                $jobObj->run();
                exit();
            }
        }
    }


    /**
     * Creates cron job instance from its array configuration.
     * @param array $config cron job configuration.
     * @return CronJob cron job instance.
     */
    protected function createJob(array $job)
    {
        $jobClass = $job['class'];
        ArrayHelper::remove($job, 'class');
        if (!is_a($jobClass, $this->defaultJobClass, true)) {
            throw new InvalidConfigException('"class" needs to be an instanceof anxu\Crontab\Job');
        }
        return Yii::createObject($jobClass, [$job]);
    }
}
