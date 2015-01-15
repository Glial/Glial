<?php

/**
 * @class ProcessManager
 * Class that handle creating multiple process
 * 
 * @licence GNU/GPLv3
 * 
 * @author Cyril Nicodème
 * 
 * 
 * @note : This doesn't work on Windows machine
 * @note : It is recommended to use this class in an cli environnement, 
 * 		forking is NOT recommended running from an Apache (or some other preforking web server) module
 * 
 * @see : http://www.ibuildings.com/blog/archives/1539-Boost-performance-with-parallel-processing.html
 */

namespace Glial\Cli;

class ProcessManager
{

    /**
     * Contain the Processus Id of the current processus
     * 
     * @var Integer $_iPid
     */
    private $_iPid;

    /**
     * Contain the priority for the current processus
     * 
     * @var Integer $_iPriority
     */
    private $_iPriority = 0;

    /**
     * Contain a list of all the childrens
     * (in case the current processus is the father)
     * 
     * @var Array $_aChildrens
     */
    private $_aChildrens = array();

    /**
     * Contain the number of max allowed childrens
     * 
     * @var Integer $_iMaxChildrens
     */
    private $_iMaxChildrens = 20;

    /**
     * Contain the number of max allowed childrens
     * 
     * @var Integer $_iMaxChildrens
     */
    private $_iStatus = array();
    private $_iCurrentThread = 0;

    /**
     * Constructor
     * Test if this application can be used, set the MaxChildren value, 
     * retrieve his Process ID and set the signals
     * 
     * @param Integer $iMaxChildrens (optional)
     * 
     * @return ProcessManager
     */
    public function __construct($iMaxChildrens = 20)
    {
        if (!function_exists('pcntl_fork'))
            throw new \Exception('Your configuration does not include pcntl functions.');

        if (!is_int($iMaxChildrens) || $iMaxChildrens < 1)
            throw new \Exception('Childrens must be an Integer');

        $this->_iMaxChildrens = $iMaxChildrens;
        $this->_iPid = getmypid();

        // Setting up the signal handlers
        $this->addSignal(SIGTERM, array($this, 'signalHandler'));
        $this->addSignal(SIGQUIT, array($this, 'signalHandler'));
        $this->addSignal(SIGINT, array($this, 'signalHandler'));
    }

    public function __destruct()
    {
        foreach ($this->_aChildrens as $iChildrensPid)
            pcntl_waitpid($iChildrensPid, $iStatus);
    }

    /**
     * Fork a Processus
     * 
     * @return void
     * @example $fork->fork(__NAMESPACE__ .'\\'.__CLASS__.'::daemonCheckServer', array($ob50->name, 'param2'));
     */
    public function fork($mFunction, $aParams = array())
    {
        if (!is_string($mFunction) && !is_array($mFunction))
            throw new \Exception('Function given must be a String or an Array');

        if (!is_array($aParams))
            throw new \Exception('Parameters must be an Array');


        //to keep order in log
        usleep(500);


        $iPid = pcntl_fork();

        if ($iPid === -1)
            throw new \Exception('Unable to fork.');
        elseif ($iPid > 0) {
            // We are in the parent process
            $this->_aChildrens[] = $iPid;

            $this->_iCurrentThread++;
            $this->_iStatus[$iPid]['function'] = $mFunction;
            $this->_iStatus[$iPid]['param'] = $aParams;
            $this->_iStatus[$iPid]['curent_thread'] = $this->_iCurrentThread;
            $this->_iStatus[$iPid]['status'] = 999;


            if (count($this->_aChildrens) >= $this->_iMaxChildrens) {

                $this->waitEndOfOneChild();
                /*
                  $pid = pcntl_waitpid(-1, $iStatus);
                  $this->_iStatus[$this->_iCurrentThread][$pid]['status'] = $iStatus;
                  $key = array_search($pid, $this->_aChildrens);
                  unset($this->_aChildrens[$key]);
                 */
            }
        } elseif ($iPid === 0) { // We are in the child process
            call_user_func_array($mFunction, $aParams);
            exit(0);
        }
    }

    /**
     * Add a new signal that will be called to the given function with some optionnals parameters
     * 
     * @param Integer $iSignal
     * @param Mixed $mFunction
     * @param Array $aParams[optional]
     * 
     * @return void
     */
    public function addSignal($iSignal, $mFunction)
    {
        if (!is_int($iSignal))
            throw new \Exception('Signal must be an Integer.');

        if (!is_string($mFunction) && !is_array($mFunction))
            throw new \Exception('Function to callback must be a String or an Array.');

        if (!pcntl_signal($iSignal, $mFunction))
            throw new \Exception('Unable to set up the signal.');
    }

    /**
     * The default signal handler, to avoid Zombies
     * 
     * @param Integer $iSignal
     * 
     * @return void
     */
    public function signalHandler($iSignal = SIGTERM)
    {
        switch ($iSignal) {
            case SIGTERM: // Finish
                exit(0);
                break;
            case SIGKILL: // Kill
                echo "The script has been killed !\n";
                exit(1);
                break;


            case SIGINT:  // Stop from the keyboard
                echo "Interuption from keyboaard !\n";
                exit(1);
                
                break;
            case SIGKILL: // Kill
                exit(1);
                break;
        }
    }

    /**
     * Set the number of max childrens
     * 
     * @param Integer $iMaxChildren
     * 
     * @return void
     */
    public function setMaxChildren($iMaxChildren)
    {
        if (!is_int($iMaxChildrens) || $iMaxChildrens < 1)
            throw new Exception('Childrens must be an Integer');

        $this->_iMaxChildrens = $iMaxChildrens;
    }

    /**
     * Return the current number of MaxChildrens
     * 
     * @return Integer
     */
    public function getMaxChildrens()
    {
        return self::$_iMaxChildrens;
    }

    /**
     * Set the priority of the current processus.
     * 
     * @param Integer $iPriority
     * @param Integer $iProcessIdentifier[optional]
     * 
     * @return void
     */
    public function setPriority($iPriority, $iProcessIdentifier = PRIO_PROCESS)
    {
        if (!is_int($iPriority) || $iPriority < -20 || $iPriority > 20)
            throw new Exception('Invalid priority.');

        if ($iProcessIdentifier != PRIO_PROCESS || $iProcessIdentifier != PRIO_PGRP || $iProcessIdentifier != PRIO_USER)
            throw new Exception('Invalid Process Identifier type.');

        if (!pcntl_setpriority($iPriority, $this->_iPid, $iProcessIdentifier))
            throw new Exception('Unable to set the priority.');

        self::$_iPriority = $iPriority;
    }

    /**
     * Get the priority of the current processus.
     * 
     * @return Integer
     */
    public function getPriority()
    {
        return self::$_iPriority;
    }

    /**
     * Return the PID of the current process
     * 
     * @return Integer
     */
    public function getMyPid()
    {
        return $this->_iPid;
    }

    /**
     * Return the status of all processes
     * 
     * @return Array
     */
    public function getStatus()
    {
        return $this->_iStatus;
    }

    /**
     * Wait the end of all child before coninue parent process
     * 
     * @return true
     */
    public function waitAll()
    {

        foreach ($this->_aChildrens as $elem) {

            $this->waitEndOfOneChild();
        }
    }

    /**
     * Wait the end of one child
     * 
     * @return true
     */
    private function waitEndOfOneChild()
    {
        $pid = pcntl_waitpid(-1, $iStatus);
        $this->_iStatus[$pid]['status'] = $iStatus;
        $key = array_search($pid, $this->_aChildrens);
        unset($this->_aChildrens[$key]);
    }

}

/**
 * 
 * 
glial pma_cli daemonStart
|                                                   [2015-01-06 14:11:32][THREAD STARTED (1 WAIT => 70)]
||                                                  [2015-01-06 14:11:32][THREAD STARTED (2 WAIT => 40)]
|||                                                 [2015-01-06 14:11:32][THREAD STARTED (3 WAIT => 69)]
||||                                                [2015-01-06 14:11:32][THREAD STARTED (4 WAIT => 33)]
|||||                                               [2015-01-06 14:11:32][THREAD STARTED (5 WAIT => 18)]
||||||                                              [2015-01-06 14:11:32][THREAD STARTED (6 WAIT => 59)]
|||||||                                             [2015-01-06 14:11:32][THREAD STARTED (7 WAIT => 61)]
||||||||                                            [2015-01-06 14:11:32][THREAD STARTED (8 WAIT => 91)]
|||||||||                                           [2015-01-06 14:11:32][THREAD STARTED (9 WAIT => 88)]
||||||||||                                          [2015-01-06 14:11:32][THREAD STARTED (10 WAIT => 38)]
|||||||||||                                         [2015-01-06 14:11:32][THREAD STARTED (11 WAIT => 95)]
||||||||||||                                        [2015-01-06 14:11:32][THREAD STARTED (12 WAIT => 100)]
|||||||||||||                                       [2015-01-06 14:11:32][THREAD STARTED (13 WAIT => 72)]
||||||||||||||                                      [2015-01-06 14:11:32][THREAD STARTED (14 WAIT => 22)]
|||||||||||||||                                     [2015-01-06 14:11:32][THREAD STARTED (15 WAIT => 51)]
||||||||||||||||                                    [2015-01-06 14:11:32][THREAD STARTED (16 WAIT => 30)]
|||||||||||||||||                                   [2015-01-06 14:11:32][THREAD STARTED (17 WAIT => 84)]
||||||||||||||||||                                  [2015-01-06 14:11:32][THREAD STARTED (18 WAIT => 91)]
|||||||||||||||||||                                 [2015-01-06 14:11:32][THREAD STARTED (19 WAIT => 63)]
||||||||||||||||||||                                [2015-01-06 14:11:32][THREAD STARTED (20 WAIT => 18)]
||||||||||||||||||||                                [2015-01-06 14:11:50][THREAD STOPED (5 WAIT => 18)]
|||| |||||||||||||||                                [2015-01-06 14:11:50][THREAD STOPED (20 WAIT => 18)]
|||| ||||||||||||||                                 [2015-01-06 14:11:54][THREAD STOPED (14 WAIT => 22)]
|||| |||||||| |||||                                 [2015-01-06 14:12:02][THREAD STOPED (16 WAIT => 30)]
|||| |||||||| | |||                                 [2015-01-06 14:12:05][THREAD STOPED (4 WAIT => 33)]
|||  |||||||| | |||                                 [2015-01-06 14:12:10][THREAD STOPED (10 WAIT => 38)]
|||  |||| ||| | |||                                 [2015-01-06 14:12:12][THREAD STOPED (2 WAIT => 40)]
| |  |||| ||| | |||                                 [2015-01-06 14:12:23][THREAD STOPED (15 WAIT => 51)]
| |  |||| |||   |||                                 [2015-01-06 14:12:31][THREAD STOPED (6 WAIT => 59)]
| |   ||| |||   |||                                 [2015-01-06 14:12:33][THREAD STOPED (7 WAIT => 61)]
| |    || |||   |||                                 [2015-01-06 14:12:35][THREAD STOPED (19 WAIT => 63)]
| |    || |||   ||                                  [2015-01-06 14:12:41][THREAD STOPED (3 WAIT => 69)]
|      || |||   ||                                  [2015-01-06 14:12:42][THREAD STOPED (1 WAIT => 70)]
       || |||   ||  |                               [2015-01-06 14:12:42][THREAD STARTED (21 WAIT => 22)]
       || |||   ||  ||                              [2015-01-06 14:12:42][THREAD STARTED (22 WAIT => 61)]
       || |||   ||  |||                             [2015-01-06 14:12:42][THREAD STARTED (23 WAIT => 62)]
       || |||   ||  ||||                            [2015-01-06 14:12:42][THREAD STARTED (24 WAIT => 40)]
       || |||   ||  |||||                           [2015-01-06 14:12:42][THREAD STARTED (25 WAIT => 35)]
       || |||   ||  ||||||                          [2015-01-06 14:12:42][THREAD STARTED (26 WAIT => 48)]
       || |||   ||  |||||||                         [2015-01-06 14:12:42][THREAD STARTED (27 WAIT => 82)]
       || |||   ||  |||||||                         [2015-01-06 14:12:44][THREAD STOPED (13 WAIT => 72)]
       || ||    ||  |||||||                         [2015-01-06 14:12:56][THREAD STOPED (17 WAIT => 84)]
       || ||     |  |||||||                         [2015-01-06 14:13:00][THREAD STOPED (9 WAIT => 88)]
       |  ||     |  |||||||                         [2015-01-06 14:13:03][THREAD STOPED (8 WAIT => 91)]
          ||     |  |||||||||                       [2015-01-06 14:13:03][THREAD STOPED (18 WAIT => 91)]
          ||     |  ||||||||                        [2015-01-06 14:13:03][THREAD STARTED (28 WAIT => 23)]
          ||     |  |||||||||                       [2015-01-06 14:13:03][THREAD STARTED (29 WAIT => 41)]
          ||        ||||||||||                      [2015-01-06 14:13:03][THREAD STARTED (30 WAIT => 35)]
          ||        ||||||||||                      [2015-01-06 14:13:04][THREAD STOPED (21 WAIT => 22)]
          ||         |||||||||                      [2015-01-06 14:13:07][THREAD STOPED (11 WAIT => 95)]
           |         ||||||||||                     [2015-01-06 14:13:07][THREAD STARTED (31 WAIT => 68)]
           |         ||||||||||                     [2015-01-06 14:13:12][THREAD STOPED (12 WAIT => 100)]
                     |||||||||||                    [2015-01-06 14:13:12][THREAD STARTED (32 WAIT => 37)]
                     ||||||||||||                   [2015-01-06 14:13:12][THREAD STARTED (33 WAIT => 24)]
                     |||||||||||||                  [2015-01-06 14:13:12][THREAD STARTED (34 WAIT => 99)]
                     ||||||||||||||                 [2015-01-06 14:13:12][THREAD STARTED (35 WAIT => 57)]
                     |||||||||||||||                [2015-01-06 14:13:12][THREAD STARTED (36 WAIT => 81)]
                     ||||||||||||||||               [2015-01-06 14:13:12][THREAD STARTED (37 WAIT => 88)]
                     |||||||||||||||||              [2015-01-06 14:13:12][THREAD STARTED (38 WAIT => 86)]
                     ||||||||||||||||||             [2015-01-06 14:13:12][THREAD STARTED (39 WAIT => 95)]
                     |||||||||||||||||||            [2015-01-06 14:13:12][THREAD STARTED (40 WAIT => 47)]
                     ||||||||||||||||||||           [2015-01-06 14:13:12][THREAD STARTED (41 WAIT => 70)]
                     ||||||||||||||||||||           [2015-01-06 14:13:17][THREAD STOPED (25 WAIT => 35)]
                     ||| ||||||||||||||||           [2015-01-06 14:13:22][THREAD STOPED (24 WAIT => 40)]
                     ||  ||||||||||||||||           [2015-01-06 14:13:26][THREAD STOPED (28 WAIT => 23)]
                     ||  || |||||||||||||           [2015-01-06 14:13:30][THREAD STOPED (26 WAIT => 48)]
                     ||   | |||||||||||||           [2015-01-06 14:13:36][THREAD STOPED (33 WAIT => 24)]
                     ||   | |||| ||||||||           [2015-01-06 14:13:38][THREAD STOPED (30 WAIT => 35)]
                     ||   | | || ||||||||           [2015-01-06 14:13:43][THREAD STOPED (22 WAIT => 61)]
                      |   | | || |||||||||          [2015-01-06 14:13:43][THREAD STARTED (42 WAIT => 82)]
                      |   | | || |||||||||          [2015-01-06 14:13:44][THREAD STOPED (23 WAIT => 62)]
                          | | || |||||||||          [2015-01-06 14:13:44][THREAD STOPED (29 WAIT => 41)]
                          |   || ||||||||||         [2015-01-06 14:13:44][THREAD STARTED (43 WAIT => 43)]
                          |   || |||||||||||        [2015-01-06 14:13:44][THREAD STARTED (44 WAIT => 78)]
                          |   || ||||||||||||       [2015-01-06 14:13:44][THREAD STARTED (45 WAIT => 93)]
                          |   || |||||||||||||      [2015-01-06 14:13:44][THREAD STARTED (46 WAIT => 80)]
                          |   || |||||||||||||      [2015-01-06 14:13:49][THREAD STOPED (32 WAIT => 37)]
                          |   |  |||||||||||||      [2015-01-06 14:13:59][THREAD STOPED (40 WAIT => 47)]
                          |   |  |||||| ||||||      [2015-01-06 14:14:04][THREAD STOPED (27 WAIT => 82)]
                              |  |||||| |||||||     [2015-01-06 14:14:04][THREAD STARTED (47 WAIT => 96)]
                              |  |||||| ||||||||    [2015-01-06 14:14:04][THREAD STARTED (48 WAIT => 47)]
                              |  |||||| |||||||||   [2015-01-06 14:14:04][THREAD STARTED (49 WAIT => 69)]
                              |  |||||| ||||||||||  [2015-01-06 14:14:04][THREAD STARTED (50 WAIT => 88)]
                              |  |||||| ||||||||||  [2015-01-06 14:14:09][THREAD STOPED (35 WAIT => 57)]
                              |  | |||| ||||||||||  [2015-01-06 14:14:15][THREAD STOPED (31 WAIT => 68)]
                                 | |||| ||||||||||  [2015-01-06 14:14:22][THREAD STOPED (41 WAIT => 70)]
                                 | ||||  |||||||||  [2015-01-06 14:14:27][THREAD STOPED (43 WAIT => 43)]
                                 | ||||  | |||||||  [2015-01-06 14:14:33][THREAD STOPED (36 WAIT => 81)]
                                 |  |||  | |||||||  [2015-01-06 14:14:38][THREAD STOPED (38 WAIT => 86)]
                                 |  | |  | |||||||  [2015-01-06 14:14:40][THREAD STOPED (37 WAIT => 88)]
                                 |    |  | |||||||  [2015-01-06 14:14:47][THREAD STOPED (39 WAIT => 95)]
                                 |       | |||||||  [2015-01-06 14:14:51][THREAD STOPED (34 WAIT => 99)]
                                         | |||||||  [2015-01-06 14:14:51][THREAD STOPED (48 WAIT => 47)]
                                         | |||| ||  [2015-01-06 14:15:02][THREAD STOPED (44 WAIT => 78)]
                                         |  ||| ||  [2015-01-06 14:15:04][THREAD STOPED (46 WAIT => 80)]
                                         |  | | ||  [2015-01-06 14:15:05][THREAD STOPED (42 WAIT => 82)]
                                            | | ||  [2015-01-06 14:15:13][THREAD STOPED (49 WAIT => 69)]
                                            | |  |  [2015-01-06 14:15:17][THREAD STOPED (45 WAIT => 93)]
                                              |  |  [2015-01-06 14:15:32][THREAD STOPED (50 WAIT => 88)]
                                              |     [2015-01-06 14:15:40][THREAD STOPED (47 WAIT => 96)]
 * 
 * 
 * After my optimisation : (always 20 thread in same time)
 * 
 * root@dba-tools-sa-01:/tmp/sharedmemory# glial pma_cli daemonStart
[Total : 0  ] [2015-01-06 18:11:03] *                                                    [THREAD STARTED (1 WAIT => 100)]
[Total : 1  ] [2015-01-06 18:11:03] |*                                                   [THREAD STARTED (2 WAIT => 30)]
[Total : 2  ] [2015-01-06 18:11:03] ||*                                                  [THREAD STARTED (3 WAIT => 92)]
[Total : 3  ] [2015-01-06 18:11:03] |||*                                                 [THREAD STARTED (4 WAIT => 96)]
[Total : 4  ] [2015-01-06 18:11:03] ||||*                                                [THREAD STARTED (5 WAIT => 80)]
[Total : 5  ] [2015-01-06 18:11:03] |||||*                                               [THREAD STARTED (6 WAIT => 62)]
[Total : 6  ] [2015-01-06 18:11:03] ||||||*                                              [THREAD STARTED (7 WAIT => 59)]
[Total : 7  ] [2015-01-06 18:11:03] |||||||*                                             [THREAD STARTED (8 WAIT => 88)]
[Total : 8  ] [2015-01-06 18:11:03] ||||||||*                                            [THREAD STARTED (9 WAIT => 90)]
[Total : 9  ] [2015-01-06 18:11:03] |||||||||*                                           [THREAD STARTED (10 WAIT => 18)]
[Total : 10 ] [2015-01-06 18:11:03] ||||||||||*                                          [THREAD STARTED (11 WAIT => 80)]
[Total : 11 ] [2015-01-06 18:11:03] |||||||||||*                                         [THREAD STARTED (12 WAIT => 46)]
[Total : 12 ] [2015-01-06 18:11:03] ||||||||||||*                                        [THREAD STARTED (13 WAIT => 89)]
[Total : 13 ] [2015-01-06 18:11:03] |||||||||||||*                                       [THREAD STARTED (14 WAIT => 79)]
[Total : 14 ] [2015-01-06 18:11:03] ||||||||||||||*                                      [THREAD STARTED (15 WAIT => 65)]
[Total : 15 ] [2015-01-06 18:11:03] |||||||||||||||*                                     [THREAD STARTED (16 WAIT => 16)]
[Total : 16 ] [2015-01-06 18:11:03] ||||||||||||||||*                                    [THREAD STARTED (17 WAIT => 55)]
[Total : 17 ] [2015-01-06 18:11:03] |||||||||||||||||*                                   [THREAD STARTED (18 WAIT => 28)]
[Total : 18 ] [2015-01-06 18:11:03] ||||||||||||||||||*                                  [THREAD STARTED (19 WAIT => 18)]
[Total : 19 ] [2015-01-06 18:11:03] |||||||||||||||||||*                                 [THREAD STARTED (20 WAIT => 29)]
[Total : 19 ] [2015-01-06 18:11:19] |||||||||||||||*||||                                 [THREAD STOPED (16 WAIT => 16)]
[Total : 19 ] [2015-01-06 18:11:19] ||||||||||||||| ||||*                                [THREAD STARTED (21 WAIT => 44)]
[Total : 19 ] [2015-01-06 18:11:21] |||||||||*||||| |||||                                [THREAD STOPED (10 WAIT => 18)]
[Total : 18 ] [2015-01-06 18:11:21] ||||||||| ||||| ||*||                                [THREAD STOPED (19 WAIT => 18)]
[Total : 18 ] [2015-01-06 18:11:21] ||||||||| ||||| || ||*                               [THREAD STARTED (22 WAIT => 21)]
[Total : 19 ] [2015-01-06 18:11:21] ||||||||| ||||| || |||*                              [THREAD STARTED (23 WAIT => 50)]
[Total : 19 ] [2015-01-06 18:11:31] ||||||||| ||||| |* ||||                              [THREAD STOPED (18 WAIT => 28)]
[Total : 19 ] [2015-01-06 18:11:31] ||||||||| ||||| |  ||||*                             [THREAD STARTED (24 WAIT => 64)]
[Total : 19 ] [2015-01-06 18:11:32] ||||||||| ||||| |  *||||                             [THREAD STOPED (20 WAIT => 29)]
[Total : 19 ] [2015-01-06 18:11:32] ||||||||| ||||| |   ||||*                            [THREAD STARTED (25 WAIT => 87)]
[Total : 19 ] [2015-01-06 18:11:33] |*||||||| ||||| |   |||||                            [THREAD STOPED (2 WAIT => 30)]
[Total : 19 ] [2015-01-06 18:11:33] | ||||||| ||||| |   |||||*                           [THREAD STARTED (26 WAIT => 81)]
[Total : 19 ] [2015-01-06 18:11:42] | ||||||| ||||| |   |*||||                           [THREAD STOPED (22 WAIT => 21)]
[Total : 19 ] [2015-01-06 18:11:42] | ||||||| ||||| |   | ||||*                          [THREAD STARTED (27 WAIT => 80)]
[Total : 19 ] [2015-01-06 18:11:49] | ||||||| |*||| |   | |||||                          [THREAD STOPED (12 WAIT => 46)]
[Total : 19 ] [2015-01-06 18:11:49] | ||||||| | ||| |   | |||||*                         [THREAD STARTED (28 WAIT => 16)]
[Total : 19 ] [2015-01-06 18:11:58] | ||||||| | ||| *   | ||||||                         [THREAD STOPED (17 WAIT => 55)]
[Total : 19 ] [2015-01-06 18:11:58] | ||||||| | |||     | ||||||*                        [THREAD STARTED (29 WAIT => 30)]
[Total : 19 ] [2015-01-06 18:12:02] | ||||*|| | |||     | |||||||                        [THREAD STOPED (7 WAIT => 59)]
[Total : 19 ] [2015-01-06 18:12:02] | |||| || | |||     | |||||||*                       [THREAD STARTED (30 WAIT => 59)]
[Total : 19 ] [2015-01-06 18:12:03] | |||| || | |||     * ||||||||                       [THREAD STOPED (21 WAIT => 44)]
[Total : 19 ] [2015-01-06 18:12:03] | |||| || | |||       ||||||||*                      [THREAD STARTED (31 WAIT => 56)]
[Total : 19 ] [2015-01-06 18:12:05] | |||* || | |||       |||||||||                      [THREAD STOPED (6 WAIT => 62)]
[Total : 19 ] [2015-01-06 18:12:05] | |||  || | |||       |||||||||*                     [THREAD STARTED (32 WAIT => 18)]
[Total : 19 ] [2015-01-06 18:12:05] | |||  || | |||       |||||*||||                     [THREAD STOPED (28 WAIT => 16)]
[Total : 19 ] [2015-01-06 18:12:05] | |||  || | |||       ||||| ||||*                    [THREAD STARTED (33 WAIT => 81)]
[Total : 19 ] [2015-01-06 18:12:08] | |||  || | ||*       ||||| |||||                    [THREAD STOPED (15 WAIT => 65)]
[Total : 19 ] [2015-01-06 18:12:08] | |||  || | ||        ||||| |||||*                   [THREAD STARTED (34 WAIT => 59)]
[Total : 19 ] [2015-01-06 18:12:11] | |||  || | ||        *|||| ||||||                   [THREAD STOPED (23 WAIT => 50)]
[Total : 19 ] [2015-01-06 18:12:11] | |||  || | ||         |||| ||||||*                  [THREAD STARTED (35 WAIT => 81)]
[Total : 19 ] [2015-01-06 18:12:22] | |||  || | |*         |||| |||||||                  [THREAD STOPED (14 WAIT => 79)]
[Total : 19 ] [2015-01-06 18:12:22] | |||  || | |          |||| |||||||*                 [THREAD STARTED (36 WAIT => 95)]
[Total : 19 ] [2015-01-06 18:12:23] | ||*  || | |          |||| ||||||||                 [THREAD STOPED (5 WAIT => 80)]
[Total : 18 ] [2015-01-06 18:12:23] | ||   || * |          |||| ||||||||                 [THREAD STOPED (11 WAIT => 80)]
[Total : 18 ] [2015-01-06 18:12:23] | ||   ||   |          |||| ||||||||*                [THREAD STARTED (37 WAIT => 100)]
[Total : 18 ] [2015-01-06 18:12:23] | ||   ||   |          |||| |||*|||||                [THREAD STOPED (32 WAIT => 18)]
[Total : 18 ] [2015-01-06 18:12:23] | ||   ||   |          |||| ||| |||||*               [THREAD STARTED (38 WAIT => 16)]
[Total : 19 ] [2015-01-06 18:12:23] | ||   ||   |          |||| ||| ||||||*              [THREAD STARTED (39 WAIT => 52)]
[Total : 19 ] [2015-01-06 18:12:28] | ||   ||   |          |||| *|| |||||||              [THREAD STOPED (29 WAIT => 30)]
[Total : 19 ] [2015-01-06 18:12:28] | ||   ||   |          ||||  || |||||||*             [THREAD STARTED (40 WAIT => 49)]
[Total : 19 ] [2015-01-06 18:12:31] | ||   *|   |          ||||  || ||||||||             [THREAD STOPED (8 WAIT => 88)]
[Total : 19 ] [2015-01-06 18:12:31] | ||    |   |          ||||  || ||||||||*            [THREAD STARTED (41 WAIT => 64)]
[Total : 19 ] [2015-01-06 18:12:32] | ||    |   *          ||||  || |||||||||            [THREAD STOPED (13 WAIT => 89)]
[Total : 19 ] [2015-01-06 18:12:32] | ||    |              ||||  || |||||||||*           [THREAD STARTED (42 WAIT => 75)]
[Total : 19 ] [2015-01-06 18:12:33] | ||    *              ||||  || ||||||||||           [THREAD STOPED (9 WAIT => 90)]
[Total : 19 ] [2015-01-06 18:12:33] | ||                   ||||  || ||||||||||*          [THREAD STARTED (43 WAIT => 90)]
[Total : 19 ] [2015-01-06 18:12:35] | *|                   ||||  || |||||||||||          [THREAD STOPED (3 WAIT => 92)]
[Total : 19 ] [2015-01-06 18:12:35] |  |                   ||||  || |||||||||||*         [THREAD STARTED (44 WAIT => 65)]
[Total : 19 ] [2015-01-06 18:12:35] |  |                   *|||  || ||||||||||||         [THREAD STOPED (24 WAIT => 64)]
[Total : 19 ] [2015-01-06 18:12:35] |  |                    |||  || ||||||||||||*        [THREAD STARTED (45 WAIT => 85)]
[Total : 19 ] [2015-01-06 18:12:39] |  *                    |||  || |||||||||||||        [THREAD STOPED (4 WAIT => 96)]
[Total : 19 ] [2015-01-06 18:12:39] |                       |||  || |||||||||||||*       [THREAD STARTED (46 WAIT => 61)]
[Total : 19 ] [2015-01-06 18:12:39] |                       |||  || |||||*||||||||       [THREAD STOPED (38 WAIT => 16)]
[Total : 18 ] [2015-01-06 18:12:43] *                       |||  || ||||| ||||||||       [THREAD STOPED (1 WAIT => 100)]
[Total : 17 ] [2015-01-06 18:12:54]                         |*|  || ||||| ||||||||       [THREAD STOPED (26 WAIT => 81)]
[Total : 16 ] [2015-01-06 18:12:59]                         * |  || ||||| ||||||||       [THREAD STOPED (25 WAIT => 87)]
[Total : 15 ] [2015-01-06 18:12:59]                           |  |* ||||| ||||||||       [THREAD STOPED (31 WAIT => 56)]
[Total : 14 ] [2015-01-06 18:13:01]                           |  *  ||||| ||||||||       [THREAD STOPED (30 WAIT => 59)]
[Total : 13 ] [2015-01-06 18:13:02]                           *     ||||| ||||||||       [THREAD STOPED (27 WAIT => 80)]
[Total : 12 ] [2015-01-06 18:13:07]                                 |*||| ||||||||       [THREAD STOPED (34 WAIT => 59)]
[Total : 11 ] [2015-01-06 18:13:15]                                 | ||| *|||||||       [THREAD STOPED (39 WAIT => 52)]
[Total : 10 ] [2015-01-06 18:13:17]                                 | |||  *||||||       [THREAD STOPED (40 WAIT => 49)]
[Total : 9  ] [2015-01-06 18:13:26]                                 * |||   ||||||       [THREAD STOPED (33 WAIT => 81)]
[Total : 8  ] [2015-01-06 18:13:32]                                   *||   ||||||       [THREAD STOPED (35 WAIT => 81)]
[Total : 7  ] [2015-01-06 18:13:35]                                    ||   *|||||       [THREAD STOPED (41 WAIT => 64)]
[Total : 6  ] [2015-01-06 18:13:40]                                    ||    ||*||       [THREAD STOPED (44 WAIT => 65)]
[Total : 5  ] [2015-01-06 18:13:40]                                    ||    || |*       [THREAD STOPED (46 WAIT => 61)]
[Total : 4  ] [2015-01-06 18:13:47]                                    ||    *| |        [THREAD STOPED (42 WAIT => 75)]
[Total : 3  ] [2015-01-06 18:13:57]                                    *|     | |        [THREAD STOPED (36 WAIT => 95)]
[Total : 2  ] [2015-01-06 18:14:00]                                     |     | *        [THREAD STOPED (45 WAIT => 85)]
[Total : 1  ] [2015-01-06 18:14:03]                                     *     |          [THREAD STOPED (37 WAIT => 100)]
[Total : 0  ] [2015-01-06 18:14:03]                                           *          [THREAD STOPED (43 WAIT => 90)]
 */