<?php
	if (PHP_SAPI !== "cli") {
		http_response_code(403);
		exit("CLI only\n");
	}

	$root = dirname(__DIR__);
	chdir($root);

	$tailwind = __DIR__ . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === "Windows" ? "tw.exe" : "tw");
	$buildCmd = '"' . $tailwind . '" -i ./src/app/css/init.css -o ./public/css/dist.css';

	$snapshot = function () use ($root): array {
		$map = array();
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root . "/src", FilesystemIterator::SKIP_DOTS)
		);

		foreach ($it as $file) {
			if ($file->isFile() && in_array(strtolower($file->getExtension()), array("twig", "css"), true)) {
				$map[$file->getPathname()] = $file->getMTime();
			}
		}

		ksort($map);
		return $map;
	};

	$build = function () use ($buildCmd) {
		echo "[dev] rebuilding CSS… ";
		passthru($buildCmd);
	};

	$server = proc_open(
		'"' . PHP_BINARY . '" -S localhost:8000 index.php',
		array(STDIN, STDOUT, STDERR),
		$pipes
	);

	if (!is_resource($server)) {
		fwrite(STDERR, "Failed running PHP-server\n");
		exit(1);
	}

	$shutdown = function () use ($server) {
		if (is_resource($server)) {
			proc_terminate($server);
		}

		exit(0);
	};

	if (function_exists("sapi_windows_set_ctrl_handler")) {
		sapi_windows_set_ctrl_handler($shutdown);
	} elseif (function_exists("pcntl_signal")) {
		pcntl_signal(SIGINT, $shutdown);
		pcntl_signal(SIGTERM, $shutdown);
	}

	$build();
	$signature = $snapshot();

	while (true) {
		if (!proc_get_status($server)["running"]) {
			$shutdown();
		}

		$current = $snapshot();
		if ($current !== $signature) {
			$signature = $current;
			$build();
		}

		if (function_exists("pcntl_signal_dispatch")) {
			pcntl_signal_dispatch();
		}

		usleep(400000);
	}
?>
