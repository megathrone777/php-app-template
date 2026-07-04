<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php";

	use Twig\Environment;
	use Twig\Loader\FilesystemLoader;
	use Twig\Loader\LoaderInterface;
	use Twig\Source;
	use Twig\TwigFunction;

	/**
	 * Loader-декоратор: резолвинг "точки входа" директории.
	 * Если имя шаблона не оканчивается на .twig — считаем его папкой и
	 * подставляем /index.twig. Так "web/blocks/header" эквивалентно
	 * "web/blocks/header/index.twig".
	 */
	class EntrypointLoader implements LoaderInterface {
		private LoaderInterface $inner;

		public function __construct(LoaderInterface $inner) {
			$this->inner = $inner;
		}

		private function normalize(string $name): string {
			if (substr($name, -5) === ".twig") {
				return $name;
			}

			return rtrim($name, "/") . "/index.twig";
		}

		public function getSourceContext(string $name): Source {
			return $this->inner->getSourceContext($this->normalize($name));
		}

		public function getCacheKey(string $name): string {
			return $this->inner->getCacheKey($this->normalize($name));
		}

		public function isFresh(string $name, int $time): bool {
			return $this->inner->isFresh($this->normalize($name), $time);
		}

		public function exists(string $name): bool {
			return $this->inner->exists($this->normalize($name));
		}
	}

	/**
	 * Генерирует теги для Vite-точек входа (строка или массив).
	 * CSS всегда выводится <link>'ом ПЕРЕД <script> — чтобы стили
	 * применялись до загрузки JS (без FOUC).
	 *
	 * dev  (задан VITE_DEV_SERVER): подключает dev-сервер Vite + HMR.
	 * prod (не задан):              читает build/.vite/manifest.json и
	 *                               подставляет собранные CSS/JS с хешами.
	 */
	function viteAssets($entries): string {
		$entries = (array) $entries;
		$base = "/build";
		$devServer = $_ENV["VITE_DEV_SERVER"] ?? null;

		$links = "";
		$scripts = "";

		if (!empty($devServer)) {
			$devServer = rtrim($devServer, "/");
			$scripts .= '<script type="module" src="' . $devServer . $base . '/@vite/client"></script>' . "\n";

			foreach ($entries as $entry) {
				if (str_ends_with($entry, ".css")) {
					// ?direct — Vite отдаёт скомпилированный text/css (без HMR-обёртки),
					// поэтому это настоящий <link>, применяющийся до загрузки JS.
					$links .= '<link rel="stylesheet" href="' . $devServer . $base . '/' . $entry . '?direct">' . "\n";
				} else {
					$scripts .= '<script type="module" src="' . $devServer . $base . '/' . $entry . '"></script>' . "\n";
				}
			}

			return $links . $scripts;
		}

		$manifestPath = $_SERVER["DOCUMENT_ROOT"] . $base . "/.vite/manifest.json";

		if (!is_file($manifestPath)) {
			return "<!-- vite: manifest not found, run `yarn build` -->";
		}

		$manifest = json_decode(file_get_contents($manifestPath), true);

		foreach ($entries as $entry) {
			if (!isset($manifest[$entry])) {
				$scripts .= "<!-- vite: entry \"" . htmlspecialchars($entry) . "\" not in manifest -->\n";
				continue;
			}

			$chunk = $manifest[$entry];

			// CSS, извлечённый из JS-чанка (если такой есть).
			foreach ($chunk["css"] ?? array() as $css) {
				$links .= '<link rel="stylesheet" href="' . $base . '/' . $css . '">' . "\n";
			}

			if (str_ends_with($entry, ".css")) {
				$links .= '<link rel="stylesheet" href="' . $base . '/' . $chunk["file"] . '">' . "\n";
			} else {
				$scripts .= '<script type="module" src="' . $base . '/' . $chunk["file"] . '"></script>' . "\n";
			}
		}

		return $links . $scripts;
	}

	function twig(): Environment {
		static $twig = null;

		if ($twig === null) {
			$root = $_SERVER["DOCUMENT_ROOT"];

			// Единый корень /src. Имена шаблонов пишутся от него:
			// "web/pages/home.twig", "admin/blocks/layout.twig" и т.д.
			$loader = new FilesystemLoader($root . "/src");

			$twig = new Environment(new EntrypointLoader($loader), array(
				"auto_reload" => ($_ENV["DB_DRIVER"] ?? "mysql") === "sqlite",
				"cache" => $root . "/var/cache/twig",
			));

			$twig->addFunction(new TwigFunction("vite", "viteAssets", array(
				"is_safe" => array("html"),
			)));
		}

		return $twig;
	}
?>
