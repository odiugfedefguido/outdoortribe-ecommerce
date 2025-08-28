<?php

// Funzione per controllare se l'utente è autenticato
function checkLogin($conn)
{
  // Controlla se è stata avviata una sessione e se è presente l'ID dell'utente
  if (isset($_SESSION['user_id'])) {
    $id = $_SESSION['user_id'];
    // Query per verificare se l'utente esiste nel database
    $sql = "SELECT id FROM user WHERE id = ? LIMIT 1";

    // Prepara la dichiarazione
    $stmt = $conn->prepare($sql);
    // Associa il parametro alla query
    $stmt->bind_param("i", $id);
    // Esegue la query sul database
    $stmt->execute();
    // Ottiene il risultato
    $result = $stmt->get_result();

    // Verifica se esiste un risultato per la query
    if ($result->num_rows > 0) {
      // L'utente esiste e la sessione è valida
      return true;
    }
  }

  // Se l'utente non è autenticato, reindirizza alla pagina di login
  header("Location: login.php");
  // Interrompe l'esecuzione dello script
  die;
}

// Funzione per ottenere l'URL della foto del profilo dell'utente
function getProfilePhotourl($conn, $user_id)
{
  // Query per selezionare la foto del profilo dell'utente
  $query_photo_profile = "SELECT name FROM photo WHERE user_id = $user_id AND post_id IS NULL";
  // Esegue la query sul database
  $result_photo_profile = $conn->query($query_photo_profile);

  // Verifica se esiste una foto del profilo associata all'utente
  if ($result_photo_profile->num_rows > 0) {
    // Recupera il nome della foto del profilo
    $photo_profile_row = $result_photo_profile->fetch_assoc();
    // Costruisce l'URL della foto del profilo
    $profile_photo_url = "./../uploads/photos/profile/" . $photo_profile_row['name'];
  } else {
    // Se non c'è una foto del profilo associata all'utente, utilizza un'immagine predefinita
    $profile_photo_url = "./../assets/icons/profile.svg";
  }
  // Ritorna l'URL della foto del profilo
  return $profile_photo_url;
}

// Funzione per ottenere la media dei rating per un determinato post
function getAverageRating($conn, $post_id)
{
  // Query per calcolare la media dei rating per il post
  $query_average_rating = "SELECT AVG(rating) AS average_rating FROM post_ratings WHERE post_id = ?";
  // Prepara la query per l'esecuzione
  $stmt_avg_rating = $conn->prepare($query_average_rating);
  // Associa i parametri alla query
  $stmt_avg_rating->bind_param("i", $post_id);
  // Esegue la query
  $stmt_avg_rating->execute();
  // Ottiene il risultato della query
  $result_avg_rating = $stmt_avg_rating->get_result();

  // Estrae la media dei rating dal risultato della query
  $average_rating_row = $result_avg_rating->fetch_assoc();
  $average_rating = $average_rating_row['average_rating'];

  // Gestisce il caso in cui non ci sono valutazioni per il post
  if ($average_rating === null) {
    $average_rating = 0;
  }
  // Ritorna la media dei rating
  return $average_rating;
}

// Funzione per ottenere il numero di stelle piene e mezze in base al rating
function getStars($rating)
{
  // Calcola il numero di stelle piene (parte intera del rating)
  $full_stars = floor($rating);
  // Calcola il numero di mezze stelle (parte decimale del rating)
  $half_star = ceil($rating - $full_stars);
  // Ritorna il numero di stelle piene e mezze
  return array($full_stars, $half_star);
}

// Funzione per verificare se l'utente ha messo like a un post
function getLike($conn, $post_id, $current_user_id)
{
  // Query per verificare se l'utente ha messo like a questo post
  $checkQuery = "SELECT COUNT(*) FROM likes WHERE post_id = {$post_id} AND user_id = $current_user_id";
  // Esegue la query sul database
  $checkResult = mysqli_query($conn, $checkQuery);
  // Estrae il risultato della query
  $row_likes = mysqli_fetch_array($checkResult);

  // Se l'utente ha messo like a questo post, imposta la classe corrispondente
  if ($row_likes[0] > 0) {
    $like_icon_class = 'like-icon liked';
  } else {
    $like_icon_class = 'like-icon';
  }
  // Ritorna la classe del like
  return $like_icon_class;
}

// Funzione per ottenere le informazioni dell'utente
function getUserInfo($conn, $user_id)
{
  // Query per selezionare il nome e il cognome dell'utente dal database
  $user_query = "SELECT name, surname FROM user WHERE id = $user_id";
  // Esegue la query sul database
  $user_result = $conn->query($user_query);

  // Verifica se esiste un risultato per la query
  if ($user_result && $user_result->num_rows > 0) {
    // Recupera i dati dell'utente
    return $user_result->fetch_assoc();
  } else {
    return false; // Ritorna false se l'utente non è stato trovato
  }
}
function updateImgProfile($conn, $newImg, $user)
{
  // Query per aggiornare la foto profilo
  $user_query = "UPDATE photo SET name = ? WHERE user_id = ?";
  $stmt = $conn->prepare($user_query);
  $stmt->bind_param("si", $newImg, $user);
  return $stmt->execute();
}

function insertRating($conn, $post_id, $user_id, $rating)
{
  $insertQuery = "INSERT INTO post_ratings (post_id, user_id, rating, created_at) VALUES (?, ?, ?, NOW())";
  $insertStmt = $conn->prepare($insertQuery);
  $insertStmt->bind_param('iii', $post_id, $user_id, $rating);
  if ($insertStmt->execute()) {
    return true;
  } else {
    return false;
  }
}

function updateRating($conn, $post_id, $user_id, $rating)
{
  $updateQuery = "UPDATE post_ratings SET rating = ?, created_at = NOW() WHERE post_id = ? AND user_id = ?";
  $updateStmt = $conn->prepare($updateQuery);
  $updateStmt->bind_param('iii', $rating, $post_id, $user_id);
  if ($updateStmt->execute()) {
    return true;
  } else {
    return false;
  }
}

function checkRating($conn, $post_id, $user_id)
{
  $checkRatingQuery = "SELECT * FROM post_ratings WHERE post_id = ? AND user_id = ?";
  $checkRatingStmt = $conn->prepare($checkRatingQuery);
  $checkRatingStmt->bind_param('ii', $post_id, $user_id);
  $checkRatingStmt->execute();
  return $checkRatingStmt->get_result();
}

function insertDifficulty($conn, $post_id, $user_id, $difficulty)
{
  $updateDifficultyQuery = "UPDATE post SET difficulty = ? WHERE id = ? AND user_id = ?";
  $updateDifficultyStmt = $conn->prepare($updateDifficultyQuery);
  $updateDifficultyStmt->bind_param('sii', $difficulty, $post_id, $user_id);
  if ($updateDifficultyStmt->execute()) {
    return true;
  } else {
    return false;
  }
}

function getProfile($conn, $input, $currentUserId)
{
  // Query per ottenere i dettagli dell'utente e la foto del profilo
  $query = $conn->prepare("
    SELECT user.id, user.name, user.surname, photo.name as photo_name 
    FROM user 
    LEFT JOIN photo ON user.id = photo.user_id AND photo.post_id IS NULL
    WHERE (user.name LIKE ? OR user.surname LIKE ?) AND user.id != ?
  ");
  $searchTerm = "%$input%";
  $query->bind_param("ssi", $searchTerm, $searchTerm, $currentUserId);
  $query->execute();
  return $query->get_result();
}
