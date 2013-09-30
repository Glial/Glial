<?php

namespace \glial\geometry\polygon;

use \glial\geometry\polygon\Segment;

class Vertex
{
/*------------------------------------------------------------------------------
** This class is almost exactly as described in the paper by Gunter/Greiner
** with some minor additions for segments. Basically it is a node in a doubly
** linked list with a few extra control variables used by the algorithm
** for boolean operations. The only methods in the class are used to encapsulate
** the properties.
*/
        var $x, $y;                 // Coordinates of the vertex
        var $nextV, $prevV;         // References to the next and previous vetices in the polygon
        var $nSeg, $pSeg;           // References to next & previous segments
        var $nextPoly;              // Reference to another polygon in a list
        var $intersect;             // TRUE if vertex is an intersection (with another polgon)
        var $neighbor;              // Ref to the corresponding intersection vertex in another polygon
        var $alpha;                 // Intersection points relative distance from previous vertex
        var $entry;                 // TRUE if intersection is an entry point to another polygon
                                    // FALSE if it is an exit point
        var $checked;               // Boolean - TRUE if vertex has been checked
        var $id;                    // A random ID assigned to make the vertex unique

        /*
        ** Construct a vertex
        */
        function vertex ($x, $y, $xc=0, $yc=0, $d=0,
                         $nextV=NULL, $prevV=NULL, $nextPoly=NULL,
                         $intersect = FALSE, $neighbor=NULL, $alpha=0, $entry=TRUE, $checked=FALSE)
        {
                $this->x = $x; $this->y = $y;
                $this->nextV = $nextV; $this->prevV = $prevV; $this->nextPoly = $nextPoly;
                $this->intersect = $intersect; $this->neighbor = $neighbor; $this->alpha = $alpha;
                $this->entry = $entry; $this->checked = $checked;
                $this->id = mt_rand(0,1000000);
                /*
                ** Create a new segment and set a reference to it. Segments are always
                ** placed after the vertex
                */
                $this->nSeg =& new Segment ($xc, $yc, $d);
                $this->pSeg = NULL;
        }
        /*
        ** Get id
        */
        function id() { return $this->id; }
        /*
        ** Get/Set x/y
        */
        function X() { return $this->x; }
        function setX($x) { $this->x = $x; }
        function Y() { return $this->y; }
        function setY($y) { $this->y = $y; }
        /*
        ** Return contents of a segment. Default is to always return the next
        ** segment, unless previous is specified. The special case is where
        ** the vertex is an intersection, in that case the contents of the
        ** neighbor vertex's next or prev segment is returned. Whether next
        ** or previous is returned depends upon the entry value of the vertex
        ** This method ensures that the correct segment data is returned when
        ** a result polygon is being constructed.
        **
        ** For $g Next == TRUE and Prev == FALSE
        */
        function Xc ($g = TRUE)
        {
                if ($this->isIntersect()) {
                        if ($this->neighbor->isEntry())
                                return $this->neighbor->nSeg->Xc();
                        else
                                return $this->neighbor->pSeg->Xc();
                } else
                        if ($g) return $this->nSeg->Xc(); else return $this->pSeg->Xc();
        }
        function Yc ($g = TRUE)
        {
                if ($this->isIntersect()) {
                        if ($this->neighbor->isEntry())
                                return $this->neighbor->nSeg->Yc();
                        else
                                return $this->neighbor->pSeg->Yc();
                } else
                        if ($g) return $this->nSeg->Yc(); else return $this->pSeg->Yc();
        }
        function d ($g = TRUE)
        {
                if ($this->isIntersect()) {
                        if ($this->neighbor->isEntry())
                                return $this->neighbor->nSeg->d();
                        else
                                return (-1*$this->neighbor->pSeg->d());
                } else
                        if ($g) return $this->nSeg->d(); else return (-1*$this->pSeg->d());
        }
        /*
        ** Set Xc/Yc (Only for segment pointed to by Nseg)
        */
        function setXc ($xc) { $this->nSeg->setXc($xc); }
        function setYc ($yc) { $this->nSeg->setYc($yc); }
        /*
        ** Set/Get the reference to the next vertex
        */
        function setNext (&$nextV){ $this->nextV =& $nextV; }
        function &Next (){ return $this->nextV; }
        /*
        ** Set/Get the reference to the previous vertex
        */
        function setPrev (&$prevV){ $this->prevV =& $prevV; }
        function &Prev (){ return $this->prevV; }
        /*
        ** Set/Get the reference to the next segment
        */
        function setNseg (&$nSeg){ $this->nSeg =& $nSeg; }
        function &Nseg (){ return $this->nSeg; }
        /*
        ** Set/Get the reference to the previous segment
        */
        function setPseg (&$pSeg){ $this->pSeg =& $pSeg; }
        function &Pseg (){ return $this->pSeg; }
        /*
        ** Set/Get reference to the next Polygon
        */
        function setNextPoly (&$nextPoly){ $this->nextPoly =& $nextPoly; }
        function &NextPoly (){ return $this->nextPoly; }
        /*
        ** Set/Get reference to neighbor polygon
        */
        function setNeighbor (&$neighbor){ $this->neighbor =& $neighbor; }
        function &Neighbor (){ return $this->neighbor; }
        /*
        ** Get alpha
        */
        function Alpha (){ return $this->alpha; }
        /*
        ** Test for intersection
        */
        function isIntersect (){ return $this->intersect; }
        /*
        ** Set/Test for checked flag
        */
        function setChecked($check = TRUE)
        {
                $this->checked = $check;
                if ($this->neighbor && !$this->neighbor->isChecked())
                        $this->neighbor->setChecked();
        }
        function isChecked () { return $this->checked; }
        /*
        ** Set/Test entry
        */
        function setEntry ($entry = TRUE){ $this->entry = $entry; }
        function isEntry (){ return $this->entry; }
        /*
        ** Print Vertex used for debugging
        */
        function print_vertex()
        {
                print("(".$this->x.")(".$this->y.") ");
                if ($this->nSeg->d() != 0)
                        print(" c(".$this->nSeg->Xc().")(".$this->nSeg->Yc().")(".$this->nSeg->d().") ");
                if ($this->intersect) {
                        print("Intersection with alpha=".$this->alpha." ");
                        if ($this->entry)
                                print(" Entry");
                        else
                                print(" Exit");}
                if ($this->checked)
                        print(" Checked");
                else
                        print(" Unchecked");
                print("<br>");
        }
} //end of class vertex
