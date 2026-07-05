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

	$allowedMimes = array_filter(
		array_map(
			"trim",
			explode(",", (string)($_ENV["UPLOAD_ALLOWED_MIME"] ?? ""))
		)
	);
	$entityName = trim((string)($_POST["entityName"] ?? ""));
	$entityId = (int)($_POST["entityId"] ?? 0);

	if (!preg_match('/^[a-z0-9_]+$/', $entityName)) {
		respond(400, ["success" => false, "error" => "Invalid entity"]);
	}

	if ($entityId <= 0) {
		respond(400, [
			"error" => "Missing entity id",
			"success" => false
		]);
	}

	if (empty($_FILES["images"]["name"][0])) {
		respond(400, [
			"error" => "No files uploaded",
			"success" => false
		]);
	}

	$uploadUrl = "/uploads/" . $entityName;
	$uploadDir = $_SERVER["DOCUMENT_ROOT"] . $uploadUrl;

	$files = $_FILES["images"];
	$count = count($files["name"]);
	$uploaded = [];
	$errors = [];

	try {
		R::begin();

		$entity = R::load($entityName, $entityId);
		
		if (!$entity->id) {
			throw new RuntimeException("Record not found");
		}

		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0755, true);
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);

		for ($i = 0; $i < $count; $i++) {
			$name = $files["name"][$i];

			if ($files["error"][$i] !== UPLOAD_ERR_OK) {
				$errors[] = "Upload error for $name";
				continue;
			}

			$mime = $finfo->file($files["tmp_name"][$i]) ?: "";

			if (!in_array($mime, $allowedMimes, true)) {
				$errors[] = "Unsupported format: $name";
				continue;
			}

			$ext = "." . preg_replace('/[^a-z0-9]+/', "", strtolower(substr($mime, strpos($mime, "/") + 1)));
			$basename = bin2hex(random_bytes(16)) . $ext;
			$dest = $uploadDir . "/" . $basename;

			if (!move_uploaded_file($files["tmp_name"][$i], $dest)) {
				$errors[] = "Failed to save $name";
				continue;
			}

			$uploaded[] = $uploadUrl . "/" . rawurlencode($basename);
		}

		$existing = json_decode((string)$entity->images, true);
		if (!is_array($existing)) $existing = [];

		$entityImages = array_values(array_unique(array_merge($existing, $uploaded)));
		$entity->images = json_encode($entityImages, JSON_UNESCAPED_SLASHES);

		R::store($entity);
		R::commit();

		$ok = $uploaded && !$errors;

		respond($ok ? 200 : 400, [
			"errors" => $errors,
			"id" => (int)$entity->id,
			"images" => $entityImages,
			"success" => $ok,
			"uploaded" => $uploaded
		]);
	} catch (Throwable $t) {
		R::rollback();
		respond(500, [
			"error" => $t->getMessage(),
			"success" => false
		]);
	}
?>
