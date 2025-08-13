<?php
/* 
 * File: public/auth/login.php
 * Scopo: Pagina di login utente.
 * Stato: RIUSO (codice copiato dal progetto OutdoorTribe).
 * ------------------------------------------------------------------
 */

// Avvia la sessione
session_start();

// Inclusione del file di connessione al database e delle funzioni ausiliarie
include("./../server/connection.php");
include("./../admin/functions.php");

// Include il file di configurazione del percorso per arrivare a config_path.php
require_once __DIR__ . '/../config_path.php';

// Definisci una variabile per il messaggio di errore
$error_message = '';

// Controllo se la richiesta è di tipo POST
if ($_SERVER['REQUEST_METHOD'] == "POST") {
  // Ottiene l'email e la password dall'input del form
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Controlla se l'email e la password non sono vuote
  if (!empty($email) && !empty($password)) {
    // Controlla se l'email esiste già nel database usando una query preparata
    $sql_check_email = "SELECT * FROM user WHERE email = ?";
    $stmt = $conn->prepare($sql_check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result_check_email = $stmt->get_result();

    if ($result_check_email->num_rows > 0) {
      // Ottiene i dati dell'utente
      $user_data = $result_check_email->fetch_assoc();
      // Verifica se la password corrisponde a quella nel database
      if (password_verify($password, $user_data['password'])) {
        // Imposta l'ID dell'utente nella sessione e reindirizza alla homepage
        $_SESSION['user_id'] = $user_data['id'];
        header("Location: homepage.php");
        die;
      } else {
        // Password errata
        $error_message = 'Wrong password';
      }
    } else {
      // Email non presente nel database
      $error_message = 'Email not found';
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <!-- Inclusione dei fogli di stile -->
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/header/header.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/components/components.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/styles/login.css">
  <link rel="icon" type="image/svg+xml" href="/ecommerce_from_outdoortribe/assets/icons/favicon.svg">
  <!-- Collegamento al font Roboto -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>

<body>
  <header>
    <!-- Logo header: visibile fino ad una certa dimensione -->
    <img class="logo" src="/ecommerce_from_outdoortribe/assets/icons/logo.svg" alt="Logo - OutdoorTribe">
  </header>
  <main class="outer-flex-container">
    <div class="image-container">
      <!-- Immagine di login -->
      <img class="login-image" src="/ecommerce_from_outdoortribe/assets/icons/login.svg" alt="login-image">
    </div>
    <form class="form-container" method="post">
      <div class="inner-flex-container">
        <div class="logo-container">
          <!-- Logo container: visibile da una certa dimensione -->
          <img class="logo" src="/ecommerce_from_outdoortribe/assets/icons/logo.svg" alt="Logo - OutdoorTribe">
        </div>
        <div class="message-container">
          <!-- Messaggi di benvenuto -->
          <p class="paragraph-450">Elevate your adventures with OutdoorTribe - where every step is a journey.</p>
          <p class="paragraph-400">Welcome Back, please login to your account</p>
          <p class="errore"><?php echo $error_message; ?></p>
        </div>
        <div class="data-input-container">
          <div class="email">
            <!-- Campo di input per l'email -->
            <label class="email-label" for="email">Email</label>
            <input class="email-txt" type="email" id="email" name="email" placeholder="mario.rossi@gmail.com" required>
          </div>
          <div class="password">
            <!-- Campo di input per la password -->
            <label class="password-label" for="password">Password</label>
            <input class="password-txt" type="password" id="password" name="password" maxlength="12" required>
          </div>
        </div>
        <div class="buttons-container">
          <!-- Pulsante per il login -->
          <input class="full-btn" type="submit" id="loginBtn" value="Login">
          <!-- Pulsante per la registrazione -->
          <button class="border-btn" id="signupBtn" onclick="window.location.href='/ecommerce_from_outdoortribe/signup.php';">Sign up</button>
        </div>
      </div>
    </form>
  </main>
</body>

</html>