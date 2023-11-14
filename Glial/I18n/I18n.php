<?php
/*
 * https://www.sitepoint.com/using-google-translate-api-php/
 * https://cloud.google.com/translate/docs/basic/translating-text?hl=fr
 *
 */

namespace Glial\I18n {


    use Glial\Extract\Grabber;
    use \App\Library\Debug;

    class I18n
    {
        const DATABASE   = DB_DEFAULT;
        const TABLE_SITE = "translation_glial";

// to prevent kick or/and ban from google
        private static $nb_google_call = 0;
        private static $DB;

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
            "sv" => "swedish",
            "ta" => "tamil",
            "te" => "telugu",
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
         * @var array
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
         * @var array
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
         * @var array
         */
        public static $languagesENGLISH          = array(
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
        private static $DEBUG                    = false;

        /**
         * Constructor
         *
         * @access private
         * @return void
         */
        public static function injectDb($db)
        {
            self::$DB = $db;
        }

        public static function getDb()
        {
            return self::$DB;
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

            // update query with surcharge in manually translated
            $sql = "SELECT target_text from translation_google WHERE ".self::$DB->ESC."key".self::$DB->ESC." ='".$key."' and ".self::$DB->ESC."target_language".self::$DB->ESC." = '".$to."'";
            $res = self::$DB->sql_query($sql);

            if (self::$DB->sql_num_rows($res) == 1) {
                $ob  = self::$DB->sql_fetch_object($res);
                $rep = $ob->target_text;
            } else if (self::$DB->sql_num_rows($res) == 0) {

                self::$_to_translate[$from][$key]['val']  = $text;
                self::$_to_translate[$from][$key]['file'] = self::$file;
                self::$_to_translate[$from][$key]['md5']  = self::$_md5File;
                self::$_to_translate[$from][$key]['line'] = self::$line;

                self::insertSource($from, $text, $key);
                //add to translation to request
                //ask_google

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
        /*
         *
         * DEPRECATED
         *
         * Remove reference !
         */


         
        public static function getTranslation($html = '')
        {
            //first loop to translate language by language

            return ($html);
            //to fix with google api

            foreach (self::$_to_translate as $from => $tab) {
                $string_to_translate = '';
                $extract             = array();

                $k = 0;

                $string = '';

                $tab_key    = array();
                $tab_string = array();

                $tab_key[]    = '<span id="'.$tab['key'].'">'.$tab['val'].'</span>';
                $tab_string[] = $str['val'];

                $tab_out = self::getAnswerFromApiGoogle($string, $from);

                if ($tab_out) {
                    $html = str_replace($tab_key, $tab_out, $html);
                    $i    = 0;
                    foreach ($result as $key => $data) {
                        self::$_translations[$data['md5']][$key] = $tab_out[$i];
                        self::$file                              = $data['file'];
                        self::$line                              = $data['line'];
                        self::$_md5File                          = $data['md5'];

                        self::save_db(self::$_language, $from, $tab_out[$i], $key, '1', $data['file'], $data['line']);
                        $i++;
                    }
                } else {
                    $html = str_replace($tab_key, $tab_string, $html);
                }
            }


            self::saveCashFile();

//self::$_to_translate = array();

            return ($html);
        }
/***/

/*
        private static function save_db($iso, $source, $text, $key, $translate_auto, $file, $line)
        {
            $data                                                        = array();
            $data["translation_".mb_strtolower($iso)]['key']             = $key;
            $data["translation_".mb_strtolower($iso)]['source']          = self::$DB->sql_real_escape_string($source);
            $data["translation_".mb_strtolower($iso)]['text']            = self::$DB->sql_real_escape_string($text);
            $data["translation_".mb_strtolower($iso)]['date_inserted']   = date("Y-m-d H:i:s");
            $data["translation_".mb_strtolower($iso)]['date_updated']    = date("Y-m-d H:i:s");
            $data["translation_".mb_strtolower($iso)]['translate_auto']  = intval($translate_auto);
            $data["translation_".mb_strtolower($iso)]['file_found']      = $file;
            $data["translation_".mb_strtolower($iso)]['id_history_etat'] = 1;
            $data["translation_".mb_strtolower($iso)]['line_found']      = intval($line);

            if (!self::$DB->sql_save($data)) {

                debug($data);
                debug(self::$DB->error);

                //mail("aurelien.lequoy@gmail.com", "Alstom : Bug with I18n", debug($data)."\n".json_encode($data));
            }
        }
*/


        /**
         * Method to translate a string
         *
         * @access public
         * @param string $string
         * @return string
         */
        public static function _($string_brut, $lgfrom, $file, $line)
        {

            if ($lgfrom != self::$_defaultlanguage) {
                $default_lg = $lgfrom;
            } else {
                $default_lg = self::$_defaultlanguage;
            }

            self::$file      = $file;
            self::$line      = $line;
            self::$_md5File  = md5(self::$file);
            self::$file_path = self::$_path."/".self::$_language.".".self::$_md5File.".ini";

            if (empty(self::$_translations[self::$_md5File])) {
                self::loadCashFile();
            }

            $string = trim($string_brut);
            $key    = sha1($lgfrom."-".$string);

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
                    $out                                        = true;
                    self::$_translations[self::$_md5File][$key] = $string;
                }

                if ($out) {
                    $res = self::$_translations[self::$_md5File][$key];
                } else {
                    $res = '<span id="'.$key.'">'.$string.'</span>';
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

        private static function loadCashFile()
        {
            if (file_exists(self::$file_path)) {
                self::$_translations[self::$_md5File] = parse_ini_file(self::$file_path);
                self::$countNumberElemAtLoading[self::$_md5File] = count(self::$_translations[self::$_md5File]);
            } else {
                //chargement du fichier de cache en fonction de la BDD


                //We get translation from google, then we overright with manual translation then we link 

                $sql = "SELECT tg.key,
                COALESCE(tm.text, tg.target_text) AS final_text
                FROM translation_google tg
                INNER JOIN translation_glial b ON b.key=tg.key AND tg.source_language = b.language 
                LEFT JOIN  translation_main tm ON tg.key = tm.key AND tg.target_language = tm.destination
                WHERE b.file_found ='".self::$file."' AND tg.target_language = '".strtolower(self::$_language)."'";

                $res23 = self::$DB->sql_query($sql);
                while ($ob    = self::$DB->sql_fetch_object($res23)) {
                    self::$_translations[self::$_md5File][$ob->key] = htmlentities($ob->final_text); //to prevent unwanted quote or double quote from transatlion
                }
                self::$countNumberElemAtLoading[self::$_md5File] = 0;
            }
        }

        private static function saveCashFile()
        {
            //hack the time to understand
            //return true;
            //if number of elem more important we save cash file

            foreach (self::$countNumberElemAtLoading as $md5 => $val) {

                //hack le temps de faire le menage ?
                if (!empty(self::$_translations[$md5])) {
                    if (count(self::$_translations[$md5]) > $val) {
                        self::writeIniFile(self::$_translations[$md5], self::$_path."/".self::$_language.".".$md5.".ini");
                    }
                }
            }
        }


        /*
        static public function install()
        {
            switch (self::$DB->getDriver()) {
                case 'mysql':
                    self::installMysql();
                    break;

                case 'oracle':
                    self::installOracle();
                    break;
            }
        }
*/

        public static function writeIniFile($assoc_arr, $path, $has_sections = false)
        {
            $content = "";
            if ($has_sections) {
                foreach ($assoc_arr as $key => $elem) {
                    $content .= "[".$key."]\n";
                    foreach ($elem as $key2 => $elem2) {
                        if (is_array($elem2)) {
                            for ($i = 0; $i < count($elem2); $i++) {
                                $content .= $key2."[] = \"".$elem2[$i]."\"\n";
                            }
                        } else if ($elem2 == "") {
                            $content .= $key2." = \n";
                        } else {
                            $content .= $key2." = \"".$elem2."\"\n";
                        }
                    }
                }
            } else {
                foreach ($assoc_arr as $key => $elem) {
                    if (is_array($elem)) {
                        for ($i = 0; $i < count($elem); $i++) {
                            $content .= $key."[] = \"".$elem[$i]."\"\n";
                        }
                    } else if ($elem == "") {
                        $content .= $key." = \n";
                    } else {
                        $content .= $key." = \"".$elem."\"\n";
                    }
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


        /*
        static public function installMysql()
        {
            $lang         = self::$languages;
            unset($lang['auto']);
            $lang['main'] = 'true';

            foreach ($lang as $iso => $libelle) {

                $sql = "CREATE TABLE IF NOT EXISTS `translation_".mb_strtolower($iso)."` (
              `id` int(11) NOT NULL auto_increment,
              `id_history_etat` int NOT NULL,
              `key` char(40) NOT NULL,
              `source` char(5) NOT NULL,
              `destination` char(5),
              `text` text NOT NULL,
              `date_inserted` datetime NOT NULL,
              `date_updated` datetime NOT NULL,
              `translate_auto` int(11) NOT NULL,
              `file_found` varchar(255) NOT NULL,
              `line_found` int NOT NULL,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `key` (`key`,`file_found`),
              INDEX `id_history_etat` (`id_history_etat`)
              )ENGINE=InnoDB DEFAULT CHARSET=utf8;";

                self::$DB->sql_query($sql);
            }
        }*/


        /*
        static public function installOracle()
        {

            $lang         = self::$languages;
            unset($lang['auto']);
            $lang['main'] = 'true';

            foreach ($lang as $iso => $libelle) {

                $sql = "CREATE TABLE translation_".mb_strtolower($iso)." (
 id NUMBER(11) NOT NULL ,
 id_history_etat NUMBER(11) NOT NULL,
 key varchar2(40) NOT NULL,
 source varchar2(5) NOT NULL,
 destination varchar2(5) NOT NULL,
 text nvarchar2(2000) NOT NULL,
 date_inserted date NOT NULL,
 date_updated date NOT NULL,
 translate_auto NUMBER(11) NOT NULL,
 file_found varchar2(255) NOT NULL,
 line_found NUMBER(11) NOT NULL,
 PRIMARY KEY (id)
);

CREATE SEQUENCE translation_".mb_strtolower($iso)."_seq START WITH 1 INCREMENT BY 1;


CREATE OR REPLACE TRIGGER trx_translation_".mb_strtolower($iso)."
BEFORE INSERT ON translation_".mb_strtolower($iso)."
FOR EACH ROW

BEGIN
  SELECT translation_".mb_strtolower($iso)."_seq.NEXTVAL
  INTO   :new.id
  FROM   dual;
END;
/";

                self::$DB->sql_query($sql);
            }
        }
        */


        /*

        static public function unInstallMysql()
        {
            $lang         = self::$languages;
            unset($lang['auto']);
            $lang['main'] = 'true';

            $tables = self::$DB->getListTable()['table'];

            foreach ($lang as $iso => $libelle) {


                if (in_array("translation_".mb_strtolower($iso), $tables)) {
                    $sql = "DROP TABLE `translation_".mb_strtolower($iso)."`;";

                    self::$DB->sql_query($sql);
                }
            }
        }

        static public function unInstall()
        {

            switch (self::$DB->getDriver()) {
                case 'mysql':
                    self::unInstallMysql();
                    break;

                case 'oracle':
                    self::unInstallOracle();
                    break;
            }
        }*/

        static public function setDebug($debug = \I18n::DEBUG)
        {
            self::$DEBUG = $debug;
        }
        /*
         *
         * The goal is to reference all translation in application to translate all in same time
         *
         */

        static public function insertSource($source, $text, $key)
        {

            $sql = "SELECT /* $text */ * FROM ".self::$DB->ESC.self::TABLE_SITE.self::$DB->ESC." WHERE ".self::$DB->ESC."key".self::$DB->ESC." ='".$key."'"
                ." AND ".self::$DB->ESC."language".self::$DB->ESC." = '".$source."' "
                ." AND  ".self::$DB->ESC."file_found".self::$DB->ESC." = '".self::$file."'"
                ." AND ".self::$DB->ESC."line_found".self::$DB->ESC." = '".self::$line."'";

            $res = self::$DB->sql_query($sql);

            if (self::$DB->sql_num_rows($res) == 0) {
                $sql2 = "INSERT IGNORE INTO ".self::$DB->ESC.self::TABLE_SITE.self::$DB->ESC."
                (".self::$DB->ESC."key".self::$DB->ESC.",".self::$DB->ESC."text".self::$DB->ESC.",".self::$DB->ESC."language".self::$DB->ESC." ,"
                    ." ".self::$DB->ESC."file_found".self::$DB->ESC." , ".self::$DB->ESC."line_found".self::$DB->ESC." )
                    VALUES ('".$key."','".self::$DB->sql_real_escape_string($text)."','".self::$DB->sql_real_escape_string($source)."','".self::$file."','".self::$line."');";

                self::$DB->sql_query($sql2);
            }
        }
    }
}

namespace {

    use \Glial\I18n\I18n;

    function __($text, $lgfrom = "auto")
    {
        //return $text;
        if (!LANGUAGE_ACTIVE) {
            return $text;
        }

        $calledFrom = debug_backtrace();

        if ($text !== strip_tags($text)) {
            throw new \Exception("GLI-145 : html tag not supported for translation : '".htmlentities($text)."' (".$calledFrom[0]['file'].":".$calledFrom[0]['line'].")");
        }

        if ($lgfrom === "auto") {
            $lgfrom = I18n::GetDefault();
        }

        $file = str_replace(ROOT."/", '', $calledFrom[0]['file']);

        $var = I18n::_($text, $lgfrom, $file, $calledFrom[0]['line']);
        return $var;


        if (preg_match_all('#\[(\w+)]#', $var, $m)) {

        }

        if (count($m[1]) > 0) {
            $replace_with = array();

            foreach ($m[1] as $species) {
                $scientific_name = str_replace("_", " ", $species);

                $sql = "SELECT b.text
				FROM species_main a
				inner JOIN scientific_name_translation b ON a.id = b.id_species_main AND b.id_species_sub = 0 and b.is_valid=1
				INNER JOIN language c ON c.iso3 = b.language AND c.iso = '".I18n::Get()."'
			where a.scientific_name ='".$scientific_name."'";
                $res = I18n::getDb()->sql_query($sql);

                if (I18n::getDb()->sql_num_rows($res) == 1) {
                    $ob             = I18n::getDb()->sql_fetch_object($res);
                    $replace_with[] = $ob->text." (".$scientific_name.")";
                } else {
                    $replace_with[] = $scientific_name;
                }
            }

            $var = str_replace($m[0], $replace_with, $var);
        }
        return $var;
    }
}

