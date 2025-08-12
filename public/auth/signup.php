<?php
/* 
 * File: public/auth/signup.php
 * Scopo: Pagina di registrazione utente.
 * Stato: RIUSO (codice copiato dal progetto OutdoorTribe).
 * ------------------------------------------------------------------
 */

// Avvia la sessione
session_start();

// Inclusione del file di connessione al database e delle funzioni ausiliarie
include("./../server/connection.php");
include("./../admin/functions.php");

// Definisci una variabile per il messaggio di errore
$error_message = '';

// Controllo se la richiesta è di tipo POST
if ($_SERVER['REQUEST_METHOD'] == "POST") {
  // Ottiene i dati dal form
  $name = $_POST['firstName'];
  $surname = $_POST['surname'];
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Controlla se tutti i campi sono stati compilati
  if (!empty($name) && !empty($surname) && !empty($email) && !empty($password)) {
    // Controlla se l'email esiste già nel database usando una query preparata
    $sql_check_email = "SELECT * FROM user WHERE email = ?";
    $stmt = $conn->prepare($sql_check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result_check_email = $stmt->get_result();

    if ($result_check_email->num_rows > 0) {
      // L'email è già registrata
      $error_message = "This email address is already registered.";
    } else {
      // L'email non esiste nel database, esegui l'inserimento dell'utente
      $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash della password

      $sql_insert = "INSERT INTO user (name, surname, email, password) VALUES (?, ?, ?, ?)";
      $stmt = $conn->prepare($sql_insert);
      $stmt->bind_param("ssss", $name, $surname, $email, $hashed_password);
      $stmt->execute();

      // Reindirizza l'utente alla pagina di login dopo la registrazione
      header("Location: login.php");
      die;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>
  <!-- Inclusione dei fogli di stile -->
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/templates/components/components.css">
  <link rel="stylesheet" href="/ecommerce_from_outdoortribe/styles/signup.css">
  <link rel="icon" type="image/svg+xml" href="/ecommerce_from_outdoortribe/assets/icons/favicon.svg">
  <!-- Collegamento al font Roboto -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

  <link rel="icon" type="image/svg+xml" href="/ecommerce_from_outdoortribe/assets/icons/favicon.svg">
</head>

<body>
  <header>
    <!-- Logo header: visibile fino ad una certa dimensione -->
    <img class="logo" src="/ecommerce_from_outdoortribe/assets/icons/logo.svg" alt="Logo - OutdoorTribe">
  </header>
  <main class="outer-flex-container">
    <div class="inner-flex-container">
      <div class="logo-container">
        <!-- Logo container: visibile da una certa dimensione -->
        <img class="logo" src="/ecommerce_from_outdoortribe/assets/icons/logo.svg" alt="Logo - OutdoorTribe">
      </div>
      <!-- Form di registrazione -->
      <form class="form-container" method="post">
        <div class="message-container">
          <!-- Messaggio di benvenuto -->
          <p class="paragraph-400">Welcome! Please create a new account</p>
          <p class="errore"><?php echo $error_message; ?></p>
        </div>
        <div class="data-input-container">
          <!-- Campi di input -->
          <div class="generic">
            <label class="generic" for="firstName">First Name</label>
            <input class="generic-txt" type="text" id="firstName" name="firstName" placeholder="mario" required>
          </div>
          <div class="generic">
            <label class="generic-label" for="surname">Surname</label>
            <input class="generic-txt" type="text" name="surname" id="surname" placeholder="rossi" required>
          </div>
          <div class="email">
            <label class="email-label" for="email">Email</label>
            <input class="email-txt" type="email" id="email" name="email" placeholder="mario.rossi@gmail.com" required>
          </div>
          <div class="password">
            <label class="password-label" for="password">Password</label>
            <input class="password-txt" type="password" id="password" name="password" required>
          </div>
          <!-- Pulsante di registrazione -->
          <div class="buttons-container">
            <input class="full-btn" type="submit" id="signupBtn" value="Sign Up">
          </div>
        </div>
      </form>
      <!-- Messaggio e pulsante per il login -->
      <div class="buttons-container-2">
        <p class="paragraph-400">Already have an account?</p>
        <button class="border-btn" id="loginBtn" onclick="window.location.href="/ecommerce_from_outdoortribe/login.php";">Login</button>
      </div>
    </div>
  </main>
</body>

</html>