<?php
  require_once $_SERVER["DOCUMENT_ROOT"] . "/bin/config.php";
  session_start();

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = $_POST["login"];
    $password = $_POST["password"];
    $user = R::findOne("users", "login = ?", [$login]);

    if ($user) {
      if (password_verify($password, $user->password)) {
        $_SESSION["user_logged_in"] = TRUE;
      } else {
        $_SESSION["login_failure"] = "Invalid user name or password";
      }

      header("Location: /admin");
      exit;
    } else {
      $_SESSION["login_failure"] = "Invalid user name or password";
      header("Location: /admin");
      exit;
    }
  } else {
    die("Method Not allowed");
  }
?>