<?php
/**
 * Панель вебмастера.
 *
 * @author Constantine Anikin (ref) <mail@anykeystudio.ru>
 * @link https://github.com/ref
 * @copyright 2015 Constantine Anikin
 * @license see LICENCE.md
 */

require_once('vendor/whois/whois.main.php');
require_once('config.php');

/**
 * Функция подсчитывает ТИЦ, получая данные у Яндекса.
 * Обязательный входной параметр строкового типа $domain - адрес сайта для проверки.
 * В случае неудачи вернет строчку N/A.
 */
function name_get_tiq($domain) {
  $xml_data = file_get_contents('http://bar-navig.yandex.ru/u?url=http://' . $domain . '&show=1');
  $tiq = $xml_data ? (int) substr(strstr($xml_data, 'value="'), 7) : 'N/A';
  return $tiq;
}

/**
 * Куча функций, чтобы определить Google Pagerank
 */
function get_google_pagerank($url)
{
	$query = "http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=".CheckHash(HashURL($url)). "&features=Rank&q=info:".$url."&num=100&filter=0";
	$data  = file_get_contents($query);
	$pos   = strpos($data, "Rank_");
	if ($pos === false) {
	
	}
	else
	{
		$pagerank = substr($data, $pos + 9);
		return $pagerank;
	}
}

function StrToNum($Str, $Check, $Magic)
{
	$Int32Unit = 4294967296; // 2^32
	$length = strlen($Str);
	for ($i = 0; $i < $length; $i++)
	{
		$Check *= $Magic;
		if ($Check >= $Int32Unit)
		{
			$Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
			$Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
		}
		$Check += ord($Str{$i});
	}
	
	return $Check;
}

function HashURL($String)
{
	$Check1 = StrToNum($String, 0x1505, 0x21);
	$Check2 = StrToNum($String, 0, 0x1003F);
	$Check1 >>= 2;
	$Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
	$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
	$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);
	$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
	$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );

	return ($T1 | $T2);
}

function CheckHash($Hashnum)
{
	$CheckByte = 0;
	$Flag = 0;
	$HashStr = sprintf('%u', $Hashnum) ;
	$length = strlen($HashStr);
	for ($i = $length - 1; $i >= 0; $i --)
	{
		$Re = $HashStr{$i};
		if (1 === ($Flag % 2))
		{
			$Re += $Re;
			$Re = (int)($Re / 10) + ($Re % 10);
		}
		$CheckByte += $Re;
		$Flag ++;
	}
	$CheckByte %= 10;
	if (0 !== $CheckByte)
	{
		$CheckByte = 10 - $CheckByte;
		if (1 === ($Flag % 2) )
		{
			if (1 === ($CheckByte % 2)) {
				$CheckByte += 9;
			}
			$CheckByte >>= 1;
		}
	}
	
	return '7'.$CheckByte.$HashStr;
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Панель вебмастера by ref.</title>

	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>

	<link rel="stylesheet" href="style.css"/>

</head>
<body>

<div class="container">
	<h1>Панель вебмастера</h1>

	<table>
		<thead style="font-weight:bold;">
		<tr>
			<th style="width:20px;"></th>
			<th style="width:150px;">Домен</th>
			<th style="width:40px;">тИц</th>
			<th style="width:40px;">PR</th>
			<th style="width:100px;">Инд.Яндекс</th>
			<th style="width:120px;">Зарегистрирован</th>
			<th style="width:120px;">Истекает</th>
			<th style="width:60px;">До окон.</th>
			<th style="width:120px;">Регистратор</th>
			<th style="width:40px;">Whois</th>
		</tr>
		</thead>
		<tbody>

		<?php
		foreach ($domains as $domain) 
		{
			$whois           = new Whois();
			$result          = $whois->Lookup($domain);

			$created         = $result['regrinfo']['domain']['created'];
			$expires         = $result['regrinfo']['domain']['expires'];
			$registar        = $result['regrinfo']['domain']['sponsor'];

			$expires_time    = strtotime($expires);
			$next            = strtotime('+2 month');
			$expires_days    = round(($expires_time - time()) / 60 / 60 / 24);
			// если срок домена истекает, то подкрасим текст оставшихся дней красным.
			$expires_warning = $expires_time <= $next ? 'color:red; ' : '';

			// Яндекс тИЦ
			$tic = name_get_tiq($domain);
			// Google PageRank
			$pr = get_google_pagerank($domain);

			/**
			 * Получаем количество страниц в индексе Яндекса
			 */
			if (!empty($y_xml_url))
			{
				$y_xml_r = file_get_contents($y_xml_url . '&query=site%3A' . $domain . '&l10n=ru');
				if ($y_xml_r) 
				{
					$y_xml_d = simplexml_load_string($y_xml_r);
					$y_pages = $y_xml_d->response->found;
				}
			} else {
				$y_pages = 'x';
			}

			echo "
				<tr>
					<td><img src=\"https://favicon.yandex.net/favicon/{$domain}\" /></td>
					<td><a href=\"http://{$domain}\" target=\"_blank\">{$domain} <img src=\"img/with-arrow.gif\"></a></td>
					<td style=\"text-align:center;\"><a href=\"http://yaca.yandex.ru/yca/cy/ch/{$domain}/\" target=\"_blank\">{$tic}</a></td>
					<td style=\"text-align:center;\">{$pr}</td>
					<td style=\"text-align:center;\"><a href=\"http://yandex.ru/yandsearch?text=host%3A{$domain}*%20%7C%20host%3Awww.{$domain}*\" target=\"_blank\">{$y_pages}</a></td>
					<td style=\"text-align:center;\">{$created}</td>
					<td style=\"text-align:center;\">{$expires}</td>
					<td style=\"{$expires_warning}text-align:center;\">{$expires_days}</td>
					<td style=\"text-align:center;\">{$registar}</td>
					<td style=\"text-align:center;\"><a href=\"http://reg.ru/whois/?dname={$domain}\" target=\"_blank\">go <img src=\"img/with-arrow.gif\"></a></td>
				</tr>";
		}

		/*
		Можно расскоментировать и увидеть полную информацию по домену.
		$domain = 'yandex.ru';

		$whois = new Whois();
		$result = $whois->Lookup($domain);

		echo '<pre>';
		print_r($result);
		echo '</pre><br /><br />';*/
		?>

		</tbody>
	</table>
</div>

</body>
</html>