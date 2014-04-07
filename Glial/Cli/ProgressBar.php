<?php

namespace Glial\Cli;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class ProgressBar
{

    public static function showStatus($done, $total, $size = false)
    {

        static $start_time;

        // if we go over our bound, just ignore it
        if ($done > $total)
            return;

        if (empty($start_time))
            $start_time = time();
        $now = time();

        $perc = (double) ($done / $total);

        $bar = floor($perc * $size);

        $status_bar = "\r\r";

        $disp = number_format($perc * 100, 0);

        $status_bar.="$disp% [";

        $status_bar.=str_repeat("=", $bar);
        if ($bar < $size) {
            $status_bar.=">";
            $status_bar.=str_repeat(" ", $size - $bar);
        } else {
            $status_bar.="=";
        }

        $status_bar.= "] " . $done . "/" . $total;

        $rate = ($now - $start_time) / $done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);

        $elapsed = $now - $start_time;

        $status_bar.= " remaining: " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";

        echo $status_bar . " ";

        //flush();
        // when done, send a newline
        if ($done == $total) {
            echo "\n";
        }
    }

}
