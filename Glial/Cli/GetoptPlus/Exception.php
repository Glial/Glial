<?php

/**
 * Console GetoptPlus/Exception
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
 * @version   SVN: $Id: Exception.php 47 2008-01-10 11:03:38Z mcorne $
 * @link      http://pear.php.net/package/Console_GetoptPlus
 */

require_once 'PEAR/Exception.php';

/**
 * Handling of error messages and exceptions.
 *
 * @category  Console
 * @package   Console_GetoptPlus
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2008 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Console_GetoptPlus
 * @see       PEAR_Exception
 */
class Console_GetoptPlus_Exception extends PEAR_Exception
{
    /**
     * The error messages
     *
     * @var    array
     * @access private
     */
    private $messages = array(// /
        'unknow' => array(1, 'Console_Getopt: unknown error ID (%s)'),
        // original Console_Getopt error messages
        'ambigous' => array(10, 'Console_Getopt: option --%s is ambiguous'),
        'mandatory' => array(11, 'Console_Getopt: option requires an argument --%s'),
        'noargument' => array(12, 'Console_Getopt: option --%s doesn\'t allow an argument'),
        'noargs' => array(13, 'Console_Getopt: Could not read cmd args (register_argc_argv=Off?)'),
        'unrecognized' => array(14, 'Console_Getopt: unrecognized option --%s'),
        // additional Console_GetoptPlus_Getopt error messages
        'duplicate' => array(20, 'Console_Getopt: duplicate option name definition --%s'),
        'invalid' => array(21, 'Console_Getopt: invalid long option definition %s'),
        'string' => array(22, 'Console_Getopt: short options definition must be a string'),
        'syntax' => array(23, 'Console_Getopt: short options definition syntax error %s'),
        // additional Console_GetoptPlus error messages
        'missing' => array(30, 'Console_GetoptPlus: unknown option name #%s'),
        'type' => array(31, 'Console_GetoptPlus: unknown option type %s'),
        'convert' => array(32, 'Console_GetoptPlus: wrong option name conversion %s'),
        );

    /**
     * Triggers the exception.
     *
     * @param  mixed  $exception the exception ID and optional message part,
     *                           e.g. "string" or array("invalid", '--foo')
     * @return void
     * @access public
     */
    public function __construct($exception)
    {
        // extracts the exception ID and message parameters
        settype($exception, 'array');
        $id = current($exception);
        // resets the exception ID if no corresponding message (programmatic error!)
        isset($this->messages[$id]) or $exception = array(null, $id) and $id = 'unknow';
        // extracts the exception code and pattern
        list($code, $format) = $this->messages[$id];
        $exception[0] = $format;
        // completes the message, throws the exception
        $message = call_user_func_array('sprintf', $exception);
        parent::__construct($message, $code);
    }
}

?>
