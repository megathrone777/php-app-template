<?php
  require_once $_SERVER["DOCUMENT_ROOT"] . "/config/config.php";
  session_start();

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $user = R::findOne("users", "username = ?", [$username]);

    if ($user) {
      $userPassword = $user->password;
      $userID = $user->id;

      if (password_verify($password, $userPassword)) {
        $_SESSION["user_logged_in"] = TRUE;
        header("Location: /admin");
      } else {
        $_SESSION["login_failure"] = "Invalid user name or password";
        header("Location: /admin/login");
      }

      exit;
    } else {
      $_SESSION["login_failure"] = "Invalid user name or password";
      header("Location: /admin/login");
      exit;
    }
  } else {
    die("Method Not allowed");
  }