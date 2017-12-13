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
        
        foreach ($this->jobs as $job) {
            $jobObj = $this->createJob($job);
            $jobObj->run();
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
