<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/config/twig.php";

	$pathname = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

	// Минимальный guard: без сессии показываем только страницу логина.
	if (empty($_SESSION["user_logged_in"])) {
		echo twig()->render("admin/pages/login.twig", array(
			"title" => "Login",
			"error" => $_SESSION["login_failure"] ?? null,
		));

		unset($_SESSION["login_failure"]);
		return;
	}

	switch ($pathname) {
		case "/admin":
			echo twig()->render("admin/pages/index.twig", array(
				"title" => "Dashboard",
			));
			break;

		default:
			http_response_code(404);
			echo twig()->render("admin/pages/notFound.twig", array(
				"title" => "Page not found",
			));
			break;
	}
?>
