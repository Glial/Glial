/****************************************************************************
 * Crypt / CryptAesClass / CryptClass
 * Nicholas de Jong - http://nicholasdejong.com - https://github.com/ndejong
 * 27 November 2011
 * 
 * @author Nicholas de Jong
 * @copyright Nicholas de Jong
 ****************************************************************************/

/**
 * Crypt - a static interface to CryptAesClass
 */
class Crypt {
        
        /**
         * Default CryptAesClass settings
         */
        static public $compress = true;         // compress the data before encrypting
        static public $base64_encode = true;    // base64_encode the encrypted data
        static public $url_safe = true;         // make the encrypted data url_safe
        static public $use_keygen = true;       // transform a user supplied key into a key using more of the available keyspace
        static public $keygen_length = 32;      // where 32 = AES256, 24 = AES192, 16 = AES128
        static public $test_decrypt_before_return = false;
        
        /**
         * The key to use if encrypt/decrypt is called without the second $key argument
         */
        static public $key = null;
        
        /**
         * $CryptAesClass
         * 
         * @var Class
         */
        static private $CryptAesClass = null;
        
        /**
         * encrypt
         * 
         * @param mixed $data
         * @param string $key
         * @return string 
         */
        static public function encrypt($data,$key=null) {
                self::__init($key);
                return self::$CryptAesClass->encrypt($data,$key);
        }
        
        /**
         * decrypt
         * 
         * @param string $data
         * @param string $key
         * @return mixed
         */
        static public function decrypt($data,$key=null) {
                self::__init($key);
                return self::$CryptAesClass->decrypt($data,$key);
        }
        
        /**
         * keygen
         * 
         * @param string $data
         * @return string
         */
        static public function keygen($clear_text,$length=32) {
                self::__init(null);
                return self::$CryptAesClass->keygen($clear_text,$length);
        }
        
        /**
         * __init
         * 
         * @param string $key 
         */
        static private function __init($key) {
                
                // Read the key from Crypt.key if it is empty here
                if(empty($key)) {
                        $key = self::$key;
                }
                
                // Setup the options for CryptAesClass
                $options = array(
                    'compress'          => self::$compress,
                    'base64_encode'     => self::$base64_encode,
                    'url_safe'          => self::$url_safe,
                    'use_keygen'        => self::$use_keygen,
                    'keygen_length'     => self::$keygen_length,
                    'test_decrypt_before_return' => self::$test_decrypt_before_return,
                );
                
                // Create CryptAesClass if we don't have not yet
                if(!self::$CryptAesClass) {
                        self::$CryptAesClass = new CryptAesClass($key,$options);
                }
        }
}
