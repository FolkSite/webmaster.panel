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
 * Работа с сервисом xtool.ru
 *
 * @see http://xtool.ru/api.php
 */
if ($ch = curl_init("http://xtool.ru/login.php")) 
{
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "login={$x_login}&pass={$x_password}&auto=0");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
	curl_setopt ($ch, CURLOPT_COOKIEJAR, "cookie.txt");
	$out = curl_exec($ch);
	curl_close($ch);
}

if (!$out=="yes") die("Ошибка авторизации в сервисе xtool.ru");

function http_request($url)
{
	$xt_script = "http://xtool.ru/trast.php?h=1&url=";
	$ch = curl_init($xt_script.$url);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1000);
	curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 10);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
	curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
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

			if ($line = http_request($domain))
			{
				if ($obj = json_decode($line))
				{
					$tic     = $obj->tic;
					$pr      = $obj->pr;
					$y_pages = $obj->iny;
				}
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