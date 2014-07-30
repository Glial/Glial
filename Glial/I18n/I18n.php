<?php

namespace Glial\I18n {

    use Glial\Extract\Grabber;

    class I18n
    {
        
// to prevent kick or/and ban from google
        private static $nb_google_call = 0;
        private static $_SQL;

        /**
         * Holds the current language
         * 
         * @var string
         */
        private static $_language;

        /**
         * Holds the default language
         * 
         * @var string
         */
        private static $_defaultlanguage = "en";

        /**
         * The path where to save the translation files
         * 
         * @var string
         */
        private static $_path;

        /**
         * Array with all translations
         * 
         * @var array
         */
        public static $_translations = array();

        /**
         * Array storage with all words / senteces to translate in one row at end
         * 
         * @var array
         */
        public static $_to_translate = array();

        /**
         * 
         * 
         * @var string
         */
        private static $file;

        /**
         * File where we get the string to translate
         * 
         * @var string
         */
        private static $line;

        /**
         * store the data before to translate all in one row
         *
         * @var array
         */
        public static $data = array();

        /**
         * Line where we get the string to translate
         *
         * @var int
         */
        public static $languages = array("auto" => "automatic",
            "af" => "afrikaans",
            "sq" => "albanian",
            "ar" => "arabic",
            "hy" => "armenian",
            "az" => "azerbaijani",
            "eu" => "basque",
            "be" => "belarusian",
            "bn" => "bengali",
            "bg" => "bulgarian",
            "ca" => "catalan",
            "zh-cn" => "chinese",
            "hr" => "croatian",
            "cs" => "czech",
            "da" => "danish",
            "nl" => "dutch",
            "en" => "english",
            "eo" => "esperanto",
            "et" => "estonian",
            "tl" => "filipino",
            "fi" => "finnish",
            "fr" => "french",
            "gl" => "galician",
            "ka" => "georgian",
            "de" => "german",
            "el" => "greek",
            "gu" => "gujarati",
            "ht" => "haitian creole",
            "he" => "hebrew",
            "hi" => "hindi",
            "hu" => "hungarian",
            "is" => "icelandic",
            "id" => "indonesian",
            "ga" => "irish",
            "it" => "italian",
            "ja" => "japanese",
            "kn" => "kannada",
            "ko" => "korean",
            "la" => "latin",
            "lv" => "latvian",
            "lt" => "lithuanian",
            "mk" => "madedonian",
            "ms" => "malay",
            "mt" => "maltese",
            "no" => "norwegian",
            "fa" => "persian",
            "pl" => "polish",
            "pt" => "portuguese",
            "ro" => "romanian",
            "ru" => "russian",
            "sr" => "serbian",
            "sk" => "slovak",
            "sl" => "slovenian",
            "es" => "spanish",
            "es" => "swahili",
            "sv" => "swedish",
            "ta" => "tamil",
            "te" => "telugu",
            "ta" => "thai",
            "tr" => "turkish",
            "uk" => "ukrainian",
            "ur" => "urdu",
            "vi" => "vietnamese",
            "cy" => "welsh",
            "yi" => "yiddish",
        );

        /**
         * Charset source
         *
         * @var arrays
         */
        public static $charset = array(
            "sq" => "albanian",
            "ar" => "arabic",
            "bg" => "bulgarian",
            "ca" => "catalan",
            "zh-cn" => "GB2312",
            "hr" => "croatian",
            "cs" => "Windows-1252",
            "da" => "ISO-8859-1",
            "nl" => "ISO-8859-1",
            "en" => "ISO-8859-1",
            "et" => "estonian",
            "tl" => "filipino",
            "fi" => "ISO-8859-1",
            "fr" => "ISO-8859-1",
            "gl" => "galician",
            "de" => "ISO-8859-1",
            "el" => "greek",
            "iw" => "hebrew",
            "hi" => "hindi",
            "hu" => "hungarian",
            "id" => "indonesian",
            "it" => "ISO-8859-1",
            "ja" => "Shift_JIS",
            "ko" => "EUC-KR",
            "lv" => "latvian",
            "lt" => "lithuanian",
            "mt" => "maltese",
            "no" => "ISO-8859-1",
            "fa" => "persian alpha",
            "pl" => "ISO-8859-2",
            "pt" => "ISO-8859-1",
            "ro" => "romanian",
            "ru" => "KOI8-R",
            "sr" => "serbian",
            "sk" => "slovak",
            "sl" => "slovenian",
            "es" => "ISO-8859-1",
            "sv" => "swedish",
            "ta" => "thai",
            "tr" => "turkish",
            "uk" => "ukrainian",
            "vi" => "vietnamese"
        );

        /**
         * language source
         *
         * @var arrays
         */
        public static $languagesUTF8 = array(
            "sq" => "albanian",
            "ar" => "العربية",
            "bg" => "bulgarian",
            "ca" => "Català",
            "zh-cn" => "简体中文",
            "hr" => "croatian",
            "cs" => "Čeština",
            "da" => "Dansk",
            "nl" => "Nederlands",
            "en" => "English",
            "et" => "estonian",
            "tl" => "filipino",
            "fi" => "Suomea",
            "fr" => "Français",
            "gl" => "galician",
            "de" => "Deutsch",
            "el" => "Ελληνικά",
            "iw" => "ייִדיש",
            "hi" => "hindi",
            "hu" => "Magyar",
            "id" => "Bahasa Indonesia",
            "it" => "Italiano",
            "ja" => "日本語",
            "ko" => "한국어",
            "lv" => "Latviešu",
            "la" => "Latin",
            "lt" => "Lietuviškai",
            "mk" => "Македонски",
            "mt" => "Malti",
            "no" => "Norsk",
            "fa" => "آلفا فارسی",
            "pl" => "Polski",
            "pt" => "Português",
            "ro" => "Română",
            "ru" => "Русский",
            "sr" => "Српски/Srpsk",
            "sk" => "Slovenčina",
            "sl" => "Slovenščina",
            "es" => "Español",
            "sv" => "Svenska",
            "ta" => "ไทย",
            "tr" => "Türkçe",
            "uk" => "Українська",
            "vi" => "việt"
        );

        /**
         * language source
         *
         * @var arrays
         */
        public static $languagesENGLISH = array(
            "Czech" => "cs",
            "German" => "de",
            "Danish" => "dk",
            "English" => "en",
            "Spanish" => "es",
            "Finnish" => "fi",
            "French" => "fr",
            "Icelandic" => "is",
            "Italian" => "it",
            "Japanese" => "ja",
            "Dutch" => "nl",
            "Norwegian" => "no",
            "Polish" => "pl",
            "Portuguese" => "pt",
            "Russian" => "ru",
            "Slovak" => "sk",
            "Swedish" => "se"
        );
        private static $_md5File;
        private static $file_path;
        private static $countNumberElemAtLoading = array();

        /**
         * Constructor
         * 
         * @access private
         * @return void
         */
        public static function injectDb($db)
        {
            self::$_SQL = $db;
        }

        public static function getDb()
        {
            return self::$_SQL;
        }

        /**
         * Method to translate a string
         * 
         * @access public
         * @param string $string
         * @return string
         */
        private static function initiate($iso)
        {

              /*
            $sql = "CREATE TABLE IF NOT EXISTS ".self::$_SQL->sql('default')->ESC."translation_" . mb_strtolower($iso) . "".self::$_SQL->sql('default')->ESC." (
		".self::$_SQL->sql('default')->ESC."id".self::$_SQL->sql('default')->ESC." int(11) NOT NULL auto_increment,
		".self::$_SQL->sql('default')->ESC."id_history_etat".self::$_SQL->sql('default')->ESC." int NOT NULL,
		".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC." char(40) NOT NULL,
		".self::$_SQL->sql('default')->ESC."source".self::$_SQL->sql('default')->ESC." char(2) NOT NULL,
		".self::$_SQL->sql('default')->ESC."text".self::$_SQL->sql('default')->ESC." text NOT NULL,
		".self::$_SQL->sql('default')->ESC."date_inserted".self::$_SQL->sql('default')->ESC." datetime NOT NULL,
		".self::$_SQL->sql('default')->ESC."date_updated".self::$_SQL->sql('default')->ESC." datetime NOT NULL,
		".self::$_SQL->sql('default')->ESC."translate_auto".self::$_SQL->sql('default')->ESC." int(11) NOT NULL,
		".self::$_SQL->sql('default')->ESC."file_found".self::$_SQL->sql('default')->ESC." varchar(255) NOT NULL,
		".self::$_SQL->sql('default')->ESC."line_found".self::$_SQL->sql('default')->ESC." int NOT NULL,
		PRIMARY KEY  (".self::$_SQL->sql('default')->ESC."id".self::$_SQL->sql('default')->ESC."),
		UNIQUE KEY ".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC." (".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC.",".self::$_SQL->sql('default')->ESC."file_found".self::$_SQL->sql('default')->ESC."),
		INDEX ".self::$_SQL->sql('default')->ESC."id_history_etat".self::$_SQL->sql('default')->ESC." (".self::$_SQL->sql('default')->ESC."id_history_etat".self::$_SQL->sql('default')->ESC.")
		);";
            self::$_SQL->sql('default')->sql_query($sql);
                
               
               */
        }

        /**
         * translate a string
         *
         * @access public
         * @param string $from
         * @param string $to
         * @param string $text
         * @return string
         */
        public static function translate($from, $to, $text, $key)
        {
//TODO : insert cost a lost even if fail have to make select before
            //self::insert_db($from, $from, $text, $key, '0');

            $translate_auto = 1;

            $sql = "SELECT text,translate_auto from translation_main WHERE ".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC." ='" . $key . "' and ".self::$_SQL->sql('default')->ESC."destination".self::$_SQL->sql('default')->ESC." = '" . $to . "'";
            $res = self::$_SQL->sql('default')->sql_query($sql);


            if (self::$_SQL->sql('default')->sql_num_rows($res) == 1) {
                $ob = self::$_SQL->sql('default')->sql_fetch_object($res);
                $rep = $ob->text;
                $translate_auto = $ob->translate_auto;
            } else if (self::$_SQL->sql('default')->sql_num_rows($res) == 0) {

                self::$_to_translate[$from][$key]['val'] = $text;
                self::$_to_translate[$from][$key]['file'] = self::$file;
                self::$_to_translate[$from][$key]['md5'] = self::$_md5File;
                self::$_to_translate[$from][$key]['line'] = self::$line;


                return false;
            } else {
                die("We have a problem !");
            }

            //self::insert_db($to, $from, $rep, $key, $translate_auto);


            self::$_translations[self::$_md5File][$key] = $rep;

            self::saveCashFile();
// Return translation

            return true;
        }

        public static function getTranslation($html = '')
        {
//first loop to translate language by language



            if (!empty(self::$_to_translate)) {
                self::testTable(self::$_language);
            }

            foreach (self::$_to_translate as $from => $tab) {
                $string_to_translate = '';
                $extract = array();

                $k = 0;

// to escape => ERROR 414 (That’s an error) from google The requested URL /translate_t... is too large to process. 
// $string_to_translate => contain max len
                foreach ($tab as $key => $elem) {
                    $nb_char = strlen($string_to_translate) + strlen($elem['val']);

                    if ($nb_char < GOOGLE_NB_CHAR_MAX) {
                        $string_to_translate .= $elem['val'];
                        $extract[$k][$key] = $elem;
                    } else {
                        $k++;
                        $string_to_translate = $elem['val'];
                        $extract[$k][$key] = $elem;
                    }
                }


                foreach ($extract as $result) {

                    if (self::$nb_google_call > 0) {
                        sleep(2); // to prevent kick/ban from google or other system
                    }

                    self::$nb_google_call++;

                    $string = '';

                    $tab_key = array();
                    $tab_string = array();

                    foreach ($result as $key => $str) {
                        $tab_key[] = '<span id="' . $key . '">' . $str['val'] . '</span>';
                        $tab_string[] = $str['val'];
                        $string = $string . "\n" . $str['val'];
                    }

                    $string = trim($string);
                    $tab_out = self::get_answer_from_google($string, $from);
                    (ENVIRONEMENT) ? $GLOBALS['_DEBUG']->save("calling google... ") : "";

                    if ($tab_out) {
                        $html = str_replace($tab_key, $tab_out, $html);
                        $i = 0;
                        foreach ($result as $key => $data) {
                            self::$_translations[$data['md5']][$key] = $tab_out[$i];
                            self::$file = $data['file'];
                            self::$line = $data['line'];
                            self::$_md5File = $data['md5'];

                            //self::insert_db($to, $from, $rep, $key, $translate_auto);
                            self::save_db(self::$_language, $from, $tab_out[$i], $key, '1', $data['file'], $data['line']);
                            $i++;
                        }
                    } else {
                        $html = str_replace($tab_key, $tab_string, $html);
                    }
                }
            }


            self::saveCashFile();

            //self::$_to_translate = array();

            return ($html);
        }

        private static function insert_db($iso, $source, $text, $key, $translate_auto)
        {

            $sql = "INSERT IGNORE INTO ".self::$_SQL->sql('default')->ESC."translation_" . mb_strtolower($iso) . "".self::$_SQL->sql('default')->ESC."
		SET ".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC." ='" . $key . "',
		".self::$_SQL->sql('default')->ESC."source".self::$_SQL->sql('default')->ESC." = '" . self::$_SQL->sql('default')->sql_real_escape_string($source) . "',
		".self::$_SQL->sql('default')->ESC."text".self::$_SQL->sql('default')->ESC." = '" . self::$_SQL->sql('default')->sql_real_escape_string($text) . "',
		".self::$_SQL->sql('default')->ESC."date_inserted".self::$_SQL->sql('default')->ESC." = now(),
		".self::$_SQL->sql('default')->ESC."date_updated".self::$_SQL->sql('default')->ESC." = now(),
		".self::$_SQL->sql('default')->ESC."translate_auto".self::$_SQL->sql('default')->ESC." = '" . $translate_auto . "',
		".self::$_SQL->sql('default')->ESC."file_found".self::$_SQL->sql('default')->ESC." = '" . self::$file . "',
		".self::$_SQL->sql('default')->ESC."id_history_etat".self::$_SQL->sql('default')->ESC." = 1,
		".self::$_SQL->sql('default')->ESC."line_found".self::$_SQL->sql('default')->ESC." ='" . self::$line . "'";

            self::$_SQL->sql('default')->sql_query($sql);
        }

        private static function save_db($iso, $source, $text, $key, $translate_auto, $file, $line)
        {
            $data = array();
            $data["translation_" . mb_strtolower($iso)]['key'] = $key;
            $data["translation_" . mb_strtolower($iso)]['source'] = self::$_SQL->sql('default')->sql_real_escape_string($source);
            $data["translation_" . mb_strtolower($iso)]['text'] = self::$_SQL->sql('default')->sql_real_escape_string($text);
            $data["translation_" . mb_strtolower($iso)]['date_inserted'] = date("Y-m-d H:i:s");
            $data["translation_" . mb_strtolower($iso)]['date_updated'] = date("Y-m-d H:i:s");
            $data["translation_" . mb_strtolower($iso)]['translate_auto'] = intval($translate_auto);
            $data["translation_" . mb_strtolower($iso)]['file_found'] = $file;
            $data["translation_" . mb_strtolower($iso)]['id_history_etat'] = 1;
            $data["translation_" . mb_strtolower($iso)]['line_found'] = intval($line);

            self::$_SQL->sql('default')->set_history_type(6);
            self::$_SQL->sql('default')->set_history_user(11);

            if (!self::$_SQL->sql('default')->sql_save($data)) {
                debug(self::$_SQL->sql('default')->error);
//mail("aurelien.lequoy@gmail.com","Estrildidae : Bug with I18n", json_encode($data));
            }
        }

        public static function testTable($iso)
        {

            $ret = self::$_SQL->sql('default')->getListTable();

            if (in_array("translation_" . strtolower($iso), $ret['table'])) {
                true;
            } else {
                self::initiate($iso);
                return false;
            }
        }

        /**
         * Method to translate a string
         * 
         * @access public
         * @param string $string
         * @return string
         */
        public static function _($string, $lgfrom, $file, $line)
        {



            if ($lgfrom != self::$_defaultlanguage) {
                $default_lg = $lgfrom;
            } else {
                $default_lg = self::$_defaultlanguage;
            }

            self::$file = $file;
            self::$line = $line;
            self::$_md5File = md5(self::$file);
            self::$file_path = self::$_path . "/" . self::$_language . "." . self::$_md5File . ".ini";



            if (empty(self::$_translations[self::$_md5File])) {
                self::loadCashFile();
            }

            $string = str_replace("\r\n", " ", $string);
            $string = str_replace("\n", " ", $string);

            $elem = $string;

            $key = sha1($elem);

            if (isset(self::$_translations[self::$_md5File][$key])) {

                $res = self::$_translations[self::$_md5File][$key];
            } else {
// Add string to translations
                if (self::$_language != $default_lg) {
//cas ou une chaine est appellé plusieurs fois dans une même page
                    if (array_key_exists($key, self::$_to_translate)) {
                        $out = false;
                    } else {

                        $out = self::translate($default_lg, self::$_language, $string, $key);
                    }
                } else {
                    $out = true;
                    self::$_translations[self::$_md5File][$key] = $string;
                }

                if ($out) {
                    $res = self::$_translations[self::$_md5File][$key];
                } else {
                    $res = '<span id="' . $key . '">' . $string . '</span>';
                }
            }

            return $res;
        }

        /**
         * Load language file
         * 
         * @access public
         * @param string $language
         * @return void
         */
        public static function load($language)
        {
            self::$_language = $language;
            //self::testTable(self::$_language);
        }

        /**
         * Set the path where to save the translation files
         * 
         * @access public
         * @param string $path
         * @return void
         */
        public static function SetSavePath($path)
        {
            self::$_path = $path;
        }

        /**
         * Set the default language
         * 
         * @access public
         * @param string $language
         * @return void
         */
        public static function SetDefault($language)
        {
            self::$_defaultlanguage = $language;
        }

        /**
         * Get the default language
         * 
         * @access public
         * @param void
         * @return string $language
         */
        public static function GetDefault()
        {
            return self::$_defaultlanguage;
        }

        /**
         * Get the current language
         * 
         * @access public
         * @return string
         */
        public static function Get()
        {
            return self::$_language;
        }

        public static function get_answer_from_google($string, $from)
        {
           

//debug("We calling google ...");
//$url ="http://translate.google.fr/translate_t?text=Traduction%20automatique%20de%20pages%20web%0Aceci%20est%20un%20test&hl=fr&langpair=fr|en&tbb=1&ie=utf-8";
            $url = 'http://translate.google.fr/translate_t?text=' . urlencode($string) . '&hl=fr&langpair=' . $from . '|' . self::$_language . '&tbb=1&ie=utf-8';
            $url = 'https://translate.google.fr/?text=' . urlencode($string) . '&amp;hl=' . self::$_language . '&amp;langpair=' . $from . '%7Cfr&amp;tbb=1&amp;ie=utf-8';

            $UA = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0';
            $UA = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.117 Safari/537.36';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $UA);
            curl_setopt($ch, CURLOPT_REFERER, "http://www.starcraft.com/");
            $body = curl_exec($ch);
            curl_close($ch);

// if we send no user_agent google send sentence translated in default charset we asked for the language
//$body = iconv(self::charset[$to], "UTF-8", $body);


            $content = Grabber::getTagContent($body, '<span id=result_box', true);
            $content = str_replace('<br>', '', $content);
            $out = Grabber::getTagContents($content, '<span title="', true);

//verify that we exactly the same number of element
            $nb = explode("\n", trim($string));

//we check that we have same number of input and output
            if (count($nb) != count($out)) {
                throw new \Exception("GLI-009 : Problem with machine translation".trim($string).PHP_EOL.var_dump($out));
                return false;
            }

            return $out;
        }

        private function get_answer_from_reverso($string, $from)
        {
//http://www.reverso.net/text_translation.aspx?lang=FR
        }

        private function get_answer_from_worldlingo($string, $from)
        {
//http://www.worldlingo.com/
        }

        private function get_answer_from_traductionenligne($string, $from)
        {
//http://www.traduction-en-ligne.com/
        }

        private static function loadCashFile()
        {

            if (file_exists(self::$file_path)) {

                self::$_translations[self::$_md5File] = parse_ini_file(self::$file_path);

                self::$countNumberElemAtLoading[self::$_md5File] = count(self::$_translations[self::$_md5File]);
            } else {
//chargement du fichier de cache en fonction de la BDD
                $sql = "SELECT * FROM translation_" . strtolower(self::$_language) . " WHERE file_found ='" . self::$file . "'";
                
             
                
                $res23 = self::$_SQL->sql('default')->sql_query($sql);
                while ($ob = self::$_SQL->sql('default')->sql_fetch_object($res23)) {
                    self::$_translations[self::$_md5File][$ob->key] = $ob->text;
                }

                self::$countNumberElemAtLoading[self::$_md5File] = 0;
            }
        }

        private static function saveCashFile()
        {
            //if number of elem more important we save cash file

            foreach (self::$countNumberElemAtLoading as $md5 => $val) {
                if (count(self::$_translations[$md5]) > $val) {
                    self::write_ini_file(self::$_translations[$md5], self::$_path . "/" . self::$_language . "." . $md5 . ".ini");
                }
            }
        }

        function install()
        {
            
            /*
            $sql = "CREATE TABLE IF NOT EXISTS ".self::$_SQL->sql('default')->ESC."translation_main".self::$_SQL->sql('default')->ESC." (
  ".self::$_SQL->sql('default')->ESC."id".self::$_SQL->sql('default')->ESC." int(11) NOT NULL AUTO_INCREMENT,
  ".self::$_SQL->sql('default')->ESC."id_history_etat".self::$_SQL->sql('default')->ESC." int(11) NOT NULL,
  ".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC." char(40) NOT NULL,
  ".self::$_SQL->sql('default')->ESC."source".self::$_SQL->sql('default')->ESC." char(5) NOT NULL,
  ".self::$_SQL->sql('default')->ESC."destination".self::$_SQL->sql('default')->ESC." char(5) NOT NULL,
  ".self::$_SQL->sql('default')->ESC."text".self::$_SQL->sql('default')->ESC." text NOT NULL,
  ".self::$_SQL->sql('default')->ESC."date_inserted".self::$_SQL->sql('default')->ESC." datetime NOT NULL,
  ".self::$_SQL->sql('default')->ESC."date_updated".self::$_SQL->sql('default')->ESC." datetime NOT NULL,
  ".self::$_SQL->sql('default')->ESC."translate_auto".self::$_SQL->sql('default')->ESC." int(11) NOT NULL,
  ".self::$_SQL->sql('default')->ESC."file_found".self::$_SQL->sql('default')->ESC." varchar(255) NOT NULL,
  ".self::$_SQL->sql('default')->ESC."line_found".self::$_SQL->sql('default')->ESC." int(11) NOT NULL,
  PRIMARY KEY (".self::$_SQL->sql('default')->ESC."id".self::$_SQL->sql('default')->ESC."),
  UNIQUE KEY ".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC." (".self::$_SQL->sql('default')->ESC."key".self::$_SQL->sql('default')->ESC.",".self::$_SQL->sql('default')->ESC."destination".self::$_SQL->sql('default')->ESC.")
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32601 ; ";

            self::$_SQL->sql('default')->sql_query($sql);
            */
            
        }

        public static function write_ini_file($assoc_arr, $path, $has_sections = false)
        {
            $content = "";
            if ($has_sections) {
                foreach ($assoc_arr as $key => $elem) {
                    $content .= "[" . $key . "]\n";
                    foreach ($elem as $key2 => $elem2) {
                        if (is_array($elem2)) {
                            for ($i = 0; $i < count($elem2); $i++) {
                                $content .= $key2 . "[] = \"" . $elem2[$i] . "\"\n";
                            }
                        } else if ($elem2 == "")
                            $content .= $key2 . " = \n";
                        else
                            $content .= $key2 . " = \"" . $elem2 . "\"\n";
                    }
                }
            }
            else {
                foreach ($assoc_arr as $key => $elem) {
                    if (is_array($elem)) {
                        for ($i = 0; $i < count($elem); $i++) {
                            $content .= $key . "[] = \"" . $elem[$i] . "\"\n";
                        }
                    } else if ($elem == "")
                        $content .= $key . " = \n";
                    else
                        $content .= $key . " = \"" . $elem . "\"\n";
                }
            }

            if (!$handle = fopen($path, 'w')) {
                return false;
            }
            if (!fwrite($handle, $content)) {
                return false;
            }
            fclose($handle);
            return true;
        }

    }

}

namespace {

    use \Glial\I18n\I18n;

    function __($text, $lgfrom = "auto")
    {


        if ($lgfrom === "auto")
            $lgfrom = I18n::GetDefault();
        $calledFrom = debug_backtrace();
//return "<span id=\"".sha1($text)."\" lang=\"".$_LG->Get()."\">".$_LG->_($text,$lgfrom,$calledFrom[0]['file'],$calledFrom[0]['line'])."</span>";

        $file = str_replace(ROOT . "/", '', $calledFrom[0]['file']);
        $var = I18n::_($text, $lgfrom, $file, $calledFrom[0]['line']);



        //debug(I18n::$_translations);

        if (preg_match_all('#\[(\w+)]#', $var, $m)) {
//print_r( $m );
        }


        if (count($m[1]) > 0) {
            $replace_with = array();

            foreach ($m[1] as $species) {
                $scientific_name = str_replace("_", " ", $species);

                $sql = "SELECT b.text 
				FROM species_main a
				inner JOIN scientific_name_translation b ON a.id = b.id_species_main AND b.id_species_sub = 0 and b.is_valid=1
				INNER JOIN language c ON c.iso3 = b.language AND c.iso = '" . I18n::Get() . "'
			where a.scientific_name ='" . $scientific_name . "'";
                $res = I18n::getDb()->sql_query($sql);


                if (I18n::getDb()->sql_num_rows($res) == 1) {
                    $ob = I18n::getDb()->sql_fetch_object($res);
                    $replace_with[] = $ob->text . " (" . $scientific_name . ")";
                } else {
                    $replace_with[] = $scientific_name;
                }
            }

            $var = str_replace($m[0], $replace_with, $var);
        }
        return $var;
    }

}
