<?php

function getMentionHtml($type)
{
	return '<span class="mention" data-mention="'.$type.'">';
}

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

function replaceAllMentions($text, $mentions = [])
{
    collect($mentions)->each(function($mention, $type) use (&$text){
        $text = replaceMention($text, $type, $mention);
    });

    return $text;
}
