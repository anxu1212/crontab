<?php
namespace anxu\tests;

use Yii;
use anxu\Crontab\Job;
use yii\base\InvalidConfigException;

class JobTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $logFile;

    protected function _before()
    {
        $this->logFile = dirname(__DIR__) . '/_output/JobTest.log';
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    protected function _after()
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
    // tests
    public function testCreateJob()
    {
        $job = [
            'class'=>'anxu\Crontab\Job',
            'name'=>'demo',
            'schedule'=>'* * * * *',
            'command'=>'echo "hello world!"',
            'output'=>$this->logFile
        ];

        $jobObj = Yii::createObject($job);

        $this->assertEquals($jobObj->name, $job['name']);
        $this->assertEquals($jobObj->schedule, $job['schedule']);
        $this->assertEquals($jobObj->command, $job['command']);
        $this->assertEquals($jobObj->output, $job['output']);
    }

    // tests
    public function testNewJob()
    {
        $job = [
            'name'=>'demo',
            'schedule'=>'* * * * *',
            'command'=>'echo "hello world!"',
            'output'=>$this->logFile
        ];
        $jobObj = new Job($job);

        $this->assertEquals($jobObj->name, $job['name']);
        $this->assertEquals($jobObj->schedule, $job['schedule']);
        $this->assertEquals($jobObj->command, $job['command']);
        $this->assertEquals($jobObj->output, $job['output']);
    }

    public function testJobRun()
    {
        $job = [
            'name'=>'demo',
            'schedule'=>'* * * * *',
            'command'=>'echo "hello world!"',
            'output'=>$this->logFile
        ];
        $jobObj = new Job($job);

        $jobObj->run();

        sleep(1);

        $this->assertContains('hello world!', $this->getLogContent());
    }

    public function testJobNameOptions()
    {
        $msg='';
        try {
            new Job([
                'schedule'=>'* * * * *',
                'command'=>'echo "hello world!"'
            ]);
        } catch (InvalidConfigException $e) {
            $msg = $e->getMessage();
        }
        
        $this->assertEquals($msg, "'schedule','command','name' is required");
    }


    public function testJobScheduleOptions()
    {
        $msg='';
        try {
            new Job([
                'name'=>'demo',
                'command'=>'echo "hello world!"'
            ]);
        } catch (InvalidConfigException $e) {
            $msg = $e->getMessage();
        }
        
        $this->assertEquals($msg, "'schedule','command','name' is required");
    }
    public function testJobCommandOptions()
    {
        $msg='';
        try {
            new Job([
                'name'=>'demo',
                'schedule'=>'* * * * *'
            ]);
        } catch (InvalidConfigException $e) {
            $msg = $e->getMessage();
        }
        
        $this->assertEquals($msg, "'schedule','command','name' is required");
    }


    private function getLogContent()
    {
        return file_get_contents($this->logFile);
    }
}
