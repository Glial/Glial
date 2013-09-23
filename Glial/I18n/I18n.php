<?php

namespace Glial\I18n;

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
    private static $_translations = array();

    /**
     * Array storage with all words / senteces to translate in one row at end
     * 
     * @var array
     */
    public static $_table_language = array();

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

    /*


      require_once '../../src/Google_Client.php';
      require_once '../../src/contrib/Google_TranslateService.php';

      $client = new Google_Client();
      $client->setApplicationName('Google Translate PHP Starter Application');

      // Visit https://code.google.com/apis/console?api=translate to generate your
      // client id, client secret, and to register your redirect uri.
      // $client->setDeveloperKey('insert_your_developer_key');
      $service = new Google_TranslateService($client);

      $langs = $service->languages->listLanguages();
      print "<h1>Languages</h1><pre>" . print_r($langs, true) . "</pre>";

      $translations = $service->translations->listTranslations('Hello', 'hi');
      print "<h1>Translations</h1><pre>" . print_r($translations, true) . "</pre>";


     *
     * 
     */

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

        $sql = "CREATE TABLE IF NOT EXISTS `translation_" . mb_strtolower($iso) . "` (
		`id` int(11) NOT NULL auto_increment,
		`id_history_etat` int NOT NULL,
		`key` char(40) NOT NULL,
		`source` char(2) NOT NULL,
		`text` text NOT NULL,
		`date_inserted` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		`translate_auto` int(11) NOT NULL,
		`file_found` varchar(255) NOT NULL,
		`line_found` int NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `key` (`key`),
		INDEX `id_history_etat` (`id_history_etat`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        self::$_SQL->sql_query($sql);
    }

    private static $greeting = 'Hello';
    private static $initialized = false;

    private static function initialize()
    {
        if (self::$initialized)
            return;

        self::$greeting .= ' There!';
        self::$initialized = true;
    }

    /**
     * Translate a string
     *
     * @access public
     * @param string $from
     * @param string $to
     * @param string $text
     * @return string
     */
    public static function Translate($from, $to, $text, $key)
    {

        self::insert_db($from, $from, $text, $key, '0');


        $translate_auto = 1;

        $sql = "SELECT text,translate_auto from translation_main WHERE `key` ='" . $key . "' and `destination` = '" . $to . "'";
        $res = self::$_SQL->sql_query($sql);

        if (self::$_SQL->sql_num_rows($res) == 1) {
            $ob = self::$_SQL->sql_fetch_object($res);
            $rep = $ob->text;
            $translate_auto = $ob->translate_auto;
        } else if (self::$_SQL->sql_num_rows($res) == 0) {
            self::$_to_translate[$from][$key] = $text;

            return false;
        } else {
            die("We have a problem !");
        }

        self::insert_db($to, $from, $rep, $key, $translate_auto);


        self::$_translations[$key] = $rep;

        self::save($to);
        // Return translation

        return true;
    }

    public static function getTranslation($html = '')
    {

        // debug(self::$_to_translate);

        foreach (self::$_to_translate as $from => $tab) {


            $string_to_translate = '';
            $extract = array();

            $k = 0;

            // to escape => ERROR 414 (That’s an error) from google The requested URL /translate_t... is too large to process. 
            foreach ($tab as $key => $elem) {
                $nb_char = strlen($string_to_translate) + strlen($elem);

                if ($nb_char < GOOGLE_NB_CHAR_MAX) {
                    $string_to_translate .= $elem;
                    $extract[$k][$key] = $elem;
                } else {
                    $k++;
                    $string_to_translate = $elem;
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
                    $tab_key[] = $key;
                    $tab_string[] = $string;
                    $string = $string . "\n" . $str;
                }

                $tab_out = self::get_answer_from_google($string, $from);


                if ($tab_out) {
                    $html = str_replace($tab_key, $tab_out, $html);

                    $i = 0;
                    foreach ($result as $key => $str) {
                        self::$_translations[$key] = $tab_out[$i];

                        self::save_db(self::$_language, $from, $tab_out[$i], $key, '1');
                        $i++;
                    }


                    self::save(self::$_language);
                } else {
                    $html = str_replace($tab_key, $tab_string, $html);
                }
            }
        }

        self::$_to_translate = array();

        return ($html);
    }

    private static function save_db($iso, $source, $text, $key, $translate_auto)
    {
        $data = array();
        $data["translation_" . mb_strtolower($iso)]['key'] = $key;
        $data["translation_" . mb_strtolower($iso)]['source'] = self::$_SQL->sql_real_escape_string($source);
        $data["translation_" . mb_strtolower($iso)]['text'] = self::$_SQL->sql_real_escape_string($text);
        $data["translation_" . mb_strtolower($iso)]['date_inserted'] = date("c");
        $data["translation_" . mb_strtolower($iso)]['date_updated'] = date("c");
        $data["translation_" . mb_strtolower($iso)]['translate_auto'] = intval($translate_auto);
        $data["translation_" . mb_strtolower($iso)]['file_found'] = self::$file;
        $data["translation_" . mb_strtolower($iso)]['id_history_etat'] = 1;
        $data["translation_" . mb_strtolower($iso)]['line_found'] = intval(self::$line);

        self::$_SQL->set_history_type(6);
        self::$_SQL->set_history_user(11);

        if (!self::$_SQL->sql_save($data)) {
            debug($data);
            debug(self::$_SQL->sql_error());
            die("erreur enregistrement");
        }
    }

    //deprecated
    private static function insert_db($iso, $source, $text, $key, $translate_auto)
    {

        $sql = "INSERT IGNORE INTO `translation_" . mb_strtolower($iso) . "`
		SET `key` ='" . $key . "',
		`source` = '" . self::$_SQL->sql_real_escape_string($source) . "',
		`text` = '" . self::$_SQL->sql_real_escape_string($text) . "',
		`date_inserted` = now(),
		`date_updated` = now(),
		`translate_auto` = '" . $translate_auto . "',
		`file_found` = '" . self::$file . "',
		`id_history_etat` = 1,
		`line_found` ='" . self::$line . "'";

        self::$_SQL->sql_query($sql);
    }

    public static function testTable($iso)
    {

        //$sql = "SHOW TABLES WHERE Tables_in_" . SQL_DATABASE . " = 'translation_" . strtolower($iso) . "'";
        //$res = self::$_SQL->sql_query($sql);

        $ret = self::$_SQL->getListTable();


        if (in_array("translation_" . strtolower($iso), $ret['table'])) {
            true;
        } else {
            self::initiate($iso);
            return false;
        }


        self::$_table_language[] = $iso;
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


        if (!in_array($lgfrom, self::$_table_language)) {
            self::testTable($lgfrom);
        }


        if ($lgfrom != self::$_defaultlanguage) {
            $default_lg = $lgfrom;
        } else {
            $default_lg = self::$_defaultlanguage;
        }


        self::$file = $file;
        self::$line = $line;


        $string = str_replace("\r\n", " ", $string);
        $string = str_replace("\n", " ", $string);


        $elem = $string;

        $res = array();

        //foreach ($tab as $elem)
        //{

        $key = sha1($elem);

        if (isset(self::$_translations[$key])) {

            $res[] = self::$_translations[$key];
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
                self::$_translations[$key] = $string;
            }

            if ($out) {
                self::save(self::$_language);
                $res[] = self::$_translations[$key];
            } else {
                $res[] = $key;
            }
        }
        //}
        //return (implode(" ! ", $res));

        return $res[0];
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


        if (!in_array(self::$_language, self::$_table_language)) {
            self::testTable(self::$_language);
        }

        if (!in_array(self::$_defaultlanguage, self::$_table_language)) {
            self::testTable(self::$_defaultlanguage);
        }

        $path = self::$_path . "/" . $language . ".csv";


        if (file_exists($path)) {

            $content = file_get_contents($path);
            $content = explode("\n", $content);
            foreach ($content as $line) {
                $parts = explode("||", $line);
                $key = isset($parts[0]) ? $parts[0] : "";
                if (isset($parts[1])) {

                    $value = $parts[1];
                } else {
                    $value = "";
                }
                self::$_translations[$key] = $value;
            }
        } else {

            //chargement du fichier de cache en fonction de la BDD

            $sql = "SELECT * FROM `translation_" . strtolower($language) . "`";
            $res23 = self::$_SQL->sql_query($sql);

            while ($ob = self::$_SQL->sql_fetch_object($res23)) {
                self::$_translations[$ob->key] = $ob->text;
            }

            self::save(self::$_language);
        }
    }

    /**
     * Save translations back to csv
     * 
     * @access public
     * @return void
     */
    private static function save($LangageOutput)
    {
        $array = "";
        foreach (self::$_translations as $key => $value) {
            $array[] = $key . "||" . $value;
        }
        $path = self::$_path . "/" . $LangageOutput . ".csv";

        if (!empty($array)) {
            file_put_contents($path, implode("\n", $array));
        }
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
        self::initiate($language);
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

    private static function get_answer_from_google($string, $from)
    {

        //debug("We calling google ...");
        //$url ="http://translate.google.fr/translate_t?text=Traduction%20automatique%20de%20pages%20web%0Aceci%20est%20un%20test&hl=fr&langpair=fr|en&tbb=1&ie=utf-8";
        $url = 'http://translate.google.fr/translate_t?text=' . urlencode($string) . '&hl=fr&langpair=' . $from . '|' . self::$_language . '&tbb=1&ie=utf-8';

        $UA = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $UA);
        curl_setopt($ch, CURLOPT_REFERER, "http://www.esysteme.com/translate.php");
        $body = curl_exec($ch);
        curl_close($ch);

        // if we send no user_agent google send sentence translated in default charset we asked for the language
        //$body = iconv(self::charset[$to], "UTF-8", $body);


        $content = Grabber::getTagContent($body, '<body', true);
        $content = Grabber::getTagContent($content, '<form', true);
        $content = Grabber::getTagContent($content, '<span id=result_box', true);

        $elem = Grabber::getTagContents($body, '<span title="', true);

        $output = "";
        foreach ($elem as $value) {
            $output .= strip_tags($value, "<br>");
        }

        $out = explode("<br>", $output);

        //verify that we exactly the same number of element
        $nb = explode("\n", trim($string));

        //we check that we have same number of input and output
        if (count($nb) != count($out)) {
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

}

