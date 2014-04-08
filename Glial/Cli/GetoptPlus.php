<?php

/**
 * Command-line option parser - Console Getopt+ (Getopt Plus)
 *
 * This package is (1) a PHP5 port/rewrite of Console_Getopt, (2) with added
 * functionalities, and (3) with a Web interface to run getopt-like shell
 * commands through a browser (not implemented yet).
 *
 * (1) Console_getoptPlus:getopt() is a replacement for Console_getopt:getopt().
 * Same for getopt2() and readPHPArgv(). It returns PEAR_Exception instead of
 * PEAR_Error. Error messages are the same.
 *
 * (2) GetoptPlus:getoptplus uses an array-based description of the command. It can
 * generates the command usage/help automaticly. It can return the parsed
 * options and parameters in an associative array. It can be set to accept
 * option shortcut names.
 *
 * Fully tested with phpUnit. Code coverage test close to 100%.
 *
 * Usage is fully documented in docs/examples files.
 *
 * PHP version 5
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + The names of its contributors may not be used to endorse or promote
 * products derived from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Console
 * @package   Console_GetoptPlus
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2008 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   SVN: $Id: GetoptPlus.php 50 2008-01-11 09:01:57Z mcorne $
 * @link      http://pear.php.net/package/Console_GetoptPlus
 */

require_once 'Console/GetoptPlus/Getopt.php';
require_once 'Console/GetoptPlus/Help.php';

/**
 * Parsing of a command line based on the command description in an array.
 * See more examples in docs/examples.
 *
 * Code Example:
 * <code>
 * require_once 'Console/GetoptPlus.php';
 *
 * try {
 *    $config = array(
 *      'header' => array('The command xyz is used to...',
 *        'Note that the header and the usage are optional.'),
 *      'usage' => array('--foo', '--bar <arg> -c [arg]'),
 *      'options' => array(
 *        array('long' => 'foo', 'type' => 'noarg', 'desc' => array(
 *          'An option without argument with only the long',
 *          'name defined.')),
 *        array('long' => 'bar', 'type' => 'mandatory', 'short' => 'b',
 *          'desc' => array('arg',
 *            'A mandatory option with both the long and',
 *            'the short names defined.')),
 *        array('short' => 'c', 'type' => 'optional',
 *          'desc' => array('arg',
 *            'An option with an optional argument with only',
 *            'the short name defined.'))),
 *        'parameters' => array('[param1] [param2]',
 *          'Some additional parameters.'),
 *        'footer' => array('Some additional information.',
 *          'Note that the footer is optional.'),
 *    );
 *
 * $options = Console_Getoptplus::getoptplus($config);
 * // some processing here...
 * print_r($options);
 * }
 * catch(Console_GetoptPlus_Exception $e) {
 *    $error = array($e->getCode(), $e->getMessage());
 *    print_r($error);
 * }
 * </code>
 *
 * Run:
 * <pre>
 * #xyz --help
 * #xyz -h
 * #xyz --foo -b car -c
 * #xyz --foo -b car -c param1
 * #xyz --foo -b car -cbus param1
 * #xyz --foo -b car -c=bus param1
 * #xyz --bar car param1 param2
 * #xyz --bar car -- param1 param2
 * #xyz --bar=car param1 param2
 * </pre>
 *
 * @category  Console
 * @package   Console_GetoptPlus
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2008 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release:
 * @package   _version@
 * @link      http://pear.php.net/package/Console_GetoptPlus
 * @todo      create a Web interface to getopt-like shell command
 * @todo      optionally split long usage lines to fit within 80 columns
 * @todo      optionally change the start position of the options usage
 */
class Console_GetoptPlus extends Console_GetoptPlus_Getopt
{
    /**
     * The long name to short option name cross-references
     *
     * @var    array
     * @access private
     */
    private $long2short = array();

    /**
     * The short name to long option name cross-references
     *
     * @var    array
     * @access private
     */
    private $short2long = array();

    /**
     * Verifies the option settings are valid
     *
     * Adds the "help" option if missing.
     *
     * @param  array  $config the command configuration, see the configuration
     *                        definition/example in the class Doc Block
     * @return array  the options configurations, the updated 'options' subarray,
     *                e.g. $config['options']
     * @access public
     */
    public function checkOptionsConfig($config)
    {
        $optionsConfig = empty($config['options'])? array() : $config['options'];

        $isHelp = false;
        $isH = false;

        foreach($optionsConfig as $idx => &$option) {
            // verifies there is at least a short or long option name
            isset($option['long']) or isset($option['short']) or
            self::exception('missing', $idx);

            if (isset($option['long']) and isset($option['short'])) {
                // creates cross-references between long and short options names
                $this->short2long[$option['short']] = $option['long'];
                $this->long2short[$option['long']] = $option['short'];
            }

            if (isset($option['type'])) {
                // the option has a type, checks it is valid
                $type = $option['type'];
                in_array($type, array('noarg', 'mandatory', 'optional')) or
                self::exception('type', $type);
            } else {
                // defaults to no argument
                $option['type'] = 'noarg';
            }
            // determines if --help is provided
            $isHelp or isset($option['long']) and
            $option['long'] == 'help' and $isHelp = true;
            // determines if -h is used
            $isH or isset($option['short']) and
            $option['short'] == 'h' and $isH = true;
        }

        if (!$isHelp) {
            // no help option, adds the default --help, and -h if unused
            $help = array('long' => 'help', 'type' => 'noarg', 'desc' => 'This help.');
            $isH or $help['short'] = 'h';
            $optionsConfig[] = $help;

            if (isset($help['long']) and isset($help['short'])) {
                $this->short2long[$help['short']] = $help['long'];
                $this->long2short[$help['long']] = $help['short'];
            }
        }

        return $optionsConfig;
    }

    /**
     * Extracts the long or short option names and types
     *
     * Validates the option name against the pattern. A short option name has
     * a single letter. A long option name has one of more alphanumerical
     * letters.
     *
     * @param  array  $optionsConfig the options configurations, see the
     *                               configuration definition/example in the
     *                               class Doc Block, e.g. $config['options']
     * @param  string $defType       the option name type: "short" or "long"
     * @param  string $pattern       the validation pattern
     * @return array  the option names list, e.g. array("a" => "noarg", ...)
     * @access public
     */
    public function createOptionsDef($optionsConfig, $defType, $pattern)
    {
        $optionsDef = array();

        foreach($optionsConfig as $option) {
            if (isset($option[$defType])) {
                // the option has a name
                $name = $option[$defType];
                // checks the option name syntax is valid and is not a duplicate
                preg_match($pattern, $name) or self::exception('invalid', $option);
                isset($duplicates[$name]) and self::exception('duplicate', $name);

                $duplicates[$name] = true;
                $optionsDef[$name] = $option['type'];
            }
        }

        return $optionsDef;
    }

    /**
     * Wraps the exception call
     *
     * @return void
     * @access private
     * @throws Console_GetoptPlus_Exception Exception
     * @static
     */
    private static function exception()
    {
        $error = func_get_args();
        throw new Console_GetoptPlus_Exception($error);
    }

    /**
     * Parses the command line
     *
     * See the configuration definition/example in the class Doc Block.
     *
     * Example 1: returning an index array
     * <code>
     * array(
     *    [0] => array(
     *      [0] => array([0] => "--foo", [1] => null),
     *      [1] => array([0] => "b", [1] => "car"),
     *      [2] => array([0] => "c", [1] => null)),
     *    [1] => array([0] => "param1", [1] => "param2")
     * );
     * </code>
     *
     * Example 2: returning an associative array
     * <code>
     * array(
     *    [0] => array("foo" => null, "bar" => "car", "c" => null),
     *    [1] => array([0] => "param1", [1] => "param2")
     * );
     * </code>
     *
     * @param  array   $config      the command configuration, see the configuration
     *                              definition/example in the class Doc Block
     * @param  string  $convertName returns short option names if set to
     *                              "long2short", long ones if set to "short2long",
     *                              as in the command line by default
     * @param  boolean $returnAssoc returns an associative array if true,
     *                              an index array if false
     * @param  string  $ambiguity   directive to handle option names ambiguity,
     *                              e.g. "--foo" and "--foobar":
     *                              <ul>
     *                              <li>"loose": allowed if "--foo" does not
     *                              accept an argument, this is the default
     *                              behaviour</li>
     *                              <li>"strict": no ambiguity allowed</li>
     *                              <li>"shortcuts": implies "strict", the use of
     *                              partial option names is allowed,
     *                              e.g. "--f" or "--fo" instead of "--foo"</li>
     *                              </ul>
     * @param  boolean $exitHelp    if "--help" is one of the options:
     *                              <ul>
     *                              <li>true: displays the command usage and exits</li>
     *                              <li>false: returns the command usage as:
     *                              <ul>
     *                              <li>an index array, e.g.
     *                              array([0] => array([0] => array("h", "Usage:...")))</li>
     *                              <li>an associative, e.g.
     *                              array([0] => array("h" => "Usage:..."))</li>
     *                              </ul></li>
     *                              </ul>
     * @return array   the parsed options, their arguments and parameters
     * @access public
     * @static
     */
    public static function getoptplus($config = array(), $convertName = '',
        $returnAssoc = false, $ambiguity = '', $exitHelp = true)
    {
        $getopt = new self;

        return $getopt->process($config, $convertName, $returnAssoc,
            $ambiguity, $exitHelp);
    }

    /**
     * Parses the long option names and types
     *
     * Verifies the option names have one of more alphanumerical characters.
     *
     * @param  array  $optionsConfig the options configurations, see the
     *                               configuration definition/example in the
     *                               class Doc Block, e.g. $config['options']
     * @return array  the option names list, e.g. array("foo" => "noarg", ...)
     * @access public
     */
    public function parseLongOptionsDef($optionsConfig)
    {
        return $this->createOptionsDef($optionsConfig, 'long', '~^\w+$~');
    }

    /**
     * Parses the short option names and types
     *
     * Verifies the option names have one alphanumerical character.
     *
     * @param  array  $optionsConfig the options configurations, see the
     *                               configuration definition/example in the
     *                               class Doc Block, e.g. $config['options']
     * @return array  the option names list, e.g. array("f" => "noarg", ...)
     * @access public
     */
    public function parseShortOptionsDef($optionsConfig)
    {
        return $this->createOptionsDef($optionsConfig, 'short', '~^\w$~');
    }

    /**
     * Parses the command line
     *
     * See getoptplus() for a complete description.
     *
     * @param  array   $config      the command configuration
     * @param  string  $convertName returns short option names if set to
     *                              "long2short", long ones if set to "short2long",
     *                              as in the command line by default
     * @param  boolean $returnAssoc returns an associative array if true,
     *                              an index array if false
     * @param  string  $ambiguity   directive to handle option names ambiguity:
     *                              "loose", "strict", or "shortcuts"
     * @param  boolean $exitHelp    same as getoptplus()
     * @return array   the parsed options, their arguments and parameters
     * @access public
     */
    public function process($config = array(), $convertName = '',
        $returnAssoc = false, $ambiguity = '', $exitHelp = true)
    {
        // extracts the command arguments, including the command name
        $args = self::readPHPArgv();
        $command = array_shift($args);
        // checks the options configurations, parses the command
        $config['options'] = $optionsConfig = $this->checkOptionsConfig($config);
        $options = parent::process($args, $optionsConfig, $optionsConfig, $ambiguity);
        // tidies the options
        $options[0] = $this->tidyOptions($options[0], $convertName, $returnAssoc);

        if (is_string($options[0])) {
            // a request for help, builds the command usage,
            $help = Console_GetoptPlus_Help::get($config, $command);
            // exits/displays the command usage or returns it
            $exitHelp and exit($help);
            $name = $options[0];
            $options[0] = $returnAssoc? array($name => $help) : array(array($name, $help));
        }

        return $options;
    }

    /**
     * Tidies the command arguments
     *
     * See getoptplus() for a complete description of the returned options.
     *
     * @param  array   $options     the parsed options arguments, e.g.
     *                              <pre>
     *                              array(
     *                              [0] => array([0] => "--foo", [1] => null),
     *                              [1] => array([0] => "b", [1] => "car"),
     *                              [2] => array([0] => "c", [1] => null))
     *                              >/pre>
     * @param  string  $convertName returns short option names if set to
     *                              "long2short", long ones if set to "short2long",
     *                              as in the command line by default
     * @param  boolean $returnAssoc returns an associative array if true,
     *                              an index array if false
     * @return array   the tidied options arguments
     * @access public
     */
    public function tidyOptions($options, $convertName = '', $returnAssoc = false)
    {
        // verifies the conversion is valid
        empty($convertName) or $convertName == 'long2short' or
        $convertName == 'short2long' or self::exception('convert', $convertName);

        $tidied = array();
        foreach($options as $option) {
            // extracs the option name and value, removes the long option prefix
            list($name, $value) = $option;
            $isLong = substr($name, 0, 2) == '--' and $name = substr($name, 2);

            if ($isLong) {
                // a long option
                if ($name == 'help') {
                    // the help option
                    if ($convertName == 'long2short' and isset($this->long2short['help'])) {
                        return $this->long2short['help'];
                    } else {
                        return '--help';
                    }
                }
                // converts to a short option if requested and possible
                if ($convertName == 'long2short' and isset($this->long2short[$name])) {
                    $name = $this->long2short[$name];
                    $isLong = false;
                }
            } else {
                // a short option
                if ($convertName == 'short2long') {
                    // converts to a long one if possible
                    if (isset($this->short2long[$name])) {
                        $name = $this->short2long[$name];
                        $isLong = true;
                    }

                    if ($isLong and $name == 'help') {
                        // the help option
                        return '--help';
                    }
                } else if (isset($this->short2long[$name]) and
                        $this->short2long[$name] == 'help') {
                    // the help option
                    return $name;
                }
            }

            if ($returnAssoc) {
                // converts the arguments to an associative array with
                // the argument name as the key, converts a NULL values to an
                // empty string to make it easier to use with isset()
                $tidied[$name] = is_null($value)? '' : $value;
            } else {
                // leaves the arguments as per per Console_Getopt::doGetopt()
                // format and prefixes long options with --
                $isLong and $name = '--' . $name;
                $tidied[] = array($name, $value);
            }
        }

        return $tidied;
    }
}

?>