<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/bin/twig.php";

	$pathname = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

	switch ($pathname) {
		case "/admin":
			if (empty($_SESSION["user_logged_in"])) {
				echo twig()->render("admin/pages/login.twig", [
					"error" => $_SESSION["login_failure"] ?? null,
					"title" => "Login page"
				]);
				unset($_SESSION["login_failure"]);
			} else {
				echo twig()->render("admin/pages/index.twig", [
					"project" => R::load("projects", 2),
					"title" => "Admin page"
				]);
			}
			break;
		default:
			if (empty($_SESSION["user_logged_in"])) {
				header("Location: /admin");
				exit;
			}

			http_response_code(404);
			echo twig()->render("app/pages/notFound.twig", array(
				"home" => "/admin",
				"layout" => "admin/blocks/layout/auth.twig",
				"title" => "Page not found"
			));
			break;
	}
?>
