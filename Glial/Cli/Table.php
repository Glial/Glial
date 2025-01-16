<?php

namespace Glial\Cli;

class Table
{
    const DATA       = "data";
    const HEADER     = "header";
    const HR_TOP     = '1';
    const HR_LINE    = '2';
    const HR_BOTTOM  = '3';
    const HORIZONTAL = true;
    const VERTICAL   = false;

    var $style       = 1;
    var $borderStyle = [
        '0' => array('╔', '╗', '╚', '╝', '╬', '╦', '╣', '╩', '╠', '║', '═'),
        '1' => array('┌', '┐', '└', '┘', '┼', '┬', '┤', '┴', '├', '│', '─'),
        '2' => array('+', '+', '+', '+', '+', '+', '+', '+', '+', '|', '-'),
        '3' => '           ',
    ];
    var $borderMerge = ['╢', '╖', '╡', '╢', '╖', '╕', '╜', '╛', '╞', '╟', '╨', '╤', '╥', '╙', '╓', '╫', '╪'];
    var $_border     = array(
        'top' => true,
        'right' => true,
        'bottom' => true,
        'left' => true,
        'inner' => true
    );
    var $borderColor    = "blue";
    var $data           = array();
    var $data_type      = array();
    var $height         = array();
    var $cellByLine     = array();
    var $maxRowInTable;
    var $maxLine;
    var $maxLengthByCol = array();
    var $padding        = 1;
    var $dataByCol      = array();

    public function __construct($style = 1)
    {
        $this->style = $style;
    }

    public function addHeader($header)
    {
        $this->addLine($header, Table::HEADER);
    }

    public function addLine(array $line, $data_type = Table::DATA)
    {
        if ($this->checkLine($line)) {
            $this->data[]      = $line;
            $this->data_type[] = $data_type;

            $max = 1;

            foreach ($line as $elem) {

                //var_dump($elem);

                $count = substr_count($elem, "\n");

                if ($count >= $max) {
                    $max = $count + 1;
                }
            }

            $this->height[] = $max;
        }
    }

    public function addData($data)
    {
        foreach ($data as $line) {
            $this->addLine($line);
        }
    }

    /**
     * Set which borders shall be shown.
     * @param array $visibility Visibility settings.
     *                          Allowed keys: left, right, top, bottom, inner
     *
     * @return void
     * @see    $_border
     */
    function setBorder($visibility)
    {
        $this->_border = array_merge($this->_border, array_intersect_key($visibility, $this->_border));
    }

    private function checkLine($line)
    {
        foreach ($line as $elem) {
            if (!empty($elem) && !is_string($elem) && !is_int($elem) && !is_float($elem)) {
                throw new \Exception("GLI-015 : \$line must be an array of string/int : '".$elem."'");
            }
        }

        return true;
    }

    public function display()
    {
        $this->calcul();

        //print_r($this->height);

        $tab = $this->hr(Table::HR_TOP);
        $tab .= $this->content();
        $tab .= $this->hr(Table::HR_BOTTOM);

        return $tab;
    }

    public function addPadding($padding)
    {
        $this->padding = intval($padding);
    }

    private function calcul()
    {

        //possible to report in add line to prevent second parse
        $this->maxLine = 0;
        foreach ($this->data as $line) {
            $cellByLine[] = count($line);
            $this->maxLine++;
        }
        //end of report

        $this->maxRowInTable = max($cellByLine);


        for ($i = 0; $i < $this->maxRowInTable; $i++) {
            $colone            = array_column($this->data, $i);
            $this->dataByCol[] = $colone;

            $this->maxLengthByCol[] = max(array_map(function ($str) {
                    //echo $str. " -- ". mb_strlen(Color::strip($str), "utf8").PHP_EOL;

                    $max   = 0;
                    $lines = explode("\n", $str);

                    foreach ($lines as $line) {
                        $nb_char = mb_strlen(Color::strip($line), "utf8");

                        if ($nb_char > $max) {
                            $max = $nb_char;
                        }
                    }


                    return $max;
                }, $colone));
        }
    }

    private function hr($type = Table::HR_TOP)
    {
        switch ($type) {
            case Table::HR_TOP: $asci = array(0, 5, 1, 10);
                break;
            case Table::HR_BOTTOM: $asci = array(2, 7, 3, 10);
                break;
            case Table::HR_LINE: $asci = array(8, 4, 6, 10);
                break;

            default:
                throw new \DomainException("GLI-016 : hr type unknow : ".$type);
        }

        $tab       = $this->borderStyle[$this->style][$asci[0]];
        $bordertop = array();

        foreach ($this->maxLengthByCol as $colLength) {
            $bordertop[] = str_repeat($this->borderStyle[$this->style][$asci[3]], $colLength + $this->padding * 2);
        }

        $tab .= implode($this->borderStyle[$this->style][$asci[1]], $bordertop);
        $tab .= $this->borderStyle[$this->style][$asci[2]];

        return $tab.PHP_EOL;
    }

    private function content($type = 1)
    {
        $tab = '';

        $j = 0;
        foreach ($this->data as $line) {


            if ($this->data_type[$j] === self::HR_LINE) {
                $tab .= $this->hr(Table::HR_LINE);
            } else {


                $height = $this->height[$j];

                $h = 0;

                do {
                    $tab        .= $this->borderStyle[$this->style][9];
                    $borderData = array();

                    $i = 0;
                    foreach ($line as $cell) {
                        $cell_lines = explode("\n", $cell);

                        if (!isset($cell_lines[$h])) {
                            $cell = "";
                        } else {
                            $cell = str_repeat(" ", $this->padding).$cell_lines[$h].str_repeat(" ", $this->padding);
                        }


                        $borderData[] = str_pad($cell, strlen($cell) - mb_strlen(Color::strip($cell), "utf8") + $this->maxLengthByCol[$i] + $this->padding * 2);

                        $i++;
                    }

                    $tab .= implode($this->borderStyle[$this->style][9], $borderData);
                    $tab .= $this->borderStyle[$this->style][9];
                    $tab .= PHP_EOL;




                    $h++;
                } while ($height > $h);
            }

            if ($this->data_type[$j] === Table::HEADER) {
                $tab .= $this->hr(Table::HR_LINE);
            }




            $j++;
        }
        return $tab;
    }

    public function flushAll()
    {
        $this->data           = array();
        $this->data_type      = array();
        $this->cellByLine     = array();
        $this->maxRowInTable  = 0;
        $this->maxLine        = 0;
        $this->maxLengthByCol = array();
    }

    public function addHr()
    {
        $this->addLine(array(), self::HR_LINE);
    }
}
