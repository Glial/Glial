<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Cli;

use \Glial\System\Cpu;

class Multithread {

    /**
     * Process in Parallel.
     *
     * Run a function (with no return result) on each item in an array in parallel.
     * Note: This function is only useful if order is not important, and you don't
     * need any return values from the function (i.e. no inter-process communication).
     *
     * @param mixed   $func  A closure function to apply to each item in parallel.
     * @param array   $arr   The array to apply function to.
     * @param integer $procs Number of processes to run in parallel.
     *
     * @return void
     * 
     *
      example to run :

      $makeDir = function($a) {
      shell_exec('mkdir '.shellescapearg($a));
      }

      // An array to process
      $dirnames = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k');

      // Run the process in parallel.
      Multithread::processParallel($makeDir, $dirnames, 8);
     * 
     */
    static public function processParallel($func, array $arr, $procs = NULL) {

        // to improve take task 5 by 5 and wait last of the group of 5 !

        if (empty($procs)) {
            $procs = Cpu::getCpuCores();
        }

        // Break array up into $procs chunks.
        $chunks = array_chunk($arr, ceil((count($arr) / $procs)));
        $pid = -1;
        $children = array();
        foreach ($chunks as $items) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                die('could not fork');
            } else if ($pid === 0) {
                // We are the child process. Pass a chunk of items to process.
                array_walk($items, $func);
                exit(0);
            } else {
                // We are the parent.
                $children[] = $pid;
            }
        }

        // Wait for children to finish.
        foreach ($children as $pid) {
            // We are still the parent.
            pcntl_waitpid($pid, $status);
        }
    }

}
