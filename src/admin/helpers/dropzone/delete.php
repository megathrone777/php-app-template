<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/bin/config.php";

	function respond(int $code, array $payload): never {
		http_response_code($code);
		echo json_encode($payload);
		exit;
	}

	if ($_SERVER["REQUEST_METHOD"] !== "POST") {
		respond(405, [
			"error" => "Method not allowed",
			"success" => false
		]);
	}

	$entityName = trim((string)($_POST["entityName"] ?? ""));
	$entityId = (int)($_POST["entityId"] ?? 0);
	$uploadedUrl = trim((string)($_POST["uploadedUrl"] ?? ""));

	if (!preg_match('/^[a-z0-9_]+$/', $entityName)) {
		respond(400, ["success" => false, "error" => "Invalid entity"]);
	}

	if ($entityId <= 0 || $uploadedUrl === "") {
		respond(400, [
			"error" => "Missing record id or url",
			"success" => false
		]);
	}

	try {
		R::begin();

		$entity = R::load($entityName, $entityId);
		
		if (!$entity->id) {
			throw new RuntimeException("Record not found");
		}

		$entityImages = json_decode((string)$entity->images, true);
		if (!is_array($entityImages)) $entityImages = [];

		$before = count($entityImages);
		$entityImages = array_values(array_filter($entityImages, fn($url) => $url !== $uploadedUrl));
		
		if (count($entityImages) === $before) {
			throw new RuntimeException("URL not found on record");
		}

		$entity->images = json_encode($entityImages, JSON_UNESCAPED_SLASHES);
		R::store($entity);
		R::commit();

		$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/uploads/" . $entityName;
		$base = rawurldecode(basename(parse_url($uploadedUrl, PHP_URL_PATH) ?: ""));
		$path = $base ? $uploadDir . "/" . $base : "";

		if ($base && is_file($path)) {
			@unlink($path);
		}

		respond(200, [
			"id" => (int)$entity->id,
			"images" => $entityImages,
			"success" => true
		]);
	} catch (Throwable $t) {
		R::rollback();
		respond(500, [
			"error" => $t->getMessage(),
			"success" => false
		]);
	}
?>
