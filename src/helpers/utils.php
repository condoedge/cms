<?php

// if (!function_exists('getMentionHtml')) {
// 	function getMentionHtml($type)
// 	{
// 		return '<span class="mention" data-mention="'.$type.'">';
// 	}
// }

// if (!function_exists('replaceMention')) {
// 	function replaceMention($subject, $type, $replaceWith)
// 	{
// 		$start = strpos($subject, getMentionHtml($type));

// 		while ($start > -1) {

// 			$end = strpos($subject, '</span>', $start + strlen(getMentionHtml($type)));

// 			$subject = substr_replace($subject, $replaceWith, $start, $end - $start);

// 			$start = strpos($subject, getMentionHtml($type));

// 		}

// 		return $subject;
// 	}
// }

// if (!function_exists('replaceAllMentions')) {
// 	function replaceAllMentions($text, $mentions = [])
// 	{
// 		collect($mentions)->each(function($mention, $type) use (&$text){
// 			$text = replaceMention($text, $type, $mention);
// 		});

// 		return $text;
// 	}
// }
