<?php

namespace Glial\Synapse;

class Config
{

    private $config_path;
    private $data = array();

    /**
     * (Glial 2.1)<br/>
     * Load the directory's path and all subdirectory with files whitch match ".config.php" or "*.config.ini.php" in their name
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     * @link http://www.glial-framework-php.org/en/manual/config.load.php
     * @param string $path <p>
     * A path.
     * </p>
     * <p>
     * On Windows, both slash (/) and backslash
     * (\) are used as directory separator character. In
     * other environments, it is the forward slash (/).
     * </p>
     * @return true
     * 
     */
    function load($path)
    {
        if (!is_dir($path)) {
            trigger_error("Config->load() Impossible to open this directory", E_USER_ERROR);
        }

        $this->config_path = $path;

        $list_config_file = glob($this->config_path . "*.config.php");
        foreach ($list_config_file as $file) {
            require $file;
        }

        $list_ini_file = glob($this->config_path . "*.config.ini.php");

        foreach ($list_ini_file as $file) {

            $file_elem = pathinfo($file);
            $file_main = explode('.', $file_elem['filename']);
            $this->data[$file_main[0]] = parse_ini_file($file, true);
        }
    }

    /**
     * (Glial 2.1)<br/>
     * get params of ini file set in argument
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     * @link http://www.glial-framework-php.org/en/manual/config.get.php
     * @param string $filename 
     * @return array
     * @see load
     */
    function get($filename)
    {
        if (empty($this->data[$filename])) {


            if ($filename === "db")
            {
                throw new \Exception("GLI-051 This ini file \"".'db.config.ini.php'."\" wasn't loaded or is empty (no connection configured)");
            }

            throw new \Exception("GLI-051 This ini file \"".$filename."\" wasn't loaded");
        }

        return $this->data[$filename];
    }

}
