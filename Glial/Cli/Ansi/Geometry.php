<?php

/*
 * Take it. use it.
 * HUMAN KNOWLEDGE BELONGS TO THE WORLD
 */

namespace Glial\Cli\Ansi;

trait Geometry
{

    public function segment(Point $a, Point $b)
    {
        $x1 = $a->getX();
        $x2 = $b->getX();
        $y1 = $a->getY();
        $y2 = $b->getY();

        $dx = $x2 - $x1;

        if ($dx != 0) {
            if ($dx > 0) {
                $dy = $y2 - $y1;

                if ($dy > 0) {

                    if ($dx >= $dy) {
                        $e = $dx;
                        $dx = $dx * 2;
                        $dy = $dy * 2;

                        while (true) {
                            $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                            echo "@";

                            $x1 = $x1 + 1;
                            if ($x1 === $x2) {
                                break;
                            }
                            $e = $e - $dy;
                            if ($e < 0) {
                                $y1 = $y1 + 1;
                                $e = $e + $dx;
                            }
                        }
                    } else {
                        $e = $dy;
                        $dy = $dy * 2;
                        $dx = $dx * 2;

                        while (true) {
                            $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                            echo "@";

                            $y1 = $y1 + 1;
                            if ($y1 === $y2) {
                                break;
                            }
                            $e = $e - $dx;
                            if ($e < 0) {
                                $x1 = $x1 + 1;
                                $e = $e + $dy;
                            }
                        }
                    }
                } else {
                    if ($dx >= -$dy) {
                        $e = $dx;
                        $dx = $dx * 2;
                        $dy = $dy * 2;


                        while (true) {
                            $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                            echo "@";

                            $x1 = $x1 + 1;
                            if ($x1 === $x2) {
                                break;
                            }

                            $e = $e + $dy;

                            if ($e < 0) {
                                $y1 = $y1 - 1;
                                $e = $e + $dx;
                            }
                        }
                    } else {
                        $e = $dy;
                        $dy = $dy * 2;
                        $dx = $dx * 2;

                        while (true) {
                            $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                            echo "@";

                            $y1 = $y1 - 1;
                            if ($y1 === $y2) {
                                break;
                            }

                            $e = $e + $dx;
                            if ($e > 0) {
                                $x1 = $x1 + 1;
                                $e = $e + $dy;
                            }
                        }
                    }
                }
            } else {
                //dx < 0

                $dy = $y2 - $y1;

                if ($dy != 0) {
                    if ($dy > 0) {
                        if (-$dx > $dy) {
                            $e = $dx;
                            $dx = $dx * 2;
                            $dy = $dy * 2;

                            while (true) {
                                $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                                echo "@";

                                $x1 = $x1 - 1;
                                if ($x1 == $x2) {
                                    break;
                                }

                                $e = $e + $dy;

                                if ($e >= 0) {
                                    $y1 = $y1 + 1;
                                    $e = $e + $dx;
                                }
                            }
                        } else {

                            $e = $dy;
                            $dy = $dy * 2;
                            $dx = $dx * 2;

                            while (true) {
                                $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                                echo "@";

                                $y1 = $y1 + 1;

                                if ($y1 === $y2) {
                                    break;
                                }

                                $e = $e + $dx;

                                if ($e <= 0) {
                                    $x1 = $x1 - 1;
                                    $e = $e + $dy;
                                }
                            }
                        }
                    } else {
                        // dy < 0 (et dx < 0)
                        if ($dx <= $dy) {
                            $e = $dx;
                            $dx = $dx * 2;
                            $dy = $dy * 2;

                            while (true) {
                                $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                                echo "@";

                                $x1 = $x1 - 1;

                                if ($x1 === $x2) {
                                    break;
                                }

                                $e = $e - $dy;

                                if ($e >= 0) {
                                    $y1 = $y1 - 1;
                                    $e = $e + $dx;
                                }
                            }
                        } else {
                            $e = $dy;
                            $dy = $dy * 2;
                            $dx = $dx * 2;

                            while (true) {
                                $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                                echo "@";

                                $y1 = $y1 - 1;

                                if ($y1 === $y2) {
                                    break;
                                }

                                $e = $e - $dx;

                                if ($e >= 0) {
                                    $x1 = $x1 - 1;
                                    $e = $e + $dy;
                                }
                            }
                        }
                    }
                } else {
                    // vecteur horizontal vers la gauche
                    while (true) {
                        $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                        echo "@";

                        $x1 = $x1 - 1;

                        if ($x1 == $x2) {
                            break;
                        }
                    }
                }
            }
        } else {
            // dx = 0
            $dy = $y2 - $y1;

            if ($dy !== 0) {
                if ($dy > 0) {

                    while (true) {
                        $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                        echo "@";

                        $y1 = $y1 + 1;
                        if ($y1 === $y2) {
                            break;
                        }
                    }
                } else {
                    while (true) {
                        $this->moveCursorTo((int) round($x1, 0), (int) round($y1, 0));
                        echo "@";

                        $y1 = $y1 - 1;
                        if ($y1 === $y2) {
                            break;
                        }
                    }
                }
            }
        }

        $this->moveCursorTo($b->getX(), $b->getY());
        echo "@";
    }

    public function rectangle(Point $a, Point $b)
    {
        $this->segment(new Point($a->getX(), $a->getY()), new Point($a->getX(), $b->getY()));
        $this->segment(new Point($a->getX(), $a->getY()), new Point($b->getX(), $a->getY()));
        $this->segment(new Point($a->getX(), $b->getY()), new Point($b->getX(), $b->getY()));
        $this->segment(new Point($b->getX(), $a->getY()), new Point($b->getX(), $b->getY()));
    }

    public function triangle(Point $a, Point $b, Point $c)
    {
        $this->segment(new Point($a->getX(), $a->getY()), new Point($b->getX(), $b->getY()));
        $this->segment(new Point($b->getX(), $b->getY()), new Point($c->getX(), $c->getY()));
        $this->segment(new Point($c->getX(), $c->getY()), new Point($a->getX(), $a->getY()));
    }

    public function circle(Point $a, $r)
    {

        $xc = $a->getX();
        $yc = $a->getY();

        $x = 0;
        $y = $r;
        $d = $r - 1;

        while ($y >= $x) {

            $this->moveCursorTo($xc + $x, $yc + $y);
            echo "@";
            
            
            $this->moveCursorTo($xc + $y, $yc + $x);
            echo "@";
            $this->moveCursorTo($xc - $x, $yc + $y);
            echo "@";
            $this->moveCursorTo($xc - $y, $yc + $x);
            echo "@";
            $this->moveCursorTo($xc + $x, $yc - $y);
            echo "@";
            $this->moveCursorTo($xc + $y, $yc - $x);
            echo "@";
            $this->moveCursorTo($xc - $x, $yc - $y);
            echo "@";
            $this->moveCursorTo($xc - $y, $yc - $x);
            echo "@";

            if ($d >= 2 * $x) {
                $d -= 2 * $x + 1;
                $x++;
            } else if ($d < 2 * ($r - $y)) {
                $d += 2 * $y - 1;
                $y--;
            } else {
                $d += 2 * ($y - $x - 1);
                $y--;
                $x++;
            }
        }
    }
    
    
    public function fillCircle(Point $a, $r)
    {
        for($i = 0; $i <= $r; $i++)
        {
            $this->circle($a, $i);
        }
    }

}
