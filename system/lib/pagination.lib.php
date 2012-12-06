<?php

/****************************************************************************
* pagination.class.php
*
* version 1.0
*
* This script can be used to generate dynamically pagination for any list of rows or items from MySQL Database
* 
* ----------------------------------------------------------------
*
* A demo example is included in demo folder.
*
* Copyright (C) 2012 Fakhri Alsadi <fakrhi.s@hotmail.com>
*
*******************************************************************************/


class pagination{

private $rows=10;					//The rows per page.
private $table_rows_count;			//The total rows to show.
private $pages_count;				//The pages number to generate.
private $current_page=1;			//The number of the current page displayed.
private $start=0;					//The start of the mysql limit keyword.
private $printed_pages=10;			//Number of pages to display.
private $parameter='page';			//The pgination paramerter name.
private $url_rewrite='';			//The URL rewrite structure.
private $current_parameters;		//The existing query string parameters
private $prev_page;					//The previous page.
private $next_page;					//The next page
private $run_count=0;				//Times that the funtion run() invoked.
private $show_pages_number=true;	//Pages number box status.
private $show_go_button=true;		//Showing Go button status.
private $show_prev_next=true;		//Showing pervious and next page buttons status.
private $show_first_last=true;		//Showing first and last page buttons status.
private $direction='';				//The direction of pagination.
private $alignment='';				//The alignment of pagination.
private $go_button_text= "Go";		//Set text of Go button
private $pages_number_text= "Pages of"; //Set text of pages number
private $page_text= "page"; //Set text of pages number
private $next_page_text= "»";		//Set text of next page button
private $prev_page_text= "«";		//Set text of previous button
private $first_page_text= "« First";	//Set text of first page button
private $last_page_text= "Last »";		//Set text of last button
private $invalid_page_number_text= "Please input a valid page number!";		//Set text of invalid page number error
private $output='';					//The next page
private $url;					//url de la page sans le parametre page

public function pagination($url, $current_page=1,$table_rows_count='',$rows=10,$printed_pages=10)
{
	
	$this->url=$url;
	$this->current_page=$current_page;
	$this->table_rows_count= $table_rows_count;
	$this->rows=$rows;
	$this->printed_pages= $printed_pages;
}


//------------------------------------------------------------------------

private function run()
{
	
	if($this->run_count == 0)
	{
		$this->pages_count = ceil($this->table_rows_count/$this->rows);
		

		if( $this->current_page < 1 )
		{
			$this->current_page = 1;
		}
		else if($this->current_page > $this->pages_count)
		{
			$this->current_page = $this->pages_count;	
		}
		
		$this->prev_page = $this->current_page - 1;
		$this->next_page = $this->current_page + 1;
		
		
		if ($this->table_rows_count === 0)
		{
			$this->current_page = 1;
		}
		
		$this->start = (($this->current_page - 1) * $this->rows);
			
	}
	
	$this->run_count ++;

}


//------------------------------------------------------------------------



public function print_pagination()
{
	
	$this->run();
	
	$options="";
	if($this->direction!='')
	$options= $options . ' dir="' . $this->direction . '" ';
	
	if($this->alignment!='')
	$options = $options . ' align="' . $this->alignment . '" ';
	$this->output .= '<div class="pagination" ' . $options . ' >';
	
	
	
	if($this->current_page > 1)
	{
		if($this->show_first_last)
			$this->output .= $this->get_page_html(1,$this->first_page_text);
		if($this->show_prev_next)
			$this->output .= $this->get_page_html($this->prev_page,$this->prev_page_text );
	}
	
	$stop=0;
	$half_pages="";
	$frompage="";
	$topage ="";
	
	if($this->printed_pages>1)
	{
	
		$half_pages= intval( $this->printed_pages/2 );
		if($this->printed_pages % $half_pages == 0)
		$half_pages--;
		
		$frompage = ( $this->current_page - $half_pages );
		$topage = ( $this->current_page + $this->printed_pages );
		
	}
	
	if($this->printed_pages==1)
	{
		$frompage = $this->current_page;
		$topage = $this->current_page;
	}	

	if($this->printed_pages==3)
	{
		$frompage = $this->current_page - 1;
		$topage = $this->current_page + 1 ;
	}	


	if($this->current_page > ( $this->pages_count - ($this->printed_pages - $half_pages ) ))
	{
		$topage =  $this->pages_count ;
		$frompage= ( $this->pages_count - $this->printed_pages +1 );
	}
	
	for($i = $frompage ; $i <= $topage ; $i++ )
	{

		if($i>0 && $i <= $this->pages_count && $this->pages_count>1)
		{
			if( $i == $this->current_page )
				$this->output .=  $this->get_current_page_html($i);
			else
				$this->output .=  $this->get_page_html($i,$i);
				
			$stop++;
			if($stop >= $this->printed_pages)
			break;
		}

	}
	
	
	if($this->current_page >= 1 && $this->pages_count>1 && $this->current_page < $this->pages_count)
		{
			if( $this->show_prev_next )
				$this->output .= $this->get_page_html( $this->next_page, $this->next_page_text );
			if( $this->show_first_last )
				$this->output .= $this->get_page_html( $this->pages_count, $this->last_page_text );
		}
	
	
	
	$this->output .= $this->get_pages_number_html();
	$this->output .= $this->get_go_button_html();
	$this->output .= '</div>'; 
	
	return $this->output;

}


//------------------------------------------------------------------------

private function get_page_html($page,$name)
{
	return '<a class="button btGreyLite overlayW btalpha" href="'.$this->get_page_url($page).'">'.$name.'</a>';
}
	
//------------------------------------------------------------------------

private function get_current_page_html($name)
{

	return '<span class="button btBlueTest overlayW btalpha">'.$name.'</span>';
}
	


private function random_id()
	{
		srand ((double) microtime( )*1000000000);
		return rand();
	}

//------------------------------------------------------------------------
private function get_go_button_html()
	{
		
		$id='gopage' . $this->random_id();
		

		$url_structure = $this->url_rewrite;

		
		if($this->show_go_button)
		$this->output .= '<script>
		function fun_'. $id .'()
			{
				var page=parseInt(document.getElementById(\''. $id .'\').value);
				var url = "' . $url_structure . '";
				if(page>0)
				{
					window.location = "'.$this->url.'/page:"+page
				}else
				{
					alert(\'' . $this->invalid_page_number_text . '\');
				}
			}
		function keypress_'. $id .'(event)
		{
   			var key=event.keyCode;

     			if(key == 13)
     			{
	     			fun_'. $id .'();
	     			return false; 
   				}
		}
		</script><span class="button btGreyLite overlayW spaninput">'.$this->page_text.' : <input onkeydown="return keypress_'. $id .'(event)" type="text" name="' . $id . '" id="' . $id . '" size="3" value="' . $this->current_page . '"  ></span>
		<a class="button btBlueTest overlayW btalpha" onclick="fun_'. $id .'()" href="#">' . $this->go_button_text .'</a>'; 
	}	
	
//------------------------------------------------------------------------

private function get_pages_number_html()
{
	if($this->show_pages_number)
	{
		$this->output .= '<span class="button btBlueTest overlayW btalpha">' . $this->current_page . ' ' . $this->pages_number_text .  ' ' . $this->pages_count . '</span>'; 
	}
}	
//------------------------------------------------------------------------


public function get_page_url($page)
{
	return $this->url . "/" .  "page:" . $page;
	
}
	



//------------------------------------------------------------------------


public function get_sql_limit()
{
	$this->run();
	return array($this->start,$this->rows);
}
	
//------------------------------------------------------------------------

public function get_rows()
	{
		return $this->rows;
	}

//------------------------------------------------------------------------

public function set_rows($rows)
	{
		if(intval($rows)>0)
		{
			$this->rows=$rows;
			$this->run_count=0;
		}
	}


//------------------------------------------------------------------------

public function get_printed_pages()
	{
	 	return $this->printed_pages;
	}

//------------------------------------------------------------------------

public function set_printed_pages($printed_pages)
	{
		if(intval($printed_pages)>0)
		{
			$this->printed_pages=$printed_pages;
			$this->run_count=0;
		}
	}

//------------------------------------------------------------------------

public function get_parameter_name()
	{
	 	return $this->parameter;
	}

//------------------------------------------------------------------------

public function set_parameter_name($parameter)
	{
		$this->parameter=$parameter;
		$this->run_count=0;
	}
//------------------------------------------------------------------------

public function get_url_rewrite()
	{
	 	return $this->url_rewrite;
	}

//------------------------------------------------------------------------

public function set_url_rewrite($url_rewrite)
	{
		$this->url_rewrite=$url_rewrite;
		$this->run_count=0;
	}



//------------------------------------------------------------------------

public function set_filter($filter)
	{
		$this->filter=$filter;
		$this->run_count=0;
	}
	


//------------------------------------------------------------------------

public function set_go_button_text($go_button_text)
	{
		$this->go_button_text=$go_button_text;
		
	}



//------------------------------------------------------------------------

public function set_pages_number_text($pages_number_text)
	{
		$this->pages_number_text=$pages_number_text;
		$this->run_count=0;
	}


//------------------------------------------------------------------------

public function set_next_page_text($next_page_text)
	{
		$this->next_page_text=$next_page_text;
		$this->run_count=0;
	}		


//------------------------------------------------------------------------

public function set_prev_page_text($prev_page_text)
	{
		$this->prev_page_text=$prev_page_text;
		$this->run_count=0;
	}	


//------------------------------------------------------------------------

public function set_first_page_text($first_page_text)
{
	$this->first_page_text=$first_page_text;
}	



//------------------------------------------------------------------------

public function set_last_page_text($last_page_text)
	{
		$this->last_page_text=$last_page_text;
		$this->run_count=0;
	}	



//------------------------------------------------------------------------

public function set_invalid_page_number_text($invalid_page_number_text)
	{
		$this->invalid_page_number_text=$invalid_page_number_text;
		$this->run_count=0;
	}	


//------------------------------------------------------------------------

public function set_alignment($alignment)
	{
		$this->alignment=$alignment;
		$this->run_count=0;
	}	



//------------------------------------------------------------------------

public function set_direction($direction)
	{
		$this->direction=$direction;
		$this->run_count=0;
	}
	
//------------------------------------------------------------------------

public function show_pages_number($value)
	{
		
		if($value)
		$this->show_pages_number = true;
		else
		$this->show_pages_number = false;
		
		$this->run_count=0;
		
	}
//------------------------------------------------------------------------

public function can_show_pages_number()
	{
		return $this->show_pages_number;
	}

//------------------------------------------------------------------------

public function show_go_button($value)
{
	
	if($value)
	{
		$this->show_go_button = true;
	}
	else
	{
		$this->show_go_button = false;
	}
	
	$this->run_count=0;
	
}

//------------------------------------------------------------------------

public function can_show_go_button()
	{
		return $this->show_go_button;
	}

//------------------------------------------------------------------------

public function show_prev_next($value)
{
	if($value)
	{
		$this->show_prev_next = true;
	}
	else
	{
		$this->show_prev_next = false;
	}
	$this->run_count=0;
	
}

//------------------------------------------------------------------------

public function can_show_prev_next()
	{
		return $this->show_prev_next;
	}

//------------------------------------------------------------------------

public function show_first_last($value)
	{
		
		if($value)
		$this->show_first_last = true;
		else
		$this->show_first_last = false;
		
		$this->run_count=0;
		
	}

//------------------------------------------------------------------------

public function can_show_first_last()
{
	return $this->show_first_last;
}


//------------------------------------------------------------------------

}

?>
