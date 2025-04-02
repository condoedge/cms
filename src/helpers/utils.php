<?php

if (!function_exists('getMentionHtml')) {
	function getMentionHtmlCms($type)
	{
		return '<span class="mention" data-mention="'.$type.'">';
	}
}

if (!function_exists('replaceMentionCms')) {
	function replaceMentionCms($subject, $type, $replaceWith)
	{
        $pattern = '/<span\s+class="mention"\s+data-mention="' . preg_quote($type, '/') . '"[^>]*>.*?<\/span>/';

        return preg_replace($pattern, $replaceWith, $subject);
	}
}

if (!function_exists('replaceAllMentionsCms')) {
	function replaceAllMentionsCms($text, $mentions = [])
	{
		collect($mentions)->each(function($mention, $type) use (&$text){
			$text = replaceMentionCms($text, $type, $mention);
		});

		return $text;
	}
}