<?php
	if (PHP_SAPI !== "cli") {
		http_response_code(403);
		exit("CLI only\n");
	}

	$bin = __DIR__ . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === "Windows" ? "tw.exe" : "tw");

	if (($argv[1] ?? null) === "download") {
		if (is_file($bin)) {
			echo "Tailwind already installed: $bin\n";
			echo "(for update remove file and run `composer tw:download`)\n";
			exit(0);
		}

		$assets = array(
			"Windows" => "tailwindcss-windows-x64.exe",
			"Darwin"  => php_uname("m") === "arm64" ? "tailwindcss-macos-arm64" : "tailwindcss-macos-x64",
			"Linux"   => str_contains(php_uname("m"), "aarch64") ? "tailwindcss-linux-arm64" : "tailwindcss-linux-x64",
		);

		$asset = $assets[PHP_OS_FAMILY] ?? "tailwindcss-linux-x64";
		$url   = "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/$asset";
		echo "Downloading $asset...\n";

		if (!@copy($url, $bin)) {
			fwrite(STDERR, "Failed to download $url\n");
			fwrite(STDERR, "Check internet connection and enabled PHP allow_url_fopen + openssl.\n");
			exit(1);
		}

		if (PHP_OS_FAMILY !== "Windows") {
			chmod($bin, 0755);
		}

		echo "Tailwind готов: $bin\n";
		exit(0);
	}

	if (!is_file($bin)) {
		fwrite(STDERR, "Binary Tailwind not found. Run `composer tw:download`.\n");
		exit(1);
	}

	$args = array_slice($argv, 1);
	$cmd  = escapeshellarg($bin) . " " . implode(" ", $args);

	passthru($cmd, $code);
	exit($code);
?>
