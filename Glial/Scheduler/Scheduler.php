<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Scheduler;

use Glial\Scheduler\Shutdown\Shutdown;

class Scheduler
{

    static public function onShutdown()
    {
        Shutdown::getInstance()->registerStaticClass($className, $methodName, $methodParams);
    }

    static public function onTimeLimit($className, $methodName, array $methodParams = array())
    {
        Shutdown::getInstance()->registerStaticClassOnTime($className, $methodName, $methodParams);
    }

    static public function onBackground($className, $methodName, array $methodParams = array())
    {
        Shutdown::getInstance()->registerStaticClassOnTime($className, $methodName, $methodParams);
    }

    static public function onMaxTime($className, $methodName, array $methodParams = array())
    {
        Shutdown::getInstance()->registerStaticClassOnTime($className, $methodName, $methodParams);
    }

    function my_background_exec($function_name, $params, $str_requires, $timeout = 600)
    {
        $map = array('"' => '\"', '$' => '\$', '`' => '\`', '\\' => '\\\\', '!' => '\!');
        $str_requires = strtr($str_requires, $map);
        $path_run = dirname($_SERVER['SCRIPT_FILENAME']);
        $my_target_exec = "/usr/bin/php -r \"chdir('{$path_run}');{$str_requires} \\\$params=json_decode(file_get_contents('php://stdin'),true);call_user_func_array('{$function_name}', \\\$params);\"";
        $my_target_exec = strtr(strtr($my_target_exec, $map), $map);
        $my_background_exec = "(/usr/bin/php -r \"chdir('{$path_run}');{$str_requires} my_timeout_exec(\\\"{$my_target_exec}\\\", file_get_contents('php://stdin'), {$timeout});\" <&3 &) 3<&0"; //php by default use "sh", and "sh" don't support "<&0"
        my_timeout_exec($my_background_exec, json_encode($params), 2);
    }

    function my_timeout_exec($cmd, $stdin = '', $timeout)
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

            if (time() - $start > $timeout) {//proc_terminate($process, 9);    //only terminate subprocess, won't terminate sub-subprocess
                posix_kill(-$status['pid'], 9);    //sends SIGKILL to all processes inside group(negative means GPID, all subprocesses share the top process group, except nested my_timeout_exec)
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

}
