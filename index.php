<?php
	if (php_sapi_name() === "cli-server") {
		$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

		if ($path !== "/" && is_file(__DIR__ . $path)) {
			return false;
		}
	}

	require_once __DIR__ . "/bin/config.php";
	require_once __DIR__ . "/bin/twig.php";

	$pathname = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

	if (str_starts_with($pathname, "/admin")) {
		session_start();
		require __DIR__ . "/src/admin/index.php";
	} else {
		require __DIR__ . "/src/web/index.php";
	}
?>
