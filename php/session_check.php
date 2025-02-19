<?php
// Vérification qu'une session est deja active avant d'en lancer une
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Condition d'authentification à toute les pages, si l'email n'est pas reconnu 
// l'utilisateur à l'obligation de se connecter
if(!isset($_SESSION['email'])){
    header('Location: login.php');
    exit;
}
?>