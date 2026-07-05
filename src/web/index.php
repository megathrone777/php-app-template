<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/bin/twig.php";

	$pathname = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

	switch ($pathname) {
		case "/":
			echo twig()->render("web/pages/home", array(
				"title" => "Home page",
			));
			break;

		case "/projects":
			echo twig()->render("web/pages/projects", array(
				"projects" => R::findAll("projects"),
				"title" => "Projects page",
			));
			break;

		case "/contacts":
			echo twig()->render("web/pages/contacts", array(
				"title" => "Contacts page",
			));
			break;

		default:
			if (preg_match('#^/project/(\d+)$#', $pathname, $matches)) {
				$project = R::load("projects", (int) $matches[1]);

				if ($project->id) {
					echo twig()->render("web/pages/projectDetails", array(
						"project" => $project,
						"title" => "Project - " . $project->title,
					));
					break;
				}
			}

			http_response_code(404);
			echo twig()->render("app/pages/notFound.twig", array(
				"home" => "/",
				"layout" => "web/blocks/layout.twig",
				"title" => "Page not found"
			));
			break;
	}
?>
