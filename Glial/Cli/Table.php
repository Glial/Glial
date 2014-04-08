<?php

namespace Glial\Cli;


class Table
{

    const DATA = "data";
    const HEADER = "header";
    const HR_TOP = '1';
    const HR_LINE = '2';
    const HR_BOTTOM = '3';
    const HORIZONTAL = true;
    const VERTICAL = false;

    var $style = 1;
    var $borderStyle = [
        '0' => array('╔', '╗', '╚', '╝', '╬', '╦', '╣', '╩', '╠', '║', '═'),
        '1' => array('┌', '┐', '└', '┘', '┼', '┬', '┤', '┴', '├', '│', '─'),
        '2' => array('+', '+', '+', '+', '+', '+', '+', '+', '+', '|', '-'),
        '3' => '           ',
    ];
    var $borderMerge = ['╢', '╖', '╡', '╢', '╖', '╕', '╜', '╛', '╞', '╟', '╨', '╤', '╥', '╙', '╓', '╫', '╪'];
    var $_border = array(
        'top' => true,
        'right' => true,
        'bottom' => true,
        'left' => true,
        'inner' => true
    );
    
    var $borderColor = "blue";
    var $data = array();
    var $data_type = array();
    var $cellByLine = array();
    var $maxRowInTable;
    var $maxLine;
    var $maxLengthByCol = array();

    public function __construct($style = 1)
    {
        $this->style = $style;
    }

    public function addHeader($header)
    {
        $this->addLine($header, Table::HEADER);
    }

    public function addLine($line, $data_type = Table::DATA)
    {
        if ($this->checkLine($line)) {
            $this->data[] = $line;
            $this->data_type[] = $data_type;
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
            if (!is_string($elem)) {
                throw new Exception("GLI-015 : \$line must be an array of string");
            }
        }

        return true;
    }

    public function display()
    {
        $this->calcul();


        $tab = $this->hr(Table::HR_TOP);
        $tab .= $this->content();
        $tab .= $this->hr(Table::HR_BOTTOM);


        return $tab;
    }

    private function calcul()
    {
        $mb_length = function ($str) {
            return mb_strlen(Color::strip($str));
        };

        //possible to report in add line to prevent second parse
        $this->maxLine = 0;
        foreach ($this->data as $line) {
            $cellByLine[] = count($line);
            $this->maxLine++;
        }
        //end of report

        $this->maxRowInTable = max($cellByLine);


        for ($i = 0; $i < $this->maxRowInTable; $i++) {
            $colone = array_column($this->data, $i);
            $this->dataByCol[] = $colone;

            $this->maxLengthByCol[] = max(array_map($mb_length, $colone));
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
                throw new \DomainException("GLI-016 : hr type unknow : " . $type);
                brea;
        }

        $tab = $this->borderStyle[$this->style][$asci[0]];
        $bordertop = array();

        foreach ($this->maxLengthByCol as $colLength) {
            $bordertop[] = str_repeat($this->borderStyle[$this->style][$asci[3]], $colLength);
        }

        $tab .= implode($this->borderStyle[$this->style][$asci[1]], $bordertop);
        $tab .= $this->borderStyle[$this->style][$asci[2]];

        return $tab . PHP_EOL;
    }

    private function content($type = 1)
    {
        $tab = '';


        $j = 0;
        foreach ($this->data as $line) {
            $tab .= $this->borderStyle[$this->style][9];
            $borderData = array();

            $i = 0;
            foreach ($line as $cell) {
                $borderData[] = str_pad($cell, $this->maxLengthByCol[$i]);
                $i++;
            }

            $tab .= implode($this->borderStyle[$this->style][9], $borderData);
            $tab .= $this->borderStyle[$this->style][9];
            $tab .= PHP_EOL;

            if ($this->data_type[$j] === Table::HEADER) {

                $tab .= $this->hr(Table::HR_LINE);
            }

            $j++;
        }
        return $tab;
    }

    public function flushAll()
    {
        $this->data = array();
        $this->data_type = array();
        $this->cellByLine = array();
        $this->maxRowInTable = 0;
        $this->maxLine = 0;
        $this->maxLengthByCol = array();
    }

}
