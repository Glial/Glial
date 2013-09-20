<?php

namespace Glial\Export;

class Csv
{
    public static function export_csv($sql_query, $what = '', $csv_terminated = "\n", $csv_separator = ";", $csv_enclosed = "\"", $csv_escaped = "\\")
    {

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $time_start = time();

        // Gets the data from the database
        $result = mysql_query($sql_query);
        //$result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED); => to delete

        $fields_cnt = mysql_num_fields($result);

        // If required, get fields name at the first line
        if ( isset($GLOBALS['csv_columns']) ) {
            $schema_insert = '';

            for ($i = 0; $i < $fields_cnt; $i++) {
                if ($csv_enclosed == '') {
                    $schema_insert .= stripslashes(mysql_field_name($result, $i));
                } else {
                    $schema_insert .= $csv_enclosed
                        . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, stripslashes($_SQL->sql_field_name($result, $i)))
                        . $csv_enclosed;
                }
                $schema_insert .= $csv_separator;
            } // end for
            $schema_insert = trim(substr($schema_insert, 0, -1));

            if ( !export::export_output_handler($schema_insert . $csv_terminated) ) {
                return false;
            }
        } // end if
        // Format the data
        while ( $row = mysql_fetch_row($result) ) {
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if ( !isset($row[$j]) || is_null($row[$j]) ) {
                    $schema_insert .= $GLOBALS[$what . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    // always enclose fields
                    if ($what == 'excel') {
                        $row[$j] = preg_replace("/\015(\012)?/", "\012", $row[$j]);
                    }
                    // remove CRLF characters within field
                    if ( isset($GLOBALS[$what . '_removeCRLF']) && $GLOBALS[$what . '_removeCRLF'] ) {
                        $row[$j] = str_replace("\n", "", str_replace("\r", "", $row[$j]));
                    }
                    if ($csv_enclosed == '') {
                        $schema_insert .= $row[$j];
                    } else {
                        // also double the escape string if found in the data
                        if ($csv_escaped != $csv_enclosed) {
                            $schema_insert .= $csv_enclosed
                                . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, str_replace($csv_escaped, $csv_escaped . $csv_escaped, $row[$j]))
                                . $csv_enclosed;
                        } else {
                            // avoid a problem when escape string equals enclose
                            $schema_insert .= $csv_enclosed
                                . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $row[$j])
                                . $csv_enclosed;
                        }
                    }
                } else {
                    $schema_insert .= '';
                }
                if ($j < $fields_cnt - 1) {
                    $schema_insert .= $csv_separator;
                }
            } // end for

            if ( !export::export_output_handler($schema_insert . $csv_terminated) ) {
                return false;
            }
        }//end while

        mysql_free_result($result);

        return true;
    }

    public static function ms_export_csv($sql_query, $what = '', $csv_terminated = "\n", $csv_separator = ";", $csv_enclosed = "\"", $csv_escaped = "\\", $csv_columns = true, $removeCRLF = true)
    {

        // Gets the data from the database
        $result = mssql_query($sql_query);
        //$result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED); => to delete

        $fields_cnt = mssql_num_fields($result);

        // If required, get fields name at the first line
        if ( isset($csv_columns) ) {
            $schema_insert = '';

            $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='ARKA_PIV_BIZFILE_FULL' order by ORDINAL_POSITION";

            $res = mssql_query($sql);//to prevent only returns 1st 30 characters of fieldname.

            while ( $ob = mssql_fetch_object($res) ) {
                if ($csv_enclosed == '') {

                    $schema_insert .= stripslashes($ob->COLUMN_NAME);
                } else {
                    $schema_insert .= $csv_enclosed
                        . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, stripslashes($ob->COLUMN_NAME))
                        . $csv_enclosed;
                }

                $schema_insert .= $csv_separator;
            }

            $schema_insert = trim(substr($schema_insert, 0, -strlen($csv_separator)));

            if ( !export::export_output_handler($schema_insert . $csv_terminated) ) {
                return false;
            }
        } // end if
        // Format the data
        while ( $row = mssql_fetch_row($result) ) {

            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if ($j == 12) {
                    $row[$j] = strtolower($row[$j]);
                }

                if ( !isset($row[$j]) || is_null($row[$j]) ) {
                    //$schema_insert .= $GLOBALS[$what . '_null'];
                    $schema_insert .= '';
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    // always enclose fields
                    if ($what == 'excel') {
                        $row[$j] = preg_replace("/\015(\012)?/", "\012", $row[$j]);
                    }
                    // remove CRLF characters within field
                    if ( isset($removeCRLF) && $removeCRLF ) {
                        $row[$j] = str_replace("\n", "", str_replace("\r", "", $row[$j]));
                    }
                    if ($csv_enclosed == '') {
                        $schema_insert .= $row[$j];
                    } else {
                        // also double the escape string if found in the data
                        if ($csv_escaped != $csv_enclosed) {
                            $schema_insert .= $csv_enclosed
                                . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, str_replace($csv_escaped, $csv_escaped . $csv_escaped, $row[$j]))
                                . $csv_enclosed;
                        } else {
                            // avoid a problem when escape string equals enclose
                            $schema_insert .= $csv_enclosed
                                . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $row[$j])
                                . $csv_enclosed;
                        }
                    }
                } else {
                    $schema_insert .= '';
                }
                if ($j < $fields_cnt - 1) {
                    $schema_insert .= $csv_separator;
                }
            } // end for

            if ( !export::export_output_handler($schema_insert . $csv_terminated) ) {
                return false;
            }
        }//end while

        mssql_free_result($result);

        return true;
    }

}

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
