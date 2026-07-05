<?php
	$root = $_SERVER["DOCUMENT_ROOT"];

	require_once $root . "/bin/config.php";
	require_once $root . "/bin/twig.php";

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	$email = $_ENV["MAIL_TO"];
	$emailHtml = "";
	$fields = array("title" => $_POST["title"]);
	$mailer = new PHPMailer(true);

	if ($_POST["template"] == "contacts") {
		$senderEmail = trim($_POST["email"] ?? "");
		$name = trim($_POST["name"] ?? "");

		if ($senderEmail === "" || $name === "") {
			http_response_code(422);
			echo json_encode(array(
				"success" => false,
				"error" => "Validation failed"
			));
			exit;
		}

		$fields["email"] = $senderEmail;
		$fields["name"] = $name;

		$emailHtml = twig()->render("web/blocks/emailTemplates/contacts.twig", $fields);
	}

	$mailer->isSMTP();
	$mailer->Host = $_ENV["MAIL_HOST"];
	$mailer->Port = (int) $_ENV["MAIL_PORT"];

	if (!empty($_ENV["MAIL_USERNAME"])) {
		$mailer->SMTPAuth = true;
		$mailer->Username = $_ENV["MAIL_USERNAME"];
		$mailer->Password = $_ENV["MAIL_PASSWORD"];
		$mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	} else {
		$mailer->SMTPAuth = false;
	}

	try {
		$mailer->CharSet = "UTF-8";
		$mailer->Encoding = "base64";
		// $mailer->addEmbeddedImage($root . $logo, "logo_cid", "logo.png", "base64", "image/png");
		$mailer->setFrom($_ENV["MAIL_FROM_ADDRESS"], $_ENV["MAIL_FROM_NAME"]);
		$mailer->addAddress($email, $_ENV["MAIL_FROM_NAME"]);

		$mailer->isHTML(true);
		$mailer->Subject = $fields["title"];
		$mailer->Body = $emailHtml;
		// $mailer->AltBody = "";

		$mailer->send();
		echo json_encode(array("success" => true));
	} catch (Exception $error) {
		echo json_encode(array(
			"error" => "Message could not be sent. Mailer Error: {$mailer->ErrorInfo}",
			"success" => false
		));
	}
?>
