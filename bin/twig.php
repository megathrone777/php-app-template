<?php
	require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php";

	use Twig\Environment;
	use Twig\Loader\FilesystemLoader;
	use Twig\Loader\LoaderInterface;
	use Twig\Source;
	use Twig\Node\Node;
	use Twig\Node\ModuleNode;
	use Twig\Node\IncludeNode;
	use Twig\Node\ImportNode;
	use Twig\Node\Expression\ConstantExpression;
	use Twig\NodeVisitor\NodeVisitorInterface;
	use Twig\TwigFunction;
	use Twig\TwigFilter;
	use Twig\Extension\DebugExtension;

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

	final class RelativePathNodeVisitor implements NodeVisitorInterface {
		private array $stack = array();

		public function enterNode(Node $node, Environment $env): Node {
			if ($node instanceof ModuleNode) {
				$name = $node->getTemplateName();

				if ($name !== null && $node->hasNode("parent")) {
					$this->rewrite($node->getNode("parent"), $name);
				}

				$this->stack[] = $name;
			}

			return $node;
		}

		public function leaveNode(Node $node, Environment $env): ?Node {
			if (($node instanceof IncludeNode || $node instanceof ImportNode) && $node->hasNode("expr")) {
				$from = end($this->stack);

				if ($from !== false && $from !== null) {
					$this->rewrite($node->getNode("expr"), $from);
				}
			}

			if ($node instanceof ModuleNode) {
				array_pop($this->stack);
			}

			return $node;
		}

		public function getPriority(): int {
			return 0;
		}

		private function rewrite(Node $expr, string $from): void {
			if (!$expr instanceof ConstantExpression) {
				return;
			}

			$value = $expr->getAttribute("value");

			if (!is_string($value) || $value === "") {
				return;
			}

			$resolved = $this->resolve($value, $from);

			if ($resolved !== $value) {
				$expr->setAttribute("value", $resolved);
			}
		}

		private function resolve(string $name, string $from): string {
			if ($name[0] === "@") {
				return $name;
			}

			$dotRelative = str_starts_with($name, "./") || str_starts_with($name, "../");
			$bareSegment = strpos($name, "/") === false;

			if (!$dotRelative && !$bareSegment) {
				return $name;
			}

			$slash = strrpos($from, "/");
			$dir = $slash === false ? "" : substr($from, 0, $slash);
			$combined = $dir === "" ? $name : $dir . "/" . $name;

			return $this->normalize($combined);
		}

		private function normalize(string $path): string {
			$out = array();

			foreach (explode("/", $path) as $segment) {
				if ($segment === "" || $segment === ".") {
					continue;
				}

				if ($segment === "..") {
					array_pop($out);
					continue;
				}

				$out[] = $segment;
			}

			return implode("/", $out);
		}
	}

	function twig(): Environment {
		static $twig = null;

		if ($twig === null) {
			$root = $_SERVER["DOCUMENT_ROOT"];
			$loader = new FilesystemLoader($root . "/src");
			$isDev = ($_ENV["DB_DRIVER"] ?? "mysql") === "sqlite";

			$twig = new Environment(new EntrypointLoader($loader), array(
				"auto_reload" => $isDev,
				"cache" => $root . "/var/cache/twig",
				"debug" => $isDev,
			));
			$twig->addNodeVisitor(new RelativePathNodeVisitor());
			$twig->addFunction(new TwigFunction("env", fn(string $key, $default = null) => $_ENV[$key] ?? $default));

			// {{ jsonString|json_decode }} -> массив (пусто/битое -> []).
			$twig->addFilter(new TwigFilter("json_decode", fn(?string $json) => json_decode((string) $json, true) ?: []));

			// {{ dump(x) }} — расширение подключаем всегда, но выводит оно
			// только когда debug=true (dev). На проде dump() — тихий no-op.
			$twig->addExtension(new DebugExtension());
		}

		return $twig;
	}
?>
