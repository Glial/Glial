<?php

class Aes {
        
        /**
         * $options
         * 
         * @var array
         */
        public $options = null;
        
        /**
         * $key
         * 
         * @var string
         */
        public $key = null;
        
        /**
         * $debug
         * 
         * @var bool
         */
        public $debug = true;

        /**
         * __construct()
         * 
         * @param string $cipher
         * @param string $key
         * @param string $mode
         * @param string $iv 
         */
        function __construct($key=null,$options=array()) {
                
                // Make sure php mcrypt is available here
                if(!function_exists('mcrypt_decrypt')) {
                        throw new Exception("Required PHP dependency library 'mcrypt' is not available - http://php.net/manual/en/book.mcrypt.php");
                }
                
                // The options to use
                $this->options = array_merge(
                        array(
                            'compress' => true,         // compress the data before encrypting
                            'base64_encode' => true,    // base64_encode the encrypted data
                            'url_safe' => true,         // make the encrypted data url_safe
                            'use_keygen' => true,       // transform a user supplied key into a key using more of the available keyspace
                            'keygen_length' => 32,      // where 32 = AES256, 24 = AES192, 16 = AES128
                            'test_decrypt_before_return' => false,
                        ),
                        (array)$options
                );
                
                // Catch the key if it is supplied at this point
                if(!empty($key)) {
                        $this->key = $key;
                }
        }

        /**
         * encrypt()
         *
         * @param mixed $data_in
         * @param string $key
         * @return string
         */
        public function encrypt($data_in,$key=null) {

                // Return early if $data is empty
                if (empty($data_in)) {
                        return $data_in;
                }
                
                // Make sure we have a key value
                if(empty($key)) {
                        $key = $this->key;
                }

                // serialize the source data - we use serialize over json_encode
                // because serialize is binary safe and JSON is not
                $data = serialize($data_in);

                // Compress if required
                if ($this->options['compress']) {
                        $data = gzcompress($data);
                }
                
                // Encrypt the data
                $data = $this->_encryptData($data,$key);
                
                // Base64 encode if required
                if ($this->options['base64_encode']) {
                        $data = base64_encode($data);
                        
                        // URL safe if required - note that we only do url_safe
                        // if the data has been guarded by a base64_encode first
                        if ($this->options['url_safe']) {
                                $data = strtr($data, '+/=', '-_,');
                        }
                }

                // Decrypt test if we need to
                if ($this->options['test_decrypt_before_return']) {
                        if ($data_in !== $this->decrypt($data,$key)) {
                                throw new Exception('Unable to confirm encrypted data will match decrypted data!');
                        }
                }
                
                return $data;
        }

        /**
         * decrypt()
         *
         * @param string $data
         * @param string $key
         * @return mixed
         */
        public function decrypt($data_in,$key=null) {
                
                // Return early if $data is empty
                if (empty($data_in)) {
                        return $data_in;
                }
                
                // Make sure we have a key value
                if(empty($key)) {
                        $key = $this->key;
                }
                
                $data = $data_in;
                
                // URL safe if required only if base64_encode performed
                if ($this->options['url_safe'] && $this->options['base64_encode']) {
                        $data = strtr($data, '-_,', '+/=');
                }
                
                // Base64 encode if required
                if ($this->options['base64_encode']) {
                        $data = base64_decode($data);
                }
                
                // Decrypt the data
                $data = $this->_decryptData($data,$key);
                
                // Decompress if required
                if ($this->options['compress']) {
                        $data = gzuncompress($data);
                }

                // Unserialize the data
                return unserialize($data);
        }
        
        /**
         * keygen()
         * 
         * generates the same return value for any given input value
         * 
         * @param string $clear_text 
         */
        public function keygen($clear_text,$length=null) {
                
                // Set the length of the key we will generate here
                if(empty($length)) {
                        $length = $this->options['keygen_length'];
                }
                
                // The hard coded first character to pick from the $string below
				// we do this because the first set of characters in a base64_encode
				// comes from a limited range of characters which is what we want
				// avoid in the first place
                $first_character_position = 20;
                
                // The generated string based on the known $clear_text - to get
				// a good jumble of (printable) characters we use the expression
				// below which will always return the same output string for the
				// same input clear_text
                $string = base64_encode(base64_encode(md5($clear_text,true).md5($clear_text,true)));
                
                // The key to return based on the first position and the required length
                return substr($string,$first_character_position,$length);
        }
        
        /**
         * _decryptData
         * 
         * @param string $data_with_iv_suffix
         * @param string $key
         * @param string $delimiter
         * @return string 
         */
        protected function _decryptData($data_with_iv_suffix,$key) {
                
                // Transform the user key into something that uses a wider spectrum of the possible keyspace
                if($this->options['use_keygen']) {
                        $key = $this->keygen($key,$this->options['keygen_length']);
                }

               $this->_preChecks($key);
 				
                // encrypt the data
                $data = mcrypt_decrypt(
                        MCRYPT_RIJNDAEL_128,    // AES is RIJNDAEL with a block size of 128 bits only
                        $key,                   // secret key - NOTE: key size determines AES_128, AES_192 or AES_256
                        substr($data_with_iv_suffix,0,(strlen($data_with_iv_suffix)-16)), // the data with the last 128 bytes (16 chars) removed since that part is the iv
                        MCRYPT_MODE_CBC,        // cipher mode
                        substr($data_with_iv_suffix,(strlen($data_with_iv_suffix)-16),16) // the iv
                );
                
                // Don't strip null padding if compression was used else the
                // decompress process will fail later on
                if($this->options['compress']) {
                        return $data;
                }
                
                return rtrim($data,"\0");
        }
        
        /**
         * _encryptData
         * 
         * @param string $data
         * @param string $key
         * @param string $delimiter
         * @return string
         */
        protected function _encryptData($data,$key) {
                
                // Transform the user key into something that uses a wider spectrum of the possible keyspace
                if($this->options['use_keygen']) {
                        $key = $this->keygen($key,$this->options['keygen_length']);
                }

                $this->_preChecks($key);
                
                // Choose a good random iv -> 16chars * 8bits = 128 block size for MCRYPT_RIJNDAEL_128
                $iv = $this->keygen(md5(mt_rand(0,1000000000)).md5(mt_rand(0,1000000000)),16);
                
                // encrypt the data
                $data = mcrypt_encrypt(
                        MCRYPT_RIJNDAEL_128,    // AES is RIJNDAEL with a block size of 128 bits only
                        $key,                   // secret key - NOTE: key size determines AES_128, AES_192 or AES_256
                        $data,                  // data to encrypt
                        MCRYPT_MODE_CBC,        // cipher mode
                        $iv                     // the random iv
                );
                
                // Append the iv at the end, the return data is useless without knowing it
                return $data.$iv;
        }
        
        
        /**
         * _preChecks
         * 
         * @param type $key 
         */
        protected function _preChecks($key) {
                
                // Make sure there is a key value set
                if(empty($key)) {
                        throw new Exception('No $key value supplied for crypt operation');
                }
                
                // Make sure the key length is valid for AES - it is the key length 
                // determines which strength AES is used, ie AES_128, AES_192 or AES_256
                $key_length = (strlen($key)*8);
                if('128' != $key_length && '192' != $key_length && '256' != $key_length) {
                        throw new Exception('Unsuitable key length for AES type encryption - the key value *MUST* be 128, 192 or 256 bits which means it must be 16, 24 or 32 string characters in length');
                }
                
                return true;
        }
        
}
