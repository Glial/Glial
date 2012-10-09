<?php

class export {

	/**
	 * Output handler for all exports, if needed buffering, it stores data into
	 * $dump_buffer, otherwise it prints thems out.
	 *
	 * @param string  $line  the insert statement
	 * @return  bool    Whether output succeeded
	 */
	static function export_output_handler($line) {
		global $time_start, $dump_buffer, $dump_buffer_len, $save_filename;

		
		
		/*
		// Kanji encoding convert feature
		if ($GLOBALS['output_kanji_conversion'])
		{
			//$line = PMA_kanji_str_conv($line, $GLOBALS['knjenc'], isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : '');
		}
		// If we have to buffer data, we will perform everything at once at the end
		if ($GLOBALS['buffer_needed'])
		{

			$dump_buffer .= $line;
			if ($GLOBALS['onfly_compression'])
			{

				$dump_buffer_len += strlen($line);

				if ($dump_buffer_len > $GLOBALS['memory_limit'])
				{
					if ($GLOBALS['output_charset_conversion'])
					{
						//$dump_buffer = PMA_convert_string('utf-8', $GLOBALS['charset_of_file'], $dump_buffer);
					}
					// as bzipped
					if ($GLOBALS['compression'] == 'bzip2' && @function_exists('bzcompress'))
					{
						$dump_buffer = bzcompress($dump_buffer);
					}
					elseif ($GLOBALS['compression'] == 'gzip' && @function_exists('gzencode'))
					{
						// as a gzipped file
						// without the optional parameter level because it bug
						$dump_buffer = gzencode($dump_buffer);
					}
					if ($GLOBALS['save_on_server'])
					{
						
						  $write_result = @fwrite($GLOBALS['file_handle'], $dump_buffer);
						  if (!$write_result || ($write_result != strlen($dump_buffer)))
						  {
						  $GLOBALS['message'] = PMA_Message::error(__('Insufficient space to save the file %s.'));
						  $GLOBALS['message']->addParam($save_filename);
						  return false;
						  }
						 
					}
					else
					{
						echo $dump_buffer;
					}
					$dump_buffer = '';
					$dump_buffer_len = 0;
				}
			}
			else
			{
				$time_now = time();
				if ($time_start >= $time_now + 30)
				{
					$time_start = $time_now;
					header('X-pmaPing: Pong');
				} // end if
			}
		}
		else
		{
			if (true)
			{
				if ($GLOBALS['output_charset_conversion'])
				{
					//$line = PMA_convert_string('utf-8', $GLOBALS['charset_of_file'], $line);
				}
				if ($GLOBALS['save_on_server'] && strlen($line) > 0)
				{
					$write_result = @fwrite($GLOBALS['file_handle'], $line);
					if (!$write_result || ($write_result != strlen($line)))
					{
						$GLOBALS['message'] = PMA_Message::error(__('Insufficient space to save the file %s.'));
						$GLOBALS['message']->addParam($save_filename);
						return false;
					}
					$time_now = time();
					if ($time_start >= $time_now + 30)
					{
						$time_start = $time_now;
						header('X-pmaPing: Pong');
					} // end if
				}
				else
				{
					// We export as file - output normally
					echo $line;
				}
			}
			else
			{
				// We export as html - replace special chars
				echo htmlspecialchars($line);
			}
		}*/
		
		echo $line;
		return true;
	}

// end of the 'PMA_exportOutputHandler()' function
}