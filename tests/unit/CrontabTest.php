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

    public function testRunJobsWithMultiProcess()
    {
        $jobs=[
            [
                'name'=>'job1',
                'schedule'=>"* * * * *",
                'command'=>'sleep 10;echo "job_1"',
                'output'=>$this->logFile
            ],[
                'name'=>'job2',
                'schedule'=>"* * * * *",
                'command'=>'sleep 10;echo "job_2"',
                'output'=>$this->logFile
            ],[
                'name'=>'job3',
                'schedule'=>"* * * * *",
                'command'=>'sleep 10;echo "job_3"',
                'output'=>$this->logFile
            ],[
                'name'=>'job4',
                'schedule'=>"* * * * *",
                'command'=>'sleep 10;echo "job_4"',
                'output'=>$this->logFile,
                'maxRuntime'=>5
            ],
            
        ];
        $a  = new Crontab([
            'isMuteProcess'=>true
        ]);
        $a->add($jobs);

        $a->run();
        sleep(1);
        $this->assertContains('job_1', $this->getLogContent());
        $this->assertContains('job_3', $this->getLogContent());
        sleep(10);
        $this->assertContains('job_2', $this->getLogContent());
        $this->assertContains('job_4', $this->getLogContent());
    }


    private function getLogContent(string $path = null)
    {
        return $path ?file_get_contents($path):file_get_contents($this->logFile);
    }
}
