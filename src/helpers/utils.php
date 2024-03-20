<?php

if (!function_exists('getMentionHtml')) {
	function getMentionHtml($type)
	{
		return '<span class="mention" data-mention="'.$type.'">';
	}
}

if (!function_exists('replaceMention')) {
	function replaceMention($subject, $type, $replaceWith)
	{
		$start = strpos($subject, getMentionHtml($type));

		while ($start > -1) {

			$end = strpos($subject, '</span>', $start + strlen(getMentionHtml($type)));

			$subject = substr_replace($subject, $replaceWith, $start, $end - $start);

			$start = strpos($subject, getMentionHtml($type));

		}

		return $subject;
	}
}

if (!function_exists('replaceAllMentions')) {
	function replaceAllMentions($text, $mentions = [])
	{
		collect($mentions)->each(function($mention, $type) use (&$text){
			$text = replaceMention($text, $type, $mention);
		});

		return $text;
	}
}

if (!function_exists('_Sax')) {
	function _Sax($path, $dimension = 24)
	{
		return _Html(_SaxSvg($path, $dimension));
	}
}

if (!function_exists('_SaxSvg')) {
	function _SaxSvg($path, $dimension = 24)
	{
		$svgHtml = file_get_contents(public_path('icons/' . $path . '.svg'));

		$svgHtml = str_replace('"#292D32"', '"currentColor"', $svgHtml);
		$svgHtml = str_replace('height="24"', 'height="' . $dimension . '"', $svgHtml);
		$svgHtml = str_replace('width="24"', 'width="' . $dimension . '"', $svgHtml);

		return $svgHtml;
	}
}