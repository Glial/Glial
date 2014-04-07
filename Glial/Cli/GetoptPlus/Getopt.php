<?php

/**
 * Console Getopt
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
 * @version   SVN: $Id: Getopt.php 48 2008-01-10 15:32:56Z mcorne $
 * @link      http://pear.php.net/package/Console_GetoptPlus
 */

require_once 'Console/GetoptPlus/Exception.php';

/**
 * Parsing of a command line.
 *
 * See more examples in docs/examples.
 *
 * Code Example 1:
 * <code>
 * require_once 'Console/GetoptPlus.php';
 *
 * try {
 *    $shortOptions = "b:c::";
 *    $longOptions = array("foo", "bar=");
 *    $options = Console_Getoptplus::getopt($config, $shortOptions, $longOptions);
 *    // some processing here...
 *    print_r($options);
 * }
 * catch(Console_GetoptPlus_Exception $e) {
 *    $error = array($e->getCode(), $e->getMessage());
 *    print_r($error);
 * }
 * </code>
 *
 * Code Example 2:
 * <code>
 * require_once 'Console/GetoptPlus/Getopt.php';
 *
 * try {
 *    $shortOptions = "b:c::";
 *    $longOptions = array("foo", "bar=");
 *    $options = Console_GetoptPlus_Getopt::getopt($config, $shortOptions, $longOptions);
 *    // some processing here...
 *    print_r($options);
 * }
 * catch(Console_GetoptPlus_Exception $e) {
 *    $error = array($e->getCode(), $e->getMessage());
 *    print_r($error);
 * }
 * </code>
 *
 * Run:
 * <pre>
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
 * @see       Console_Getopt
 */
class Console_GetoptPlus_Getopt
{
    /**
     * The list of ambigous option names
     *
     * @var    array
     * @access private
     */
    private $ambigous;

    /**
     * The command arguments
     *
     * @var    array
     * @access private
     */
    private $args;

    /**
     * The long option names
     *
     * @var    array
     * @access private
     */
    private $longOptionsDef;

    /**
     * The parsed options
     *
     * @var    array
     * @access private
     */
    private $options;

    /**
     * The option shortcut names
     *
     * @var    array
     * @access private
     */
    private $shortcuts;

    /**
     * The short option names and their definition
     *
     * @var    array
     * @access private
     */
    private $shortOptionsDef;

    /**
     * The option types
     *
     * @var    array
     * @access private
     */
    private $type = array(// /
        false => 'noarg',
        '=' => 'mandatory', ':' => 'mandatory',
        '==' => 'optional', '::' => 'optional',
        );

    /**
     * Creates the option shorcut names
     *
     * @param  array  $longOptionsDef the long option names
     * @param  string $ambiguity      directive to handle option names ambiguity
     * @return array  the option shorcuts and the ambigous options
     * @access public
     */
    public function createShorcuts($longOptionsDef, $ambiguity)
    {
        $shortcuts = array();
        $ambigous = array();

        if ($ambiguity == 'shortcuts') {
            foreach(array_keys($longOptionsDef) as $name) {
                // splits the option name in characters to build the name
                // substring combinations, e.g. foo => f, fo, foo
                $subName = '';
                foreach(str_split($name) as $char) {
                    $subName .= $char;

                    if (isset($ambigous[$subName])) {
                        // adds the shortcut to the list of ambigous shortcuts
                        $ambigous[$subName][] = $name;
                    } else if (isset($shortcuts[$subName])) {
                        // there is already a shortcut, adds the previous one
                        // and the current one in the list of ambigous shortcuts
                        $ambigous[$subName] = array($shortcuts[$subName], $name);
                        unset($shortcuts[$subName]);
                    } else {
                        // creates the shorcut entry
                        $shortcuts[$subName] = $name;
                    }
                }
            }
            // checks if some options are ambigous, e.g. --foo --foobar
            $names = array_intersect_key($longOptionsDef, $ambigous) and
            self::exception('ambigous', key($names));
        }

        return array($shortcuts, $ambigous);
    }

    /**
     * Parses the command line
     *
     * See getopt() for a complete description.
     *
     * @param  numeric $version      the getopt version: 1 or 2
     * @param  array   $args         the arguments
     * @param  string  $shortOptions the short options definition, e.g. "ab:c::"
     * @param  array   $longOptions  the long options definition
     * @param  string  $ambiguity    directive to handle option names ambiguity
     * @return array   the parsed options, their arguments and parameters
     * @access public
     * @static
     */
    public static function doGetopt($version = null, $args = array(),
        $shortOptions = '', $longOptions = array(), $ambiguity = '')
    {
        $getopt = new self;

        return $getopt->process($args, $shortOptions, $longOptions,
            $ambiguity, $version);
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
     * See the definition/example in the class Doc Block.
     *
     * Example: returning an index array
     * <code>
     * array(
     *      [0] => array("foo" => null, "bar" => "car", "c" => null),
     *      [1] => array([0] => "param1", [1] => "param2")
     * );
     * </code>
     *
     * @param  array  $args         the arguments
     * @param  string $shortOptions the short options definition, e.g. "ab:c::"
     *                              <ul>
     *                              <li>":" : the option requires an argument</li>
     *                              <li>"::" : the option accepts an optional argument</li>
     *                              <li>otherwise the option accepts no argument</li>
     *                              </ul>
     * @param  array  $longOptions  the long options definition,
     *                              e.g. array("art", "bar=", "car==)
     *                              <ul>
     *                              <li>"=" : the option requires an argument</li>
     *                              <li>"==" : the option accepts an optional argument</li>
     *                              <li>otherwise the option accepts no argument</li>
     *                              </ul>
     * @param  string $ambiguity    directive to handle option names ambiguity,
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
     * @return array  the parsed options, their arguments and parameters
     * @access public
     * @static
     */
    public static function getopt($args = array(), $shortOptions = '',
        $longOptions = array(), $ambiguity = '')
    {
        return self::doGetopt(1, $args, $shortOptions, $longOptions, $ambiguity);
    }

    /**
     * Parses the command line
     *
     * See getopt() for a complete description.
     *
     * @param  array  $args         the arguments
     * @param  string $shortOptions the short options definition, e.g. "ab:c::"
     * @param  array  $longOptions  the long options definition
     * @param  string $ambiguity    directive to handle option names ambiguity
     * @return array  the parsed options, their arguments and parameters
     * @access public
     * @static
     */
    public static function getopt2($args = array(), $shortOptions = '',
        $longOptions = array(), $ambiguity = '')
    {
        return self::doGetopt(2, $args, $shortOptions, $longOptions, $ambiguity);
    }

    /**
     * Checks if the argument is an option
     *
     * @param  string  $argument the argument, e.g. "-f" or "--foo"
     * @return boolean true if an option, false otherwise
     * @access public
     */
    public function isOption($argument)
    {
        return (bool)preg_match('~^(-\w|--\w+)$~', $argument);
    }

    /**
     * Parses a long option
     *
     * @param  string $argument the option and argument (excluding the "--" prefix),
     *                          e.g. "file=foo.php", "file foo.php", "bar"
     * @return void
     * @access public
     */
    public function parseLongOption($argument)
    {
        $option = explode('=', $argument, 2);
        $name = current($option);
        $arg = next($option) or $arg = null;
        // verifies the option is valid
        isset($this->ambigous[$name]) and self::exception('ambigous', $name);
        isset($this->shortcuts[$name]) and $name = $this->shortcuts[$name] or
        isset($this->longOptionsDef[$name]) or self::exception('unrecognized', $name);

        if ($this->longOptionsDef[$name] == 'mandatory') {
            // the option requires an argument, e.g. --file=foo.php
            // tries the next argument if necessary, e.g. --file foo.php
            is_null($arg) and list(, $arg) = each($this->args);
            is_null($arg) and self::exception('mandatory', $name);
            // verifies the argument is not an option itself
            $this->isOption($arg) and self::exception('mandatory', $name);
        } else if ($this->longOptionsDef[$name] == 'noarg' and !is_null($arg)) {
            // the option may not take an optional argument
            self::exception('noargument', $name);
        }
        // capture the option and its argument
        $this->options[] = array('--' . $name, $arg);
    }

    /**
     * Parses the long option names and types
     *
     * @param  array  $options the long options, e.g. array("foo", "bar=")
     * @return array  the options name and type,
     *                e.g. array("foo"=>"noarg", "bar"=>"mandatory")
     * @access public
     */
    public function parseLongOptionsDef($options)
    {
        // converts to an array if there is only one option
        settype($options, 'array');

        $longOptionsDef = array();
        foreach($options as $option) {
            if ($option = trim($option)) {
                // extracts the option name and type:
                // optional argument (==), mandatory (=), or none (null)
                // verifies the option syntax is correct
                preg_match("~^(\w+)(==|=)?$~", $option, $match) or
                self::exception('invalid', $option);
                $name = next($match);
                $type = next($match);
                // verifies the option is not a duplicate
                isset($longOptionsDef[$name]) and self::exception('duplicate', $name);
                // captures the option name and type
                $longOptionsDef[$name] = $this->type[$type];
            }
        }

        return $longOptionsDef;
    }

    /**
     * Parses a short option
     *
     * @param  string $argument the option and argument (excluding the "-" prefix),
     *                          e.g. "zfoo.php", "z foo.php", "z".
     * @return void
     * @access public
     */
    public function parseShortOption($argument)
    {
        for ($i = 0; $i < strlen($argument); $i++) {
            $name = $argument{$i};
            $arg = null;
            // verifies the option is valid
            isset($this->shortOptionsDef[$name]) or self::exception('unrecognized', $name);

            if ($this->shortOptionsDef[$name] == 'optional') {
                // the option may take an optional argument, e.g. -zfoo.php or -z
                if (($arg = substr($argument, $i + 1)) !== false) {
                    // the remainder of the string is the option argument
                    $this->options[] = array($name, $arg);
                    return;
                }
            } else if ($this->shortOptionsDef[$name] == 'mandatory') {
                // the option requires an argument, -zfoo.php or -z foo.php
                if (($arg = substr($argument, $i + 1)) === false) {
                    // nothing left to use as the option argument
                    // the next argument is expected to be the option argument
                    // verifies there is one and it is not an option itself
                    list(, $arg) = each($this->args);
                    (is_null($arg) or $this->isOption($arg)) and
                    self::exception('mandatory', $name);
                }
                $this->options[] = array($name, $arg);
                return;
            }
            // else: the option is not expecting an argument, e.g. -h
            // TODO: verify that if followed by a non option which is interpreted
            // as the end of options, there is indeed no option after until
            // possibly -- or -
            // capture the option and its argument
            $this->options[] = array($name, $arg);
        }
    }

    /**
     * Parses the short option names and types
     *
     * @param  string $options the short options, e.g. array("ab:c::)
     * @return array  the options name and type,
     *                e.g. array("a"=>"noarg", "b"=>"mandatory", "c"=>"optional")
     * @access public
     */
    public function parseShortOptionsDef($options)
    {
        // expecting a string for a the short options definition
        is_array($options) and self::exception('string');
        // trims and extracts the options name and type
        // optional argument (::), mandatory (:), or none (null)
        $options = trim($options);
        preg_match_all("~(\w)(::|:)?~", $options, $matches, PREG_SET_ORDER);

        $check = '';
        $shortOptionsDef = array();
        foreach($matches as $match) {
            $check .= current($match);
            $name = next($match);
            $type = next($match);
            // verifies the option is not a duplicate
            isset($shortOptionsDef[$name]) and self::exception('duplicate', $name);
            // captures the option name and type
            $shortOptionsDef[$name] = $this->type[$type];
        }
        // checks there is no syntax error the short options definition
        $check == $options or self::exception('syntax', $name);

        return $shortOptionsDef;
    }

    /**
     * Parses the command line
     *
     * See getopt() for a complete description.
     *
     * @param  array   $args         the arguments
     * @param  string  $shortOptions the short options definition, e.g. "ab:c::"
     * @param  array   $longOptions  the long options definition, e.g. array("foo", "bar=")
     * @param  string  $ambiguity    directive to handle option names ambiguity
     * @param  numeric $version      the getopt version: 1 or 2
     * @return array   the parsed options, their arguments and parameters
     * @access public
     */
    public function process($args = array(), $shortOptions, $longOptions,
        $ambiguity = '', $version = 2)
    {
        settype($args, 'array');
        in_array($ambiguity, array('loose', 'strict', 'shortcuts')) or
        $ambiguity = 'loose';

        if ($version < 2) {
            // preserve backwards compatibility with callers
            // that relied on erroneous POSIX fix
            // note: ported from Console/Getopt
            isset($args[0]) and substr($args[0], 0, 1) != '-' and array_shift($args);
            settype($args, 'array');
        }
        $this->args = $args;
        // parses the options definitions, create shorcuts or check ambiguities
        $this->shortOptionsDef = $this->parseShortOptionsDef($shortOptions);
        $this->longOptionsDef = $this->parseLongOptionsDef($longOptions);
        list($this->shortcuts, $this->ambigous) = $this->createShorcuts($this->longOptionsDef, $ambiguity);
        $this->verifyNoAmbiguity($this->longOptionsDef, $ambiguity);

        $this->options = array();
        $parameters = array();
        while (list($i, $arg) = each($this->args)) {
            if ($arg == '--') {
                // end of options
                // the remaining arguments are parameters excluding this one
                $parameters = array_slice($this->args, $i + 1);
                break;
            } else if ($arg == '-') {
                // the stdin flag
                // the remaining arguments are parameters including this one
                $parameters = array_slice($this->args, $i);
                break;
            } else if (substr($arg, 0, 2) == '--') {
                // a long option, e.g. --foo
                if ($this->longOptionsDef) {
                    $this->parseLongOption(substr($arg, 2));
                } else {
                    // not expecting long options, the remaining arguments are
                    // parameters including this one stripped off of --
                    $parameters = array_slice($this->args, $i);
                    $parameters[0] = substr($parameters[0], 2);
                    break;
                }
            } else if ($arg{0} == '-') {
                // a short option, e.g. -h
                $this->parseShortOption(substr($arg, 1));
            } else {
                // the first non option
                // the remaining arguments are parameters including this one
                $parameters = array_slice($this->args, $i);
                break;
            }
        }

        return array($this->options, $parameters);
    }

    /**
     * Reads the command arguments
     *
     * @return array  the arguments
     * @access public
     * @static
     */
    public static function readPHPArgv()
    {
        global $argv;

        is_array($args = $argv) or
        is_array($args = $_SERVER['argv']) or
        is_array($args = $GLOBALS['HTTP_SERVER_VARS']['argv']) or
        self::exception('noargs');

        return $args;
    }

    /**
     * Verifies there is no ambiguity with option names
     *
     * @param  array   $longOptionsDef the long options names and their types
     * @param  string  $ambiguity      directive to handle option names ambiguity,
     *                                 See getopt() for a complete description
     * @return boolean no ambiguity if true, false otherwise
     * @access public
     */
    public function verifyNoAmbiguity($longOptionsDef, $ambiguity)
    {
        settype($longOptionsDef, 'array');

        foreach($longOptionsDef as $name => $type) {
            foreach($longOptionsDef as $name2 => $type2) {
                if ($name != $name2) {
                    if ($ambiguity == 'loose' and $type == 'noarg') {
                        // according to Getopt.php, CVS v 1.4 2007/06/12,
                        // _parseLongOption(), line #236, the possible
                        // ambiguity of a long option name with another one is
                        // ignored if this option does not expect an argument!
                        continue;
                    }
                    // checks options are not ambigous, e.g. --foo --foobar
                    strpos($name2, $name) === false or self::exception('ambigous', $name);
                }
                // else: there is no ambiguity between an option and itself!
            }
        }

        return true;
    }
}

?>
