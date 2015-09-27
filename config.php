<?php
/**
 * Webmaster panel, config file.
 *
 * @author Constantine Anikin (ref) <mail@anykeystudio.ru>
 * @link https://github.com/ref
 * @copyright 2015 Constantine Anikin
 * @license see LICENCE.md
 */

// список доменов, простой массив.
$domains = array(
	'yandex.ru',
	'kremlin.ru',
	'gov.ru',
	'avito.ru',
	'ozon.ru',
	'google.ru',
	'mail.ru',
	'9seo.ru',
	'maulnet.ru',
);
asort($domains);

/*
 * URL для запроса xml.yandex.ru
 * Необходимо зайти на xml.yandex.ru и указать ip, с которого будут поступать запросы.
 */
$y_xml_url = '';