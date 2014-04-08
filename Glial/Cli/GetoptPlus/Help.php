<?php

/**
 * Console GetoptPlus/Help
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
 * @version   SVN: $Id: Help.php 47 2008-01-10 11:03:38Z mcorne $
 * @link      http://pear.php.net/package/Console_GetoptPlus
 */

/**
 * Generation of the command usage/help
 *
 * @category  Console
 * @package   Console_GetoptPlus
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2008 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release:@package_version@
 * @link      http://pear.php.net/package/Console_GetoptPlus
 */
class Console_GetoptPlus_Help
{
    /**
     * The option name padding within the option descrition
     */
    const optionPadding = 30;

    /**
     * The options section title
     */
    const options = 'Options:';

    /**
     * The parameter section title
     */
    const parameters = 'Parameters:';

    /**
     * The usage section title
     */
    const usage = 'Usage: ';

    /**
     * Aligns a set of lines
     *
     * Additional data is added to the first line.
     * The other lines are padded and aligned to the first one.
     *
     * @param  array   $lines         the set of lines
     * @param  string  $addon         the additional data to add to the first line
     * @param  integer $paddingLength the padding length
     * @return array   the aligned lines
     * @access public
     */
    public function alignLines($lines, $addon = '', $paddingLength = null)
    {
        settype($lines, 'array');
        settype($addon, 'string');
        // defaults the left alignment to the length of the additional data + 1
        is_null($paddingLength) and $paddingLength = $addon? (strlen($addon) + 1) : 0;
        // extracts the first line
        $firstLine = (string)current($lines);
        $firstLineEmpty = $firstLine == '';

        if (!$addon or $firstLineEmpty or $paddingLength > strlen($addon)) {
            // no addon or padding larger than addon
            // pads the additional data and adds it to the left of the first line
            $addon = str_pad($addon, $paddingLength);
            $firstLine = $addon . array_shift($lines);
        } else {
            // the information on the left is longer than the padding size
            $firstLine = $addon;
        }
        // left-pads the other lines
        $padding = str_repeat(' ', $paddingLength);
        $callback = create_function('$string', "return '$padding' . \$string;");
        $lines = array_map($callback, $lines);
        // prepends the first line
        $firstLine = rtrim($firstLine);
        array_unshift($lines, $firstLine);

        return $lines;
    }

    public static function get($config, $command)
    {
        $help = new self;
        return $help->set($config, $command);
    }

    /**
     * Creates the help/usage text
     *
     * @param  array  $config  the command configuration
     * @param  string $command the command name
     * @return string the help/usage text
     * @access public
     */
    public function set($config, $command)
    {
        // sets all the help/usage section texts
        $help = array();
        isset($config['header']) and
        $help[] = $this->tidyArray($config['header']);
        $help[] = $this->setUsage($config, $command);
        isset($config['options']) and $help[] = $this->setOptions($config['options']);
        isset($config['parameters']) and
        $help[] = $this->alignLines($config['parameters'], self::parameters) ;
        isset($config['footer']) and $help[] = $this->tidyArray($config['footer']);
        // merges the section texts together
        $callback = create_function('$array, $array1',
            '$array or $array = array(); return array_merge($array, $array1);');
        $help = array_reduce($help, $callback, array());

        return implode("\n", $help);
    }

    /**
     * Creates the options help text section
     *
     * @param  array  $optionsConfig the options descriptions
     * @return array  the options help text section
     * @access public
     */
    public function setOptions($optionsConfig)
    {
        settype($optionsConfig, 'array');

        $padding = str_repeat(' ', self::optionPadding);
        $callback = create_function('$string', "return '$padding' . \$string;");

        $lines = array();
        foreach($optionsConfig as $option) {
            $desc = isset($option['desc'])? $option['desc']: '';
            settype($desc, 'array');
            // extracts the option example value from the description
            // encloses with angle/square brackets if mandatory/optional
            $value = '';
            empty($option['type']) or
            $option['type'] == 'mandatory' and $value = '<' . array_shift($desc) . '>' or
            $option['type'] == 'optional' and $value = '[' . array_shift($desc) . ']';
            // sets the option names
            $optionNames = array();
            isset($option['short']) and $optionNames[] = "-{$option['short']}";
            isset($option['long']) and $optionNames[] = "--{$option['long']}";
            $value and $optionNames[] = $value;
            $optionNames = implode(' ', $optionNames);
            // adds the option names to the description
            $desc = $this->alignLines($desc, $optionNames, self::optionPadding);
            $lines = array_merge($lines, $desc);
        }
        // prefix the options with e.g. "Options:"
        $lines and array_unshift($lines, self::options);

        return $lines;
    }

    /**
     * Creates the usage help text section
     *
     * @param  array  $usages        the usage descriptions
     * @param  string $command       the command name
     * @param  array  $optionsConfig the options descriptions
     * @param  array  $paramsConfig  the parameters descriptions
     * @return array  the usage help text section
     * @access public
     */
    public function setUsage($config, $command)
    {
        if (empty($config['usage'])) {
            // usage is empty, defaults to a one line usage,
            // e.g. [options] [parameters]
            $usage = array();
            empty($config['options']) or $usage[] = '[options]';
            empty($config['parameters']) or $usage[] = '[parameters]';
            $config['usage'] = implode(' ', $usage);
        }
        // expecting an array of arrays of usage lines,
        // or possibly a single usage line
        settype($config['usage'], 'array');
        $lines = array();
        $padding = str_repeat(' ', strlen(self::usage));

        foreach($config['usage'] as $idx => $usage) {
            $usage = $this->tidyArray($usage);
            // adds the usage keywork to the first usage, e.g. "Usage:"
            $prefix = $idx? $padding : self::usage;
            // adds the command to each usage, e.g. command [options] [parameters]
            $prefix .= basename($command);
            $usage = $this->alignLines($usage, $prefix);
            $lines = array_merge($lines, $usage);
        }

        return $lines;
    }

    /**
     * Tidies an array
     *
     * Makes an array if passed as a string.
     * Optionally forces the values to strings if there are not.
     *
     * @param  array   $array      the array
     * @param  boolean $tidyString forces the values to string if true,
     *                             or leaves them untouched if false
     * @return array   the tidied array
     * @access public
     */
    public function tidyArray($array, $tidyString = true)
    {
        settype($array, 'array');
        // tidies the array string values
        $tidyString and $array = array_map(array($this, 'tidyString'), $array);

        return $array;
    }

    /**
     * Tidies a string
     *
     * Retains only the first value if passed as an array.
     *
     * @param  string $string the string
     * @return string the tidy string
     * @access public
     */
    public function tidyString($string)
    {
        // if an array: captures the first value and converts it to a string
        // silently ignores the other values
        is_array($string) and $string = current($string);

        return trim($string);
    }
}

?>