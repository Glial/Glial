<?php

namespace Glial\Cli;

class SetTimeLimit
{

    const TIME_OUT_REACHED = 1;
    const SCRIPT_WITH_STD_ERROR = 2;
    const SCRIPT_WITH_STD_OUT = 4;
    const EXIT_WITHOUT_ERROR = 8;
    const DEBUG = true;

    /*
     * this function launch a cmd and return true or false
     * 
     * idea from kexianbin at diyism dot com : http://php.net/manual/fr/function.set-time-limit.php
     * 
     * 
     * to check if anything good 
     * 
     * $ret = SetTimeLimit(...);
     * 
     * if ($ret['return'] & SetTimeLimit::EXIT_WITHOUT_ERROR !== 0)
     * //if it's ok
     * else
     * //if not
     *  
     * 
     */

    static public function run($controller, $action, $param = array(), $timeout = 10)
    {
        $start = time();
        $stdout = '';
        $stderr = '';
        $stdin = '';

        $output = 0;

        //file_put_contents('debug.txt', time().':cmd:'.$cmd."\n", FILE_APPEND);
        //file_put_contents('debug.txt', time().':stdin:'.$stdin."\n", FILE_APPEND);

        $params = implode(" ", $param);
        $cmd = "php -f " . ROOT . "/application/webroot/index.php $controller $action $params";


        //echo $cmd;

        $process = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return array('return' => '1', 'stdout' => $stdout, 'stderr' => $stderr);
        }
        $status = proc_get_status($process);
        posix_setpgid($status['pid'], $status['pid']);    //seperate pgid(process group id) from parent's pgid

        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        while (1) {
            $stdout.=stream_get_contents($pipes[1]);
            $stderr.=stream_get_contents($pipes[2]);

            if (time() - $start > $timeout) {
                //proc_terminate($process, 9);    
                //only terminate subprocess, won't terminate sub-subprocess
                posix_kill(-$status['pid'], 9);
                //sends SIGKILL to all processes inside group(negative means GPID, all subprocesses share the top process group, except nested my_timeout_exec)
                //file_put_contents('debug.txt', time().":kill group {$status['pid']}\n", FILE_APPEND);

                $output |= self::TIME_OUT_REACHED;
                $output |= empty($stderr) ? 0 : self::SCRIPT_WITH_STD_ERROR;
                $output |= empty($stdout) ? 0 : self::SCRIPT_WITH_STD_OUT;

                return array('return' => $output, 'exitcode' => $status['exitcode'], 'stdout' => $stdout, 'stderr' => $stderr);
            }

            $status = proc_get_status($process);
            //file_put_contents('debug.txt', time().':status:'.var_export($status, true)."\n";
            if (!$status['running']) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                $output |= empty($stderr) ? 0 : self::SCRIPT_WITH_STD_ERROR;
                $output |= empty($stdout) ? 0 : self::SCRIPT_WITH_STD_OUT;
                $output |= empty($status['exitcode']) ? self::EXIT_WITHOUT_ERROR : 0;

                return array('return' => $output, 'exitcode' => $status['exitcode'], 'stdout' => $stdout, 'stderr' => $stderr);
            }

            usleep(100000);
        }
    }

    static public function my_background_exec($function_name, $params, $str_requires, $timeout = 600)
    {
        $map = array('"' => '\"', '$' => '\$', '`' => '\`', '\\' => '\\\\', '!' => '\!');
        $str_requires = strtr($str_requires, $map);
        $path_run = dirname($_SERVER['SCRIPT_FILENAME']);
        $my_target_exec = "/usr/bin/php -r \"chdir('{$path_run}');{$str_requires} \\\$params=json_decode(file_get_contents('php://stdin'),true);call_user_func_array('{$function_name}', \\\$params);\"";
        $my_target_exec = strtr(strtr($my_target_exec, $map), $map);
        $my_background_exec = "(/usr/bin/php -r \"chdir('{$path_run}');{$str_requires} my_timeout_exec(\\\"{$my_target_exec}\\\", file_get_contents('php://stdin'), {$timeout});\" <&3 &) 3<&0"; //php by default use "sh", and "sh" don't support "<&0"
        SetTimeLimit::my_timeout_exec($my_background_exec, json_encode($params), 2);
    }

    static private function my_timeout_exec($cmd, $stdin = '', $timeout)
    {
        $start = time();
        $stdout = '';
        $stderr = '';
        //file_put_contents('debug.txt', time().':cmd:'.$cmd."\n", FILE_APPEND);
        //file_put_contents('debug.txt', time().':stdin:'.$stdin."\n", FILE_APPEND);

        $process = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return array('return' => '1', 'stdout' => $stdout, 'stderr' => $stderr);
        }
        $status = proc_get_status($process);
        posix_setpgid($status['pid'], $status['pid']);    //seperate pgid(process group id) from parent's pgid

        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        while (1) {
            $stdout.=stream_get_contents($pipes[1]);
            $stderr.=stream_get_contents($pipes[2]);

            if (time() - $start > $timeout) {
//proc_terminate($process, 9);    
//only terminate subprocess, won't terminate sub-subprocess
                posix_kill(-$status['pid'], 9);
                ////sends SIGKILL to all processes inside group(negative means GPID, all subprocesses share the top process group, except nested my_timeout_exec)
                //file_put_contents('debug.txt', time().":kill group {$status['pid']}\n", FILE_APPEND);
                return array('return' => '1', 'stdout' => $stdout, 'stderr' => $stderr);
            }

            $status = proc_get_status($process);
            //file_put_contents('debug.txt', time().':status:'.var_export($status, true)."\n";
            if (!$status['running']) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                return $status['exitcode'];
            }

            usleep(100000);
        }
    }

    static function exitWithoutError(array $ret)
    {
        return ($ret['return'] & SetTimeLimit::EXIT_WITHOUT_ERROR) === SetTimeLimit::EXIT_WITHOUT_ERROR;
    }

}
