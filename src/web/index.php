<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/config/twig.php";

	$pathname = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

	switch ($pathname) {
		case "/":
			echo twig()->render("web/pages/home.twig", array(
				"title" => "Home",
			));
			break;

		default:
			http_response_code(404);
			echo twig()->render("web/pages/notFound.twig", array(
				"title" => "Page not found",
			));
			break;
	}
?>
