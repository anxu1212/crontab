<?php
namespace anxu\tests;

use anxu\Crontab\Crontab;

class CrontabTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $logFile;

    protected function _before()
    {
        $this->logFile = dirname(__DIR__) . '/_output/CronTabTest.log';
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

    /**
     * 测试命令可以正常执行
     */
    public function testCommandRun()
    {
        $jobs=[
            [
                'name'=>'demo',
                'schedule'=>'* * * * *',
                'command'=>'echo "hello world!"',
                'output'=>$this->logFile
            ]
        ];
        $a  = new Crontab();
        $a->add($jobs);

        $a->run();

        sleep(1);

        $this->assertContains('hello world!', $this->getLogContent());
    }

    public function testTheDatetimeFormatIsSupport()
    {
        $jobs=[
            [
                'name'=>'demo',
                'schedule'=>date('Y-m-d H:i:s'),
                'command'=>'echo "hello world!"',
                'output'=>$this->logFile
            ]
        ];
        $a  = new Crontab();
        $a->add($jobs);

        $a->run();

        sleep(1);

        $this->assertContains('hello world!', $this->getLogContent());
    }

    public function testIfDatetimeIsWrongCommandShouldNotRun()
    {
        $jobs=[
            [
                'name'=>'demo',
                'schedule'=>date('Y-m-d H:i:s', strtotime('+1 minute')),
                'command'=>'echo "hello world!"',
                'output'=>$this->logFile
            ]
        ];
        $cron  = new Crontab();
        $cron->add($jobs);
        $cron->run();
        sleep(1);

        $this->assertFileNotExists($this->logFile);
    }

    public function testRunMultipleJobs()
    {
        $jobs=[
            [
                'name'=>'test',
                'schedule'=>"* * * * *",
                'command'=>'echo "job_1"',
                'output'=>$this->logFile
            ],[
                'name'=>'demo',
                'schedule'=>"* * * * *",
                'command'=>'echo "job_2"',
                'output'=>$this->logFile
            ],
        ];
        $a  = new Crontab();
        $a->add($jobs);

        $a->run();

        sleep(1);

        $this->assertContains('job_1', $this->getLogContent());
        $this->assertContains('job_2', $this->getLogContent());
    }



    private function getLogContent()
    {
        return file_get_contents($this->logFile);
    }
}
