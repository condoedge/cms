<?php

if (!function_exists('getMentionHtmlCms')) {
	function getMentionHtmlCms($type)
	{
		return '<span class="mention" data-mention="'.$type.'">';
	}
}

if (!function_exists('replaceMentionCms')) {
	function replaceMentionCms($subject, $type, $replaceWith)
	{
		$mentionHtml = '<span class="mention" data-mention="' . $type . '"';
        $start = strpos($subject, $mentionHtml);

        while ($start !== false) {
            // Encuentra el final del span
            $end = strpos($subject, '</span>', $start);
            if ($end === false) {
                break; // Si no se encuentra el cierre, salimos del bucle
            }

            // Calcula la longitud del contenido a reemplazar
            $length = $end + strlen('</span>') - $start;

            // Reemplaza el contenido
            $subject = substr_replace($subject, $replaceWith, $start, $length);

            // Busca la siguiente ocurrencia
            $start = strpos($subject, $mentionHtml, $start + strlen($replaceWith));
        }

        return $subject;
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