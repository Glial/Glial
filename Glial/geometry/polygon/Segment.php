<?php
/*------------------------------------------------------------------------------
** File:        vertex.php
** Description: PHP class for a polygon vertex. Used as the base object to
**              build a class of polygons.
** Version:     1.6
** Author:      Brenor Brophy
** Email:       brenor dot brophy at gmail dot com
** Homepage:    www.brenorbrophy.com
**------------------------------------------------------------------------------
** COPYRIGHT (c) 2005-2010 BRENOR BROPHY
**
** The source code included in this package is free software; you can
** redistribute it and/or modify it under the terms of the GNU General Public
** License as published by the Free Software Foundation. This license can be
** read at:
**
** http://www.opensource.org/licenses/gpl-license.php
**
** This program is distributed in the hope that it will be useful, but WITHOUT
** ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
** FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
**------------------------------------------------------------------------------
**
** Based on the paper "Efficient Clipping of Arbitary Polygons" by Gunther
** Greiner (greiner at informatik dot uni-erlangen dot de) and Kai Hormann
** (hormann at informatik dot tu-clausthal dot de), ACM Transactions on Graphics
** 1998;17(2):71-83.
**
** Available at:
**
**      http://www2.in.tu-clausthal.de/~hormann/papers/Greiner.1998.ECO.pdf
**
** Another useful site describing the algorithm and with some example
** C code by Ionel Daniel Stroe is at:
**
**              http://davis.wpi.edu/~matt/courses/clipping/
**
** The algorithm is extended by Brenor Brophy to allow polygons with
** arcs between vertices.
**
** Rev History
** -----------------------------------------------------------------------------
** 1.0  08/25/2005      Initial Release
** 1.1  09/04/2005      Added software license language to header comments
** 1.2  09/07/2005      Minor fix to polygon.php - no change to this file
** 1.3  04/16/2006      Minor fix to polygon.php - no change to this file
** 1.4  03/19/2009      Minor change to comments in this file. Significant
**                      change to polygon.php
** 1.5  07/16/2009      No change to this file
** 1.6  15/05/2010      No change to this file
*/

namespace \glial\geometry\polygon;

class Segment
{
/*------------------------------------------------------------------------------
** This class contains the information about the segments between vetrices. In
** the original algorithm these were just lines. In this extended form they
** may also be arcs. By creating a separate object for the segment and then
** referencing to it forward & backward from the two vertices it links it is
** easy to track in various directions through the polygon linked list.
*/
        var     $xc, $yc;               // Coordinates of the center of the arc
        var $d;                         // Direction of the arc, -1 = clockwise, +1 = anti-clockwise,
                                        // A 0 indicates this is a line
        /*
        ** Construct a segment
        */
        function segment ($xc=0, $yc=0, $d=0)
        {
                $this->xc = $xc; $this->yc = $yc; $this->d = $d;
        }
        /*
        ** Return the contents of a segment
        */
        function Xc () { return $this->xc ;}
        function Yc () { return $this->yc ;}
        function d () { return $this->d ;}
        /*
        ** Set Xc/Yc
        */
        function setXc ($xc) { $this->xc = $xc; }
        function setYc ($yc) { $this->yc = $yc; }
} // end of class segment
