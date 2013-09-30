<?php

namespace Glial\Utility;

class Inflector
{

    /**
     * Returns the given camelCasedWord as an underscored_word.
     *
     * @param string $camelCasedWord Camel-cased word to be "underscorized"
     * @return string Underscore-syntaxed version of the $camelCasedWord
     * @access public
     * @static
     * @link 
     */
    static function underscore($camelCasedWord)
    {
        $result = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
        return $result;
    }

    /**
     * Returns Cake model class name ("Person" for the database table "people".) for given database table.
     *
     * @param string $tableName Name of database table to get class name for
     * @return string Class name
     * @access public
     * @static
     */
    static function camelize($lowerCaseAndUnderscoredWord)
    {
        $result = str_replace(' ', '', self::humanize($lowerCaseAndUnderscoredWord));
        return $result;
    }

    /**
     * Returns the given underscored_word_group as a Human Readable Word Group.
     * (Underscores are replaced by spaces and capitalized following words.)
     *
     * @param string $lower_case_and_underscored_word String to be made more readable
     * @return string Human-readable string
     * @access public
     * @static
     */
    static function humanize($lowerCaseAndUnderscoredWord)
    {
        $result = ucwords(str_replace('_', ' ', $lowerCaseAndUnderscoredWord));
        return $result;
    }

}