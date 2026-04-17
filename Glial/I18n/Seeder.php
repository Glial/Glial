<?php
namespace Glial\I18n;

use Glial\Sgbd\Sgbd;

/**
 * Seeder — bulk-register manual translations into the Glial i18n tables.
 *
 * Unlike Glial\I18n\I18n::translate() which relies on Google, Seeder lets
 * a site pre-register curated FR→(EN|PL|RU|...) pairs, one or many at a
 * time. Idempotent: safe to call on every request thanks to INSERT IGNORE.
 *
 * The cache .ini produced by I18n::loadCashFile is invalidated only when
 * an INSERT actually affected a row, so stable pages hit the filesystem
 * cache instead of the database.
 */
class Seeder
{
    /**
     * Register one French source string and its translations.
     *
     * @param string       $source  French source text
     * @param string|array $targets Either an EN string (legacy) or
     *                              ['en'=>'...', 'ru'=>'...', 'pl'=>'...']
     * @param string       $file    Caller __FILE__ (used for cache key)
     * @param int          $line    Caller line
     */
    public static function register(string $source, $targets, string $file = '', int $line = 0): void
    {
        if (!defined('DB_DEFAULT') || DB_DEFAULT === '__no_database__') {
            return;
        }

        if (is_string($targets)) {
            $targets = ['en' => $targets];
        }

        try {
            $db  = Sgbd::sql(DB_DEFAULT);
            $key = sha1('fr-' . trim($source));

            $changedLangs = [];

            $sourceEsc = $db->sql_real_escape_string(trim($source));
            $fileEsc   = $db->sql_real_escape_string($file);
            $db->sql_query(
                "INSERT IGNORE INTO `translation_glial`
                 (`key`, `text`, `language`, `file_found`, `line_found`)
                 VALUES ('{$key}', '{$sourceEsc}', 'fr', '{$fileEsc}', {$line})"
            );
            if (mysqli_affected_rows($db->link) > 0) {
                $changedLangs = array_keys($targets);
            }

            foreach ($targets as $lang => $text) {
                $textEsc = $db->sql_real_escape_string(trim($text));
                $langEsc = $db->sql_real_escape_string($lang);
                $db->sql_query(
                    "INSERT IGNORE INTO `translation_google`
                     (`key`, `source_language`, `target_language`, `target_text`)
                     VALUES ('{$key}', 'fr', '{$langEsc}', '{$textEsc}')"
                );
                if (mysqli_affected_rows($db->link) > 0 && !in_array($lang, $changedLangs, true)) {
                    $changedLangs[] = $lang;
                }
            }

            if ($file !== '' && !empty($changedLangs) && defined('TMP') && defined('DS')) {
                foreach ($changedLangs as $lang) {
                    $cacheFile = TMP . 'translations' . DS . $lang . '.' . md5($file) . '.ini';
                    if (file_exists($cacheFile)) {
                        @unlink($cacheFile);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silent fail — translation is non-critical
        }
    }

    /**
     * Bulk-register pairs.
     *
     * Accepts two formats:
     *   Legacy:  ['fr text' => 'en text', ...]
     *   Multi:   ['fr text' => ['en' => '...', 'ru' => '...'], ...]
     *
     * @param array  $pairs
     * @param string $file  __FILE__ of the caller
     */
    public static function registerMany(array $pairs, string $file = ''): void
    {
        foreach ($pairs as $source => $targets) {
            self::register($source, $targets, $file);
        }
    }
}
