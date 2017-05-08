<?php

namespace DavinBao\PhpGit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

/**
 * 运行脚本补丁
 *
 * @package App\Console\Commands
 *
 * @author cunqinghuang
 * @since 2017/4/25 14:34
 */
class PatchScript extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'patch:script';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Patching the script.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function getOptions()
    {
        return [
            ['uninstall', 'u', InputOption::VALUE_NONE, 'uninstall for patch.'],
            ['install', 'i', InputOption::VALUE_NONE, 'install for patch.'],
            ['put_file', 'p', InputOption::VALUE_OPTIONAL, 'install for file.'],
            ['off_file', 'o', InputOption::VALUE_OPTIONAL, 'uninstall for file.'],
        ];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $self = $this;
        $self->info("Patching script... \n");
        $unOption = $this->option('uninstall');
        $inOption = $this->option('install');
        $putOption = $this->option('put_file');
        $offOption = $this->option('off_file');

        $path = app('config')->get('phpgit.path');
        $branch = $self->getBranch($self);

        $scriptFile = dirname(app_path()).$path."ScriptBase.php";
        if(file_exists($scriptFile)){
            require_once $scriptFile;
        }

        $production = app('config')->get('app.debug');
        if(!$production){
            $self->executeProductionCommand($path,$unOption,$inOption,$putOption,$offOption);
        }else{
            $self-> executeCommand($path,$unOption,$inOption,$putOption,$offOption,$branch);
        }
        return $self->info("Patching Script Success\n");
    }

    /**
     * 获取当前分支名称
     *
     * @return mixed
     */
    public function getBranch($self){
        $branch = @file_get_contents(base_path() . '/.git/HEAD');

        if (!empty($branch)) {
            $branch = trim($branch);
            $i      = strripos($branch, '/');
            $branch = strtolower(substr($branch, $i + 1));
            return $branch;
        }else{
            return $self->error("Expect parameter '--branch'\n");
        }
    }

    /**
     * 根据指令在生产环境进行安装和卸载配置文件
     * @param string $path 脚本路径
     * @param string $unOption 卸载指令
     * @param string $inOption 安装指令
     * @param string $putOption 指定安装的分支名
     * @param string $offOption 指定卸载的分支名
     *
     * @return mixed
     */
    public function executeProductionCommand($path,$unOption,$inOption,$putOption,$offOption){
        $self = $this;
        $pathFile = strtolower(dirname(app_path()).$path."production.php");
        if (!file_exists($pathFile)){
            return $self->info("No Configuration\n");
        }
        require_once $pathFile;

        $class = "ProductionScript";

        if($inOption){
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> install();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }

        if($unOption){
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> uninstall();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }

        if($putOption){
            $class = str_replace('-','',ucfirst($putOption))."ProductionScript";
            $pathFile = strtolower(dirname(app_path()).$path.$putOption."-production.php");
            if (!file_exists($pathFile)){
                return $self->info("No Configuration\n");
            }
            require_once $pathFile;
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> install();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }

        if($offOption){
            $class = str_replace('-','',ucfirst($offOption))."ProductionScript";
            $pathFile = strtolower(dirname(app_path()).$path.$offOption."-production.php");
            if (!file_exists($pathFile)){
                return $self->info("No Configuration\n");
            }
            require_once $pathFile;
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> uninstall();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }
    }

    /**
     * 根据运行指令进行安装和卸载
     *
     * @param string $path 脚本路径
     * @param string $unOption 卸载指令
     * @param string $inOption 安装指令
     * @param string $putOption 指定安装的分支名
     * @param string $offOption 指定卸载的分支名
     * @param string $branch 当前分支名
     *
     * @return mixed
     */
    public function executeCommand($path,$unOption,$inOption,$putOption,$offOption,$branch){
        $self = $this;
        $class = str_replace('-','',ucfirst($branch))."Script";
        $pathFile = strtolower(dirname(app_path()).$path.$branch.".php");
        if (!file_exists($pathFile)){
            return $self->info("No Configuration\n");
        }
        require_once $pathFile;
        if ($unOption) {
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> uninstall();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }
        if($inOption){
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script->install();
            } catch (\Exception $e) {
                return $self->error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }

        if($putOption){
            $class = str_replace('-','',ucfirst($putOption))."Script";
            $pathFile = strtolower(dirname(app_path()).$path.$putOption.".php");
            if (!file_exists($pathFile)){
                return $self->info("No Configuration\n");
            }
            require_once $pathFile;
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> install();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }

        if($offOption){
            $class = str_replace('-','',ucfirst($offOption))."Script";
            $pathFile = strtolower(dirname(app_path()).$path.$offOption.".php");
            if (!file_exists($pathFile)){
                return $self->info("No Configuration\n");
            }
            require_once $pathFile;
            try {
                set_time_limit(0);
                $script = eval("return new \\$class();");
                $script -> uninstall();
            } catch (\Exception $e) {
                return $self -> error($e->getMessage(). "\n" . $e->getTraceAsString() . "\n");
            }
        }
    }

}