<?php

namespace anxu\Crontab;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use anxu\Crontab\BaseJob;
use yii\console\Exception;
use \Cron\CronExpression;

/**
 * job class
 */
class Job extends BaseObject implements BaseJob
{
    /**
     * resource $tmpfileHandle
     * @var null
     */
    private $tmpfileHandle =null;

    /**
    * @var string
    */
    protected $tmpDir;

 
    public $name;
    public $schedule;
    public $command;
    public $maxRuntime =null;
    public $output =null;


    public function init()
    {
        parent::init();

        if (empty($this->schedule) || empty($this->command) || empty($this->name)) {
            throw new InvalidConfigException("'schedule','command','name' is required");
        }
        $this->tmpDir = $this->getTempDir();
    }

    /**
    * job run
    * @return void
    */
    public function run()
    {

        if (!$this->isDo()) {
            return;
        }

        try {
            $tmpFile = $this->tmpFilePath();
            
            $this->checkMaxRuntime($tmpFile); //检查上一次执行是否超时
    
            $this->lockFile($tmpFile); //为该任务创建一个临时文件，保证每次只有一个任务进程执行
    
            $this->runCommand();
        } catch (Exception $e) {
            Yii::error("job:".$this->name."Error:".$e->getMessage(), __METHOD__);
            return;
        }

        if ($this->tmpfileHandle !==null) {
            $this->unlockFile($tmpFile);
        }
    }

    protected function isDo()
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->schedule);
        if ($dateTime !== false) {
            return $dateTime->format('Y-m-d H:i') == (date('Y-m-d H:i'));
        }

        return CronExpression::factory($this->schedule)->isDue();
    }
    /**
     * 根据临时文件检查执行是否超时
    * @param string $tmpFile
    * @throws Exception
    */
    protected function checkMaxRuntime(string $tmpFile)
    {
        if ($this->maxRuntime === null) {
            return;
        }

        $runtime = $this->getFileMtime($tmpFile);

        if ($runtime < $this->maxRuntime) {
            return;
        }
        throw new Exception("MaxRuntime of $this->maxRuntime secs exceeded! Current runtime: $runtime secs");
    }

    /**
     * 创建一个临时文件，防止重复执行
     * @param  string $tmpFile
     */
    protected function lockFile(string $tmpFile)
    {
        if (!file_exists($tmpFile) && !touch($tmpFile)) {
            throw new Exception("Unable to create file (File: $tmpFile).");
        }

        $fh = fopen($tmpFile, 'rb+');
        if ($fh === false) {
            throw new Exception("Unable to open file (File: $tmpFile).");
        }

        $i = 5;
        //如果文件无法锁定，则等待250微秒，再次尝试；总共尝试5次
        while ($i > 0) {
            if (flock($fh, LOCK_EX | LOCK_NB)) {//锁定文件
                $this->tmpfileHandle = $fh;
                ftruncate($fh, 0);
                fwrite($fh, posix_getpid());
                return;
            }
            usleep(250);
            --$i;
        }

        throw new Exception("Job is still locked (Lockfile: $tmpFile)!");
    }

    /**
     * @param string $tmpFile
     *
     * @throws Exception
     */
    protected function unlockFile($tmpFile)
    {
        ftruncate($this->tmpfileHandle, 0);
        flock($this->tmpfileHandle, LOCK_UN);
    }


    /**
    * 获取文件最后一次修改到现在到现在有多长时间
    * @param  string 文件路径
    * @return timestamp
    */
    protected function getFileMtime($tmpFile)
    {
        if (!file_exists($tmpFile)) {
            return 0;
        }

        $pid = file_get_contents($tmpFile);
        if (empty($pid)) {
            return 0;
        }
        //posix_kill
        if (!posix_kill((int) $pid, 0)) {
            return 0;
        }

        $stat = stat($tmpFile);

        return (time() - $stat['mtime']);//time of last modification (unix timestamp)
    }
       
    /**
    * @return string
    */
    protected function tmpFilePath()
    {
        $tmp = $this->tmpDir;
        $job = md5($this->name);
        return "$tmp/$job.lck";
    }
    /**
     * 执行脚本或命令
     * @return [type] [description]
     */
    protected function runCommand()
    {
        $output =$this->getOutputFile();
        exec("$this->command 1>>".$output." 2>&1", $dummy, $retval);
    }

    /**
     * 获取脚本执行后，结果的输出目录
     * @return string
     */
    protected function getOutputFile()
    {
        if ($this->output === null) {
            return '/dev/null';
        }
        $logfile = $this->output;
        $logs = dirname($logfile);
        if (!file_exists($logs)) {
            mkdir($logs, 0755, true);
        }
        return $logfile;
    }

    /**
     * 获取临时文件目录
     * @return string
     */
    protected function getTempDir()
    {
        $tmp = sys_get_temp_dir();
        return $tmp ;
    }
}
