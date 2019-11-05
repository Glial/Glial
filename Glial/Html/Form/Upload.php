<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Html\Form;

class Upload
{

    /**
     * Destination directory
     *
     * @var string
     */
    protected $_destination;
    
    
    
    protected $_error = '';

    /**
     * Constructor
     *
     * @param string $destination
     */
    public function __construct($destination)
    {
        $this->_destination = rtrim($destination, '/');

        if (!is_writable($this->_destination)) {
            return false;
            //throw new \Exception("GLI-067 : Impossible to write into this directory : " . $this->_destination);
        }
    }

    /**
     * Receive file
     *
     * @param string $name
     * @return boolean
     */
    public function receive($name)
    {
        if (empty($_FILES[$name]) || !self::is_uploaded_file($_FILES[$name]['tmp_name'])) {
            
            //debug($_FILES);
            
            $this->codeToMessage($_FILES[$name]['error']);
            return false;
        }

        return self::move_uploaded_file($_FILES[$name]['tmp_name'], $this->_destination . '/' . $_FILES[$name]['name']);
    }

    static public function is_uploaded_file($filename)
    {
        //Check only if file exists
        return file_exists($filename);
    }

    static public function move_uploaded_file($filename, $destination)
    {
        //Copy file

        return copy($filename, $destination);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $this->_error = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $this->_error = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $this->_error = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->_error = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->_error = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $this->_error = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $this->_error = "File upload stopped by extension";
                break;

            default:
                $this->_error = "Unknown upload error";
                break;
        }
    }
    
    public function getErrorMsg()
    {
        return $this->_error;
    }

	
	static public function getMaxUploadSize()
	{
		
		$upload_max_filesize = self::returnBytes(ini_get('upload_max_filesize'));
		$post_max_size = self::returnBytes(ini_get('post_max_size'));
		
		$tab = array($upload_max_filesize, $post_max_size);
		
		$max = max($tab);
		
		/*
		; Maximum allowed size for uploaded files.
		upload_max_filesize = 40M

		; Must be greater than or equal to upload_max_filesize
		post_max_size = 40M
		*/
		
		return $max;
	}
	
	static function returnBytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// Le modifieur 'G' est disponible depuis PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
	
	
	static function formatBytes($bytes, $precision = 2) { 
		$units = array('o', 'Ko', 'Mo', 'Go', 'To'); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 

		// Uncomment one of the following alternatives
		// $bytes /= pow(1024, $pow);
		 $bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow]; 
	} 
}
