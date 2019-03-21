<?php

namespace Glial\Debug;

class Debug
{

    public $timer = "";
    public $timerdiff = "";
    public $memoryfrom = "";
    public $memory = "";
    public $memorydiff = "";
    public $memory_get_peak_usage = array();
    public $debug = array();
    public $comment = "";
    public $i = 0;

    public function print_table()
    {
        echo "<table class=\"display-tab table table-condensed\" width=\"100%\">
<tr><th>Comment text</th><th>Execution time</th><th>Relative memory</th><th>Absolute memory</th><th>Debug</th></tr>";
        for ($i = 0; $i < $this->i; $i++) {
            $adjust = ($i == ($this->i - 1)) ? " style=\"border:0\"" : "";
            echo "<tr>
<td{$adjust}>" . ($i + 1) . ".&nbsp;" . str_replace(" ", "&nbsp;", htmlentities(trim($this->comment[$i]))) . "</td>
<td{$adjust}>" . number_format($this->timerdiff[$i], 5, ",", ".") . "&nbsp;s</td>
<td{$adjust}>" . round($this->memorydiff[$i] / (1024 * 1024), 2) . "&nbsp;Mo</td>
<td{$adjust}>" . round($this->memory[$i] / (1024 * 1024), 2) . "&nbsp;Mo</td>
<td{$adjust}>in&nbsp;<strong>" . $this->debug[$i]['file'] . "</strong>&nbsp;on&nbsp;line&nbsp;<strong>" . $this->debug[$i]['line'] . "</strong></td>
</tr>";
        }
        echo "</table>";

        echo "Memory used :<b>" . round(memory_get_peak_usage() / (1024 * 1024), 2) . " Mo</b><br /><br />";
    }

    public function save($text = "")
    {
        $this->memory[$this->i] = memory_get_usage();
        list($a, $b) = explode(" ", microtime());
        $this->timer[$this->i] = ((float) $a + (float) $b);
        if ($this->i > 0) {
            if (isset($this->timer[($this->i - 1)])) {
                $this->memorydiff[$this->i] = ($this->memory[$this->i] - $this->memoryfrom[0]);
                if (substr($this->memorydiff[$this->i], 0, 1) != "-") {
                    $this->memorydiff[$this->i] = "+" . $this->memorydiff[$this->i];
                }
                $this->timerdiff[$this->i] = ($this->timer[$this->i] - $this->timer[($this->i - 1)]);
                $this->memory_get_peak_usage[$this->i] = memory_get_peak_usage();
            } else {
                $this->memorydiff[$this->i] = 0;
                $this->timerdiff[$this->i] = "0.00000000";
                $this->memory_get_peak_usage[$this->i] = memory_get_peak_usage();
            }
        } else {
            $this->memorydiff[$this->i] = "0";
            $this->memoryfrom[$this->i] = $this->memory[$this->i];
            $this->timerdiff[$this->i] = "0.00000000";
            $this->memory_get_peak_usage[$this->i] = memory_get_peak_usage();
        }
        $debug_backtrace = debug_backtrace();
        $this->debug[$this->i]['file'] = isset($debug_backtrace[0]['file']) ? $debug_backtrace[0]['file'] : (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "file.php")));
        $this->debug[$this->i]['line'] = isset($debug_backtrace[0]['line']) ? $debug_backtrace[0]['line'] : 0;
        $this->comment[$this->i] = $text;
        $this->i++;
    }

    public function graph()
    {
        $code = "
    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {packages:['corechart']});
      google.setOnLoadCallback(drawChart2);
      function drawChart2()
      {
        var data = google.visualization.arrayToDataTable([
          ['Comment text', 'Seconds'],";
        foreach ($this->memorydiff as $i => $m) {

            $code .= "[
                '" . htmlentities(str_replace("'", "\'", $this->comment[$i])) . "',
                " . ($this->timerdiff[$i]) . ",
                ],";
        }
        $code = substr($code, 0, -1);
        $code .= "]);

        var options = {
          title: 'Execution time'
        };

        var chart2 = new google.visualization.LineChart(document.getElementById('chart_memory'));
        chart2.draw(data, options);
      }
</script>
    <div id='chart_memory'></div>";

        return $code;
    }

    public function graph2()
    {
        $code = "
    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {packages:['corechart']});
      google.setOnLoadCallback(drawChart);
      function drawChart()
      {
        var data = google.visualization.arrayToDataTable([
          ['Comment text', 'Memory Bytes','Peak usage'],";
        foreach ($this->memorydiff as $i => $m) {
            $code .= "[
                '" . htmlentities(str_replace("'", "\'", $this->comment[$i])) . "',
                " . str_replace("+", null, str_replace("-", null, $m)) / 1024 / 1024 . ",
                " . $this->memory_get_peak_usage[$i] / 1024 / 1024 . "
                ],";
        }
        $code = substr($code, 0, -1);
        $code .= "]);

        var options = {
          title: 'Memory'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_execution_time'));
        chart.draw(data, options);
      }
</script>
    <div id='chart_execution_time'></div>";

        return $code;
    }

}
