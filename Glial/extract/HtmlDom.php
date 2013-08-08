<?php

namespace glial\extract;


/**
 * Web Grabber HTML DOM Parser class definition<br/>
 * This static class is a simple HTML DOM parser providing various methods for handling html tags and attributes.<br/>
 * @author WiseLoop & Aurélien LEQUOY
 */

class htmlDom {
    /**
     * Cleans a tag by removing accidental leading and trailing unwanted strings.
     * @param string $tagSlice
     * @return string
     */
    public static function cleanTag($tagSlice) {
        $tagSlice = trim($tagSlice);
        $s0 = substr($tagSlice, 0, 1);
        if("\n" == $s0 || "\r" == $s0)
            $tagSlice = substr($tagSlice, 1, strlen($tagSlice - 1));
        $s0 = substr($tagSlice, -1);
        if("\n" == $s0 || "\r" == $s0)
            $tagSlice = substr($tagSlice, 0, strlen($tagSlice - 1));
        return $tagSlice;
    }

    /**
     * Autocompletes a tag slice based on the contents given in $html and returns a full tag string enclosed by &lt; and &gt; that is contained by $html.
     * @param string $html the HTML content to be searched
     * @param string $tagSlice a part of a tag contained in $html to be completed
     * @return bool|string the full tag or false if $tagSlice is not found
     */
    public static function completeTag($html, $tagSlice) {
        $k1 = stripos($html, $tagSlice);
        if ($k1 === false) {
            return false;
        }

        $i1 = null;
        if (substr($tagSlice, 0, 1) !== '<') {
            for ($i1 = $k1; $i1 > 0; $i1--) {
                if (substr($html, $i1, 1) === '<') {
                    break;
                }
            }
        }

        $i2 = null;
        if (substr($tagSlice, -1) !== '>') {
            for ($i2 = $k1 + strlen($tagSlice); $i2 < strlen($html); $i2++) {
                if (substr($html, $i2, 1) === '>') {
                    break;
                }
            }
        }

        if (isset($i2)) {
            if (substr($html, $i2, 1) === '>') {
                $tagSlice = $tagSlice . substr($html, $k1 + strlen($tagSlice), 1 + $i2 - ($k1 + strlen($tagSlice)));
            }
        }
        if (isset($i1)) {
            if (substr($html, $i1, 1) === '<') {
                $tagSlice = substr($html, $i1, $k1 - $i1) . $tagSlice;
            }
        }

        $i3 = stripos($tagSlice, '>');
        if($i3 !== strlen($tagSlice) - 1) {
            $tagSlice = substr($tagSlice, $i3 + 1);
        }

        return $tagSlice;
    }

    /**
     * Extracts from the HTML content provided by $html the first tag content specified by $tagSlice.
     * @param string $html the HTML content string
     * @param string $tagSlice the tag to be searched and extracted (can be an incomplete tag also)
     * @param bool $strip specify if the result should be stripped by the searched tag, in other words if only the inner HTML content shoud be returned
     * @return string|bool the extracted tag contents or false if $tagSlice is not found
     */
    public static function getTagContent($html, $tagSlice, $strip = false) {
        $fullTag = self::completeTag($html, $tagSlice);
        $html = self::normalizeHtml($html, $fullTag);

        $k1 = stripos($html, $fullTag);
        if ($k1 === false) {
            return false;
        }

        $html = substr($html, $k1, strlen($html) - $k1);
        $openingTag = self::computeOpeningTag($fullTag);
        if (substr($openingTag, -1) !== '>') {
            $openingTag .= ' ';
        }
        $closingTag = self::computeClosingTag($fullTag);

        $cLen = strlen($html);
        $openingTagLen = strlen($openingTag);
        $closingTagLen = strlen($closingTag);

        $openingCount = 0;
        $closingCount = 0;

        for ($i = 0; $i < $cLen; $i++) {
            $strOpening = substr($html, $i, $openingTagLen);
            $strClosing = substr($html, $i, $closingTagLen);

            if ($strOpening == $openingTag) {
                $openingCount++;
            }
            if ($strClosing == $closingTag) {
                $closingCount++;
            }

            if ($openingCount == $closingCount) {
                break;
            }
        }
        $html = substr($html, 0, $i + $closingTagLen);
        if($strip) {
            $fullTagLen = strlen($fullTag);
            $html = substr($html, $fullTagLen, strlen($html) - $fullTagLen - $closingTagLen);
        }
        return $html;
    }

    /**
     * Fills an array with all tag contents specified by $tagSlice extracted from the HTML content provided in $html.
     * @param string $html the HTML content string
     * @param string $tagSlice the tag to be searched and extracted (can be an incomplete tag also)
     * @param bool $strip specify if the result should be stripped by the searched tag, in other words if only the inner HTMLs shoud be returned
     * @return array the extracted tag contents
     */
    public static function getTagContents($html, $tagSlice, $strip = false) {
        $fullTag = self::completeTag($html, $tagSlice);
        $html = self::normalizeHtml($html, $fullTag);

        $ret = array();
        $content = self::getTagContent($html, $tagSlice, $strip);
        while ($content !== false) {
            $ret[] = $content;
            $contentToReplace = $content;
            if($strip) {
                $contentToReplace = self::getTagContent($html, $tagSlice, false);
            }
			$start = strpos ($html, $contentToReplace);
			$html = substr($html,0,$start).substr($html,$start+ strlen($contentToReplace));
			
            $content = self::getTagContent($html, $tagSlice, $strip);
        }
        return $ret;
    }

    /**
     * Normalizes all the tag strings against $fullTag from a HTML content string.
     * @param string $html the HTML content string
     * @param string $fullTag the tag to be normalized
     * @return string the normalized HTML content
     */
    public static function normalizeHtml($html, $fullTag) {
        $openingTag = self::computeOpeningTag($fullTag);
        $html = str_replace(str_replace(array('<', '< ', '<  '), '<', $openingTag), $openingTag, $html);
        $html = str_replace($openingTag . '>', $openingTag . ' >', $html);
        return $html;
    }

    /**
     * Computes the corresponding closing tag for the open tag specified by $fullTag.
     * @param string $fullTag the open tag
     * @return string the closing tag
     */
    public static function computeClosingTag($fullTag) {
        $tagName = self::getTagName($fullTag);
        if(in_array($tagName, array('img', 'br'))) {
            return '>';
        }
        if(substr($fullTag, -2) == '/>') {
            return '/>';
        }
        $tag = self::computeOpeningTag($fullTag);
        if (substr($tag, -1) !== '>') {
            $tag .= '>';
        }
        $tag = str_replace('<', '</', $tag);

        return $tag;
    }

    /**
     * Computes the corresponding opening tag for the closed tag specified by $fullTag.
     * @param string $fullTag the closed tag
     * @return string the opening tag
     */
    public static function computeOpeningTag($fullTag) {
        $k = stripos($fullTag, ' ');
        if ($k === false) {
            return $fullTag;
        }
        return substr($fullTag, 0, $k);
    }

    /**
     * Tests if the given tag is a self closed tag.
     * @param string $tag tha tag
     * @return bool if the tag is closed
     */
    public static function isClosedTag($tag) {
        $tag = trim($tag);
        if (substr($tag, -2) === '/>') {
            return true;
        }
        return false;
    }

    /**
     * Returns an attribute value of a tag.
     * @param string $fullTag the full tag definition
     * @param string $attributeName the requested attribute
     * @return bool|string the requested attribute string value if attribute exists for the given tag, or false if the attribute does not exists for the given tag
     */
    public static function getTagAttributeValue($fullTag, $attributeName) {
        $fullTag = str_replace('=""', '=" "', $fullTag);
        $fullTag = str_replace('\"', '"', $fullTag);
        $fullTag = str_replace(array("\r", "\n"), '', $fullTag);
        $re = '/' . preg_quote($attributeName) . '=(["\'])(.*?)\1/';
        if (preg_match($re, $fullTag, $match))
            return trim(($match[2]));
        return false;
    }

    /**
     * Returns the name of a tag.
     * @param string $fullTag the full tag definition
     * @return string the tag name
     */
    public static function getTagName($fullTag) {
        return str_replace('<', '', self::computeOpeningTag($fullTag));
    }

    /**
     * Extracts the value of a given css style attribute from a complete css styles string.
     * @param string $style the css styles string
     * @param string $styleAttributeName the attribute to be extracted
     * @return string the attribute value
     */
    public static function getStyleAttributeValue($style, $styleAttributeName) {
        $style = trim($style).';';
        $k1 = stripos($style, $styleAttributeName);
        if ($k1 === false) {
            return '';
        }
        $len = strlen($styleAttributeName);
        $k2 = stripos($style, ';', $k1 + $len);
        if ($k2 === false) {
            return '';
        }
        $ret = trim(substr($style, $k1 + $len, $k2 - $k1 - $len));
        if(substr($ret, 0, 1) === ':') {
            $ret = trim(substr($ret, 1, strlen($ret) - 1));
        }

        return $ret;
    }
}
