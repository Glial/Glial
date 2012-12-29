<?php


// Defines
define("RESIZE_STRETCH"	, "stretch");
define("RESIZE_FIT"		, "fit");
define("RESIZE_CROP"	, "crop");
define("POSITION_TOP"	, "top");
define("POSITION_CENTER", "center");
define("POSITION_BOTTOM", "bottom");
define("POSITION_LEFT"	, "left");
define("POSITION_RIGHT"	, "right");

// Script is directly called
if(isset($_GET['src']) && (isset($_GET['w']) || isset($_GET['h']) || isset($_GET['m']) || isset($_GET['f']) || isset($_GET['q']))){
	$ImageProcessor = new ImageProcessor(true);
	$ImageProcessor->Load($_GET['src'], true);
	$ImageProcessor->EnableCache("cache/", 3000);

	$width = isset($_GET['w']) ? $_GET['w'] : null;
	$height = isset($_GET['h']) ? $_GET['h'] : null;
	
	$mode = RESIZE_STRETCH;
	if(isset($_GET['mode'])){
		switch($_GET['mode']){
			case "stretch":
				$mode = RESIZE_STRETCH;
				break;
			case "fit":
				$mode = RESIZE_FIT;
				break;
			case "crop":
				$mode = RESIZE_CROP;
				break;
		}
	}
	
	if(isset($_GET['w']) || isset($_GET['h'])){
		$ImageProcessor->Resize($width, $height, $mode);
	}
	
	if(isset($_GET['f']) && $_GET['f'] == "bw"){
		$ImageProcessor->FilterGray();
	}
	
	if(isset($_GET['m'])){
		$ImageProcessor->Watermark($_GET['m']);
	}
	
	$quality = isset($_GET['q']) ? $_GET['q'] : 80;
	$ImageProcessor->Parse($quality);
}

/**
 * Images processing class
 * 
 * @todo add example howto auto watermark with htaccess
 * @todo cache feature with auto cleanup
 * @todo houdt rekening met landscape en portret foto's
 * @todo generate favicon
 * 
 * - supports jpg, png and gif
 * - Preserves png and gif transparency
 * - rotate images
 * - create image thumbnails on the fly
 * - Can be used with direct url imageprocessor.php?src=
 * - Can be used as a class in your own website/application code
 * - 3 types of image resizing (stretch, fit, crop)
 * - watermark image with easy watermark positioning (pixels or top,bottom,center,left,right)
 * - Cache images for efficiency 
 * 
 * 
 * @see http://php.mirror.facebook.net/manual/en/function.imagefilter.php
 * @see http://themeforest.net/item/imageresize/59935
 */
class ImageProcessor
{
	/**
	 * Origninal image path
	 *
	 * @var string
	 */
	private $_image_path;
	
	/**
	 * Image name
	 * 
	 * @var string
	 */
	protected $_image_name;
	
	/**
	 * Image type
	 * 
	 * @var int
	 */
	private $_image_type;
	
	/**
	 * Image mime type
	 *
	 * @var string
	 */
	protected $_mime;
	
	/**
	 * Image file extension
	 *
	 * @var string
	 */
	protected $_extension;
	
	/**
	 * Is it a direct url call?
	 * 
	 * @var bool
	 */
	private $_direct_call = false;
	
	/**
	 * Old image height
	 * 
	 * @var int
	 */
	private $_old_height;
	
	/**
	 * Old image width
	 * 
	 * @var int
	 */
	private $_old_width;
	
	/**
	 * New image height
	 * 
	 * @var int
	 */
	private $_new_height;
	
	/**
	 * New image width
	 * 
	 * @var int
	 */
	private $_new_width;
	
	/**
	 * Resize mode
	 * 
	 * @var defined
	 */
	private $_resize_mode;
	
	/**
	 * Image resource
	 * 
	 * @var Resource
	 */
	private $_image_resource;
	
	/**
	 * Cache folder
	 * 
	 * @var string
	 */
	private $_cache_folder;
	
	/**
	 * Cache time to live
	 * 
	 * @var int
	 */
	private $_cache_ttl;
	
	/**
	 * Cache on
	 * 
	 * @var bool
	 */
	private $_cache = false;
	
	/**
	 * Cache skip
	 * 
	 * @var bool
	 */
	private $_cache_skip = false;
	
	/**
	 * Cleanup url
	 * 
	 * @access private
	 * @param string $image
	 * @return string
	 */
	private function cleanUrl($image){
		$cimage = str_replace("\\", "/", $image);
		return $cimage;
	}
	
	/**
	 * Show error
	 * 
	 * @access private
	 * @param string $message
	 * @return void
	 */
	private function showError($message=""){
		if($this->_direct_call){
			header('HTTP/1.1 400 Bad Request');
			die($message);
		}else{
			trigger_error($message, E_USER_WARNING);
		}
	}
	
	/**
	 * Get image resource
	 * 
	 * @access private
	 * @param string $image
	 * @param string $extension
	 * @return resource
	 */
	private function GetImageResource($image, $extension){
		switch($extension){
			case "jpeg":
			case "jpg":
				@ini_set('gd.jpeg_ignore_warning', 1);
				$resource = imagecreatefromjpeg($image);
				break;
			case "gif":
				$resource = imagecreatefromgif($image);
				break;
			case "png":
				$resource = imagecreatefrompng($image);
				break;
		}
		return $resource;
	}
	
	/**
	 * Save image to cache folder
	 * 
	 * @access private
	 * @return void
	 */
	private function cacheImage($name, $content){
	    
		// Write content file
		$path = $this->_cache_folder . $name;
		$fh = fopen($path, 'w') or die("can't open file");
		fwrite($fh, $content);
		fclose($fh);
	}
	
	/**
	 * Get an image from cache
	 * 
	 * @access public
	 * @param string $name
	 * @return void
	 */
	private function cachedImage($name){
		$file = $this->_cache_folder . $name;
		$fh = fopen($file, 'r');
		$content = fread($fh,  filesize($file));
		fclose($fh);
		return $content;
	}
	
	/**
	 * Get name of the cache file
	 * 
	 * @access private
	 * @return string
	 */
	private function generateCacheName(){
		$get = implode("-", $_GET);
		return md5($this->_resize_mode . $this->_image_path . $this->_old_width . $this->_old_height . $this->_new_width . $this->_new_height . $get) . "." . $this->_extension;
	}
	
	/**
	 * Check if a cache file is expired
	 * 
	 * @access private
	 * @return bool
	 */
	private function cacheExpired(){
		$path = $this->_cache_folder . $this->generateCacheName();
		if(file_exists($path)){
			$filetime = filemtime($path);
			return $filetime < (time() - $this->_cache_ttl);
		}else{
			return true;
		}
	}
	
	/**
	 * Merges to layers for watermark
	 * but keeps a clean images when using 24bit png
	 *
	 * @return void
	 */
	private function imagecopymergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){ 
		if(!isset($pct)){ 
			return false; 
		} 
		$pct /= 100; 
		// Get image width and height 
		$w = imagesx( $src_im ); 
		$h = imagesy( $src_im ); 
		// Turn alpha blending off 
		imagealphablending( $src_im, false ); 
		// Find the most opaque pixel in the image (the one with the smallest alpha value) 
		$minalpha = 127; 
		for( $x = 0; $x < $w; $x++ ) 
		for( $y = 0; $y < $h; $y++ ){ 
			$alpha = ( imagecolorat( $src_im, $x, $y ) >> 24 ) & 0xFF; 
			if( $alpha < $minalpha ){ 
				$minalpha = $alpha; 
			} 
		} 
		//loop through image pixels and modify alpha for each 
		for( $x = 0; $x < $w; $x++ ){ 
			for( $y = 0; $y < $h; $y++ ){ 
				//get current alpha value (represents the TANSPARENCY!) 
				$colorxy = imagecolorat( $src_im, $x, $y ); 
				$alpha = ( $colorxy >> 24 ) & 0xFF; 
				//calculate new alpha 
				if( $minalpha !== 127 ){ 
					$alpha = 127 + 127 * $pct * ( $alpha - 127 ) / ( 127 - $minalpha ); 
				} else { 
					$alpha += 127 * $pct; 
				} 
				//get the color index with new alpha 
				$alphacolorxy = imagecolorallocatealpha( $src_im, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha ); 
				//set pixel with the new color + opacity 
				if( !imagesetpixel( $src_im, $x, $y, $alphacolorxy ) ){ 
					return false; 
				} 
			} 
		} 
		// The image copy 
		imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h); 
	}
	
	/**
	 * Get the extension name of a file
	 *
	 * @param string $file
	 * @return string
	 */
	private function getExtension($file){
		$parts = explode(".", $file);
		return strtolower(end($parts));	
	}
	
	/**
	 * Lazy load the image resource 
	 * needed for the caching to work
	 *
	 * @return void
	 */
	private function lazyLoad(){
		if(empty($this->_image_resource)){
			if($this->_cache && !$this->cacheExpired()){
				$this->_cache_skip = true;
				return;
			}
			$resource = $this->GetImageResource($this->_image_path, $this->_extension);
			$this->_image_resource = $resource;
		}	 
	}
	
	/**
	 * Constructor
	 * 
	 * @access public
	 * @param bool $direct_call
	 * @return void
	 */
	public function __construct($direct_call=false){
		
		 //Check if GD extension is loaded
	    if (!extension_loaded('gd') && !extension_loaded('gd2')) {
	        $this->showError("GD is not loaded");
	    }
		
		$this->_direct_call = $direct_call;
	}
	
	/**
	 * Resize
	 *
	 * @param int $width
	 * @param int $height
	 * @param define $mode
	 * @param bool $auto_orientation houd rekening met orientatie wanneer er een resize gebeurt
	 */
	public function Resize($width=100, $height=100, $mode=RESIZE_STRETCH, $auto_orientation=false){
		
		// Validate resize mode
		$valid_modes = array("stretch", "fit", "crop");
		if(in_array($mode, $valid_modes)){
			$this->_resize_mode = $mode;
		}else{
			$this->showError("The resize mode '" . $mode . "' does not exists.");
		}
		
		// Aspect ratio resize based on width
		if(is_numeric($width) && !is_numeric($height)){
			$ratio = $this->_old_width / $width;
			$height = ceil($this->_old_height / $ratio);
		}
		
		// Aspect ratio resize based on height
		if(is_numeric($height) && !is_numeric($width)){
			$ratio = $this->_old_height / $height;
			$width = ceil($this->_old_width / $ratio);
		}
		
		// Mode calculations
	    switch($mode){
	    	case "stretch":
	    		$dst_x = 0;
	    		$dst_y = 0;
	    		$src_x = 0;
	    		$src_y = 0;
	    		$dst_w = $width;
	    		$dst_h = $height;
	    		$src_w = $this->_old_width;
	    		$src_h = $this->_old_height;
	    		break;
	    	case "fit":
	    		$dst_x = 0;
	    		$dst_y = 0;
	    		$src_x = 0;
	    		$src_y = 0;
	    		$dst_w = ($this->_old_width > $this->_old_height) ? $this->_old_width : $width;
	    		$dst_h = ($this->_old_height > $this->_old_width) ? $this->_old_height : $height;
	    		$src_w = $this->_old_width;
	    		$src_h = $this->_old_height;
	    		if($dst_w == $this->_old_width){
	    			$ratio = $dst_h/$this->_old_height;
	    			$dst_w = floor($dst_w * $ratio);
	    		}
	    		if($dst_h == $this->_old_height){
	    			$ratio = $dst_w/$this->_old_width;
	    			$dst_h = floor($dst_h * $ratio);
	    		}

	    		$width = $width > $dst_w ? $dst_w : $width;
	    		$height = $height > $dst_h ? $dst_h : $height;
	    		break;
	    	case "crop":
	    		$width = $width > $this->_old_width ? $this->_old_width : $width;
	    		$height = $height > $this->_old_height ? $this->_old_height : $height;
	    		$dst_x = 0;
	    		$dst_y = 0;
	    		$calc_x = ceil($this->_old_width/2) - floor($width / 2);
	    		$src_x = $calc_x > 0 ? $calc_x : 0;
	    		$calc_y = ceil($this->_old_height/2) - floor($height / 2);
	    		$src_y = $calc_y > 0 ? $calc_y : 0;
	    		$dst_w = $this->_old_width;
	    		$dst_h = $this->_old_height;
	    		$src_w = $this->_old_width;
	    		$src_h = $this->_old_height;
	    		break;
	    }
	    
	    // Set news size vars because these are used for the
	    // cache name generation
	    $this->_new_width = $width;
	    $this->_new_height = $height;
	    
		$this->_old_width = $width;
		$this->_old_height = $height;
		
		// Lazy load for the directurl cache to work
		$this->lazyLoad();
		if($this->_cache_skip) return true;
		
		// Create canvas for the new image
		$new_image = imagecreatetruecolor($width, $height);
		
		 // Check if this image is PNG or GIF to preserve its transparency
	    if(($this->_image_type == 1) || ($this->_image_type == 3))
	    {
	        imagealphablending($new_image, false);
	        imagesavealpha($new_image,true);
	        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
	        imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
	    }
		
		imagecopyresampled($new_image, $this->_image_resource, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		
		// Apply transparency to resized gif images
		if($this->_extension == "gif"){
			$trnprt_indx = imagecolortransparent($resource);
      		if ($trnprt_indx >= 0) {
       			$trnprt_color    = imagecolorsforindex($this->_image_resource, $trnprt_indx);
        		$trnprt_indx    = imagecolorallocate($new_image, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
        		imagefill($new_image, 0, 0, $trnprt_indx);
       			imagecolortransparent($new_image, $trnprt_indx);
			}
		}
		
		$this->_image_resource = $new_image;
	}
	
	public function Crop($x1, $y1, $x2, $y2)
	{
		$width = $x2-$x1;
		$height = $y2-$y1;
		
	    $this->_new_width = $width;
	    $this->_new_height = $height;
	    
		$this->_old_width = $width;
		$this->_old_height = $height;
		
		$cropimg = imagecreatetruecolor($width,$height);
		imagecopyresampled($cropimg, $this->_image_resource, 0, 0,$x1,$y1, $width, $height, $width, $height); //amйlioration en un seul passage grace а l'extrapolation
		
		$this->_image_resource = $cropimg;
	}
	
	
	
	
	
	/**
	 * Rotate an image
	 *
	 * @access public
	 * @param int $degrees
	 * @return void
	 */
	public function Rotate($degrees=180){
		$this->lazyLoad();
		if($this->_cache_skip) return true;
		$this->_image_resource = imagerotate($this->_image_resource, $degrees, 0);
	}
	
	/**
	 * Add watermark
	 * 
	 * To use the left right bottom top center parameters you must first d
	 * 
	 * @access public
	 * @param string $image
	 * @param define $horizontal
	 * @param define $vertical
	 * @return void
	 */
	public function Watermark($image, $horizontal=POSITION_RIGHT, $vertical=POSITION_BOTTOM){
		
		// Lazy load
		$this->lazyLoad();
		if($this->_cache_skip) return;
		
		// Get extension
		$extension = $this->getExtension($image);
		
		// Image info
		list($width, $height, $type) = getimagesize($image);
		
		// Get image resource
		$watermark = $this->GetImageResource($image, $extension);
		
		// Resource width and height
		$image_width = imagesx($this->_image_resource);
		$image_height = imagesy($this->_image_resource);

		// Calculate positions
		$position_x = $horizontal;
		$position_y = $vertical;
		switch($position_x){
			case "left":
				$position_x = 0;
				break;
			case "center":
				$position_x = ceil($image_width/2) - floor($width/2);
				break;
			case "right":
				$position_x = $image_width - $width;
				break;
		}
		switch($position_y){
			case "top":
				$position_y = 0;
				break;
			case "center":
				$position_y = ceil($image_height/2) - floor($height/2);
				break;
			case "bottom":
				$position_y = $image_height - $height;
				break;
		}

		$extension = $this->getExtension($image);
		if($extension == "png"){
			$this->imagecopymergeAlpha($this->_image_resource, $watermark, $position_x, $position_y, 0, 0, $width, $height, 100);
		}else{
			imagecopymerge($this->_image_resource, $watermark, $position_x, $position_y, 0, 0, $width, $height, 100);
		}
		
		// Destroy watermark
		imagedestroy($watermark); 
	}
	
	/**
	 * Create image resource from path or url
	 * 
	 * @access public
	 * @param string $location
	 * @param bool $lazy_load
	 * @return 
	 */
	public function Load($image,$lazy_load=false){
		
		// Cleanup image url
		$image = $this->cleanUrl($image);
		
		// Get mime type of the image
		$extension = $this->getExtension($image);
		
		$mimes = array(
			'jpeg' => 'image/jpeg',
 			'jpg'  => 'image/jpeg',
 			'gif'  => 'image/gif',
 			'png'  => 'image/png'
 		);
 		
 		// Check if it is a valid image
 		if(isset($mimes[$extension]) && ((!strstr($image, "http://") && file_exists($image)) || strstr($image, "http://")) ){
			
			// Urlencode if http
			if(strstr($image, "http://")){
				$image = str_replace(array('http%3A%2F%2F', '%2F'), array('http://', '/'), urlencode($image));
			}
			
 			$this->_extension = $extension;
 			$this->_mime = $mimes[$extension];
 			$this->_image_path = $image;
 			$parts = explode("/", $image);
 			$this->_image_name = str_replace("." . $this->_extension, "", end($parts));
 			
 			// Get image size
 			list($width, $height, $type) = getimagesize($image);
 			$this->_old_width = $width;
 			$this->_old_height = $height;
 			$this->_image_type = $type;
 		}else{
 			$this->showError("Wrong image type or file does not exists.");
 		}
 		if(!$lazy_load){
			$resource = $this->GetImageResource($image, $extension);
			$this->_image_resource = $resource;
 		}
		
	}
	
	/**
	 * Save image to computer
	 *
	 * @access public
	 * @param string $destination
	 * @return void
	 */
	public function Save($destination, $quality=80){
		if($this->_extension == "png" || $this->_extension == "gif"){
			imagesavealpha($this->_image_resource, true); 
		}
		
		switch ($this->_extension) {
	        case "jpg": imagejpeg($this->_image_resource,$destination, $quality);  	break;
	        case "jpeg": imagejpeg($this->_image_resource,$destination, $quality);  	break;
	        case "gif": imagegif($this->_image_resource,$destination); 		break;
	        case "png": imagepng($this->_image_resource,$destination); 		break;
	        default: $this->showError('Failed to save image!');  			break;
	    }
	    
	}
	
	/**
	 * Print image to screen
	 * 
	 * @access public
	 * @return void
	 */
	public function Parse($quality=80){
		$name = $this->generateCacheName();
		$content = "";
		if(!$this->_cache || ($this->_cache && $this->cacheExpired())){
			ob_start();
			header ("Content-type: " . $this->_mime);
			
			if($this->_extension == "png" || $this->_extension == "gif"){
				imagesavealpha($this->_image_resource, true); 
			}
			
			switch ($this->_extension) {
		        case "jpg": imagejpeg($this->_image_resource, "", $quality);  	break;
		        case "jpeg": imagejpeg($this->_image_resource, "", $quality);  	break;
		        case "gif": imagegif($this->_image_resource); 	break;
		        case "png": imagepng($this->_image_resource); 	break;
		        default: $this->showError('Failed to save image!');  			break;
		    }

		    $content = ob_get_contents();
			ob_end_clean();
		}else{
			header ("Content-type: " . $this->_mime);
			echo $this->cachedImage($name);
			exit();
		}
		
		// Save image content
		if(!empty($content) && $this->_cache){
			$this->cacheImage($name, $content);
		}
		
		// Destroy image
		$this->Destroy();
		
		echo $content;
		exit();
	}
	
	/**
	 * Destroy resources
	 * 
	 * @access public
	 * @return void
	 */
	public function Destroy(){
		imagedestroy($this->_image_resource); 
	}
	
	/**
	 * Filter: Negative effect
	 * 
	 * @access public
	 * @return void
	 */
	public function FilterNegative(){
		if(isset($this->_image_resource)){
			imagefilter($this->_image_resource, IMG_FILTER_NEGATE);
		}else{
			$this->showError("Load an image first");
		}
	}
	
	/**
	 * Filter: Grayscale effect
	 * 
	 * @access public
	 * @return void
	 */
	public function FilterGray(){
		$this->lazyLoad();
		if($this->_cache_skip) return;
		if(isset($this->_image_resource)){
			imagefilter($this->_image_resource, IMG_FILTER_GRAYSCALE);
		}
		//}else{
		//	$this->showError("Load an image first");
		//}
	}
	
	/**
	 * Get image resources
	 * 
	 * @access public
	 * @return resource
	 */
	public function GetResource(){
		return $this->_image_resource;
	}
	
	/**
	 * Set image resources
	 * 
	 * @access public
	 * @param resource $image
	 * @return resource
	 */
	public function SetResource($image){
		$this->_image_resource = $image;
	}
	
	/**
	 * Enable caching
	 * 
	 * @access public
	 * @param string $folder
	 * @param int $ttl
	 * @return void
	 */
	public function EnableCache($folder="cache/", $ttl=60){
		if(!is_dir($folder)){
			$this->showError("Directory '" . $folder . "' does'nt exist");
		}else{
			$this->_cache			= true;
			$this->_cache_folder 	= $folder;
			$this->_cache_ttl 		= $ttl;
		}
		return false;
	}
	
	
	

	
}

function generate_crop($data, $size, $src, $dst)
{

//creation of directory to host the tmp photo
	$data['species'] = str_replace(" ","_",$data['species_']);
	$name_pic = $data['id_photo']."-".$data['species'].".jpg";
	$path = "/Eukaryota/".$data['kingdom']."/".$data['phylum']."/".$data['class']."/".$data['order2']."/".$data['family']."/".$data['genus']."/".$data['species']."/";
	$path_src = $src."img".$path;
	
	
	if (! file_exists($path_src.$name_pic))
	{
		//echo $path_src.$name_pic;
		return false;
	}
	
	$path_dst = $dst."/".$size."x".$size.$path;
	$path_dst2 = $dst."/".SIZE_SITE_MAX."x".SIZE_SITE_MAX.$path;
	exec("mkdir -p ".$path_dst);
	exec("mkdir -p ".$path_dst2);
	
	//load de la photo original
	$ImageProcessor = new ImageProcessor();
	$ImageProcessor->Load($path_src.$name_pic);
	
	if ($data['width'] > SIZE_SITE_MAX)
	{
		$ImageProcessor->Resize(SIZE_SITE_MAX, null, RESIZE_STRETCH); //on retaille en 890 de large pour appliquer le crop
		$ImageProcessor->Save($path_dst2.$name_pic,100);
	}
	else
	{
		$cmd = "cp ".$path_src.$name_pic." ".$path_dst2.$name_pic;
		shell_exec($cmd);
	}
	
	
	$crop = explode(";",$data['crop']);
	
	
	$ImageProcessor->Crop($crop[0],$crop[1],$crop[2],$crop[3]);
	$ImageProcessor->Resize($size, $size, RESIZE_STRETCH);
	$ImageProcessor->Save($path_dst.$name_pic,100);
	

}

