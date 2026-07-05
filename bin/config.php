<?php
  require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php";

  if (!class_exists("R", false)) {
    class_alias(\RedBeanPHP\R::class, "R");
  }

  if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/.env")) {
    $vars = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/.env");
    
    foreach ($vars as $key => $var) {
      $_ENV[$key] = $var;
    };
  }

  $driver = $_ENV["DB_DRIVER"] ?? "mysql";

  if ($driver === "sqlite") {
    R::setup("sqlite:" . $_SERVER["DOCUMENT_ROOT"] . "/" . ($_ENV["DB_NAME"] ?? "dev") . ".db");
  } else {
    R::setup(
      "mysql:host=" . $_ENV["DB_HOST"] . ";dbname=" . $_ENV["DB_NAME"] . ";port=" . $_ENV["DB_PORT"],
      $_ENV["DB_USERNAME"],
      $_ENV["DB_PASSWORD"]
    );
  }

  R::freeze($driver !== "sqlite");
  set_include_path("src/");
?>