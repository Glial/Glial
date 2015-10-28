<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\System;

class Cpu
{
    /**
     * Returns the number of available CPU cores
     *  Should work for Linux, Windows, Mac & BSD
     * @return integer 
     */
    static function getCpuCores()
    {
        $numCpus = 1;

        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);

            $numCpus = count($matches[0]);
            // 'nproc' should work too 
        } else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
            $process = @popen('wmic cpu get NumberOfCores', 'rb');

            if (false !== $process) {
                fgets($process);
                $numCpus = intval(fgets($process));

                pclose($process);
            }
        } else {
            $process = @popen('sysctl -a', 'rb');

            if (false !== $process) {
                $output = stream_get_contents($process);

                preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                if ($matches) {
                    $numCpus = intval($matches[1][0]);
                }

                pclose($process);
            }
        }

        return $numCpus;
    }
}
