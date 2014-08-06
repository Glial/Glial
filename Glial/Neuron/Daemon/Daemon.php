<?php


/*
 * 
 * Daemon
 * 
 * first case
 * start daemon example : glial daemon start controller-action-param
 * 
 * second case :
 * /etc/init.d/gliald controller-action-param start
 * 
 * 
 * special case :
 * /etc/init.d/gliald all {start|stop|restart}
 * 
 * 
 * 
 *  
 */


namespace Glial\Neuron\Controller\Daemon;

trait Daemon 
{
    function start()
    {
        
    }
    
    
    function restart()
    {
        $this->stop();
        $this->start();
    }
    
    
    function stop()
    {
        
    }
}