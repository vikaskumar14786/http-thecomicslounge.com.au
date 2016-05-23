<?php
/**
 * @package		JCalPro
 * @subpackage	com_jcalpro

**********************************************
JCal Pro
Copyright (c) 2006-2012 Anything-Digital.com
**********************************************
JCalPro is a native Joomla! calendar component for Joomla!

JCal Pro was once a fork of the existing Extcalendar component for Joomla!
(com_extcal_0_9_2_RC4.zip from mamboguru.com).
Extcal (http://sourceforge.net/projects/extcal) was renamed
and adapted to become a Mambo/Joomla! component by
Matthew Friedman, and further modified by David McKinnis
(mamboguru.com) to repair some security holes.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This header must not be removed. Additional contributions/changes
may be added to this header as long as no information is deleted.
**********************************************
Get the latest version of JCal Pro at:
http://anything-digital.com/
**********************************************

 */

defined('JPATH_PLATFORM') or die;

abstract class JCalProHelperFilter
{
	/**
	 * Truncates text.
	 * 
	 * @param string  $text String to truncate.
	 * @param integer $length Length of returned string, including ellipsis.
	 * @param string  $ending Ending to be appended to the trimmed string.
	 * @param boolean $exact If false, $text will not be cut mid-word
	 * @param boolean $considerHtml If true, HTML tags would be handled correctly
	 * @return string Trimmed string.
	 */
	public static function truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = true) {
		// if the length is 0 or less, just return the string
		if (0 >= $length) return $text;
		// addition by jeffchannell: if the string is long, has no spaces, and no html this method fails
		if (false === strpos($text, '<') && $considerHtml) $considerHtml = false;
		// handle html
		if ($considerHtml) {
			// if the plain text is shorter than the maximum length, return the whole text
			if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = strlen($ending);
			$open_tags = array();
			$truncate = '';
			foreach ($lines as $line_matchings) {
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1])) {
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
						// do nothing
					// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
						unset($open_tags[$pos]);
						}
					// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, strtolower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}
				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
				if ($total_length+$content_length> $length) {
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;
					// search for html entities
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1]+1-$entities_length <= $left) {
								$left--;
								$entities_length += strlen($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}
					// add break
					$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
					// maximum lenght is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}
				// if the maximum length is reached, get off the loop
				if($total_length>= $length) {
					break;
				}
			}
		} else {
			if (strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = substr($text, 0, $length - strlen($ending));
			}
		}
		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				$truncate = substr($truncate, 0, $spacepos);
			}
		}
		// add the defined ending to the text
		$truncate .= $ending;
		if($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}
		return $truncate;
	}
	
	/**
	 * wrapper for htmlspecialchars
	 * 
	 * @param  string $text
	 * @param  string $quotes
	 * @param  string $charset
	 * @return string escaped text
	 */
	public static function escape($text, $quotes = ENT_QUOTES, $charset = 'UTF-8') {
		return htmlspecialchars($text, $quotes, $charset);
	}
	
	/**
	 * escapes text for use in JavaScript
	 * 
	 * @param  string $text
	 * @return string escaped text
	 */
	public static function escape_js($text) {
		for($i = 0, $l = strlen($text), $new_str=''; $i < $l; $i++) {
			$new_str .= (ord(substr($text, $i, 1)) < 16 ? '\\x0' : '\\x') . dechex(ord(substr($text, $i, 1)));
		}
		return $new_str;
	}
	
	/**
	 * wrapper for core filterText
	 * 
	 * if we're in 2.5+, we can use the core component helper
	 * otherwise, we'll piggyback off the com_content helper (thus using the same rules)
	 * 
	 * @param  string $text
	 * @return string filtered text
	 */
	public static function filter($text) {
		jimport('joomla.application.component.helper');
		if (method_exists('JComponentHelper', 'filterText')) {
			$text = JComponentHelper::filterText($text);
		}
		else {
			if (!class_exists('ContentHelper')) {
				require_once JPATH_ADMINISTRATOR . '/components/com_content/helpers/content.php';
			}
			$text = ContentHelper::filterText($text);
		}
		// run through HTMLPurifier
		$text = self::purify($text);
		return $text;
	}
	
	/**
	 * static wrapper for HTMLPurifier::purify
	 * 
	 * @param string $text
	 */
	public static function purify($text) {
		static $purifier;
		if (!isset($purifier)) {
			// are we supposed to use purifier?
			if (!((int) JCalPro::config('purifier', 1))) return $text;
			// try to disable compat mode because it causes problems
			@ini_set('zend.ze1_compatibility_mode', 0);
			// import & use purifier
			jimport('htmlpurifier.purifier');
			$purifier = new HTMLPurifier();
		}
		// double check the compat mode
		if (1 == ini_get('zend.ze1_compatibility_mode')) return $text;
		// return purified text
		return $purifier->purify($text);
	}
	
	/**
	 * strips content plugins
	 * 
	 * @param string $text
	 * @return string
	 */
	public static function strip_plugins($text) {
		return preg_replace('/\{.*?\}/', '', $text);
	}
	
	/**
	 * change email cloaking so it doesn't break ajax mode
	 * 
	 * @param string $text
	 * @return string
	 */
	public static function recloak_emails($text)
	{
		// keep track of how many cloaks are run
		static $cloaks;
		if (is_null($cloaks))
		{
			$cloaks = 0;
		}
		$cloaks++;
		// parts to search for
		$docwrite = 'document.write';
		$script   = "<script type='text/javascript'>";
		$warning  = JText::_('JLIB_HTML_CLOAKING');
		// quick test to see if the cloaking has ran on this text
		if (!strpos($text, $docwrite) || !strpos($text, $warning))
		{
			return $text;
		}
		// split into smaller pieces
		$parts = explode($script, $text);
		// loop each part
		foreach ($parts as $i => $part)
		{
			// if this part contains 0 docwrites add the script tag back and keep going
			if (false === strpos($parts[$i], $docwrite))
			{
				if ($i && !empty($parts[$i]))
				{
					$parts[$i] = $script . $parts[$i];
				}
				continue;
			}
			// check for a few key strings - if all are found alter the cloaking
			if (strpos($parts[$i], 'var prefix') && strpos($parts[$i], 'var path') && strpos($parts[$i], 'var addy') && isset($parts[1+$i]) && strpos($parts[1+$i], $warning))
			{
				$parts[1+$i] = '';
				$parts[2+$i] = '';
			}
			// 
			// start the ends to add
			$start = '';
			$end   = '';
			// append an element here
			$start .= '<span id="uncloak' . $cloaks . '">' . $warning . '</span>';
			// add a new script tag to kill document.write
			$start .= $script . 'window.docwritestring = \'\';document.getElementById(\'uncloak' . $cloaks . '\').innerHTML = \'\';window.docwrite = document.write;document.write=function(){window.docwritestring += [].concat.apply([], arguments).join(\'\');};</script>';
			// add original script tag
			$start .= $script;
			// replace docwrite
			$end .= $script . 'document.getElementById(\'uncloak' . $cloaks . '\').innerHTML = window.docwritestring;window.docwritestring = \'\';document.write = window.docwrite;</script>';
			// put this part back together
			$parts[$i] = $start . $parts[$i] . $end;
			// increase counts
			$cloaks++;
		}
		// send back the text
		return implode('', $parts);
	}
}
