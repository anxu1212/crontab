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


    public $bin = 'yii';

    protected $jobs = [];


    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }


    /**
     * Add a job.
     * [
     *   'class'=>'anxu\Crontab\Job'
     *   'name'=>'',
     *   'schedule'=>'',
     *   'command'=>'',
     *   'maxRuntime'=>''
     * ]
     *
     * @param array $job
     *
     * @throws Exception
     */
    public function add(array $jobs)
    {
        foreach ($jobs as $job) {
            if (empty($job['schedule']) || !isset($job['command']) || !isset($job['name'])) {
                throw new InvalidConfigException("'schedule','command','name' is required");
            }

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
            if (!$this->isDo($job['schedule'])) {
                continue;
            }
            
            $this->runJob($job);
        }
    }


    protected function isDo(string $schedule)
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $schedule);
        if ($dateTime !== false) {
            return $dateTime->format('Y-m-d H:i') == (date('Y-m-d H:i'));
        }

        return   \Cron\CronExpression::factory($schedule)->isDue();
    }

    /**
     * @param string $job
     * @param array  $config
     */
    protected function runJob(array $job)
    {
        $jobObj = $this->createJob($job);
        $jobObj->run();
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
