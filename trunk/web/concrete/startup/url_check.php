<?

defined('C5_EXECUTE') or die(_("Access Denied."));

if (REDIRECT_TO_BASE_URL == true) {
	$protocol = 'http://';
	$base_url = BASE_URL;
	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS']) && ($base_url_ssl = Config::get('BASE_URL_SSL'))) {
		$protocol = 'https://';
		$base_url = $base_url_ssl;
	}

	$uri = $_SERVER['REQUEST_URI'];
	if (strpos($uri, '%7E') !== false) {
		$uri = str_replace('%7E', '~', $uri);
	}

	if (($base_url != $protocol . $_SERVER['HTTP_HOST']) && ($base_url . ':' . $_SERVER['SERVER_PORT'] != 'https://' . $_SERVER['HTTP_HOST'])) {
		header('HTTP/1.1 301 Moved Permanently');  
		header('Location: ' . $base_url . $uri);
		exit;
	}

}

