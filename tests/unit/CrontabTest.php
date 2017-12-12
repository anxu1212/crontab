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
                'name'=>'223',
                'schedule'=>'* * * * *',
                'command'=>'echo "hello world!"',
                'output'=>$this->logFile
            ]
        ];
        $a  = new Crontab();
        $a->add($jobs);

        $a->run();

        sleep(1);

        $this->assertContains('hello world!', $this->getLogContent(), "命令没有执行");
    }

    public function testTheDatetimeFormatIsSupport()
    {
        $jobs=[
            [
                'name'=>'223',
                'schedule'=>date('Y-m-d H:i:s'),
                'command'=>'echo "hello world!"',
                'output'=>$this->logFile
            ]
        ];
        $a  = new Crontab();
        $a->add($jobs);

        $a->run();

        sleep(1);

        $this->assertContains('hello world!', $this->getLogContent(), "datetime格式日期，测试有问题");
    }

    public function testCommandShouldNotRun()
    {
        $jobs=[
            [
                'name'=>'223',
                'schedule'=>date('Y-m-d H:i:s', strtotime('+1 minute')),
                'command'=>'echo "hello world!"',
                'output'=>$this->logFile
            ]
        ];
        $cron  = new Crontab();
        $cron->add($jobs);
        $cron->run();
        sleep(1);

        $this->assertFileNotExists($this->logFile, "命令被执行");
    }

    public function testJobAdd()
    {
        
    }


    private function getLogContent()
    {
        return file_get_contents($this->logFile);
    }
}
