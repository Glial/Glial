<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\Controller;

trait Test {
    function win() {
        $this->view = false;
        $win = new Window('Test windows', "SFRHSFGH x xfhg xfgh xfgh xgfh gxfh xfxgfh x xfgh xfgh xfgh xfgh : failed to open stream: No such file or directory in \n"
                . "ok test msg :\n"
                . "\n"
                . "[[INPUT]]"
                . "\n");
    }
}