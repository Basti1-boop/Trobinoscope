<?php
session_start();
require_once 'config.php';


/*
Lors de la soumission du formulaire :
    - Verifier que les champs sont remplis
    - Verifier que l'email est au bon format
    - Le mot de passe doit comporter au moins 6 caracteres
    - Verifier que l'email existe dans la base (table utilisateurs)
    - Si ok, connecter l'utilisateur
    - Afficher un message d'erreur en fonction du resultat
*/


$errors = [];
$email = '';
$remember = false;
$successMessage = '';
$loginSuccess = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);


    if (empty($email) || $email == '') {
        $errors['email'] = "<span style='color:red;'>Erreur : L'email est incorrect</span>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "<span style='color:red;'>Erreur : L'email est incorrect</span>";
    }


    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = "<span style='color:red;'>Erreur : Votre mot de passe est incorrect</span>";
    }


    // Si aucune erreur verifier l'utilisateur
    if (empty($errors)) {
        $emailTab = "SELECT id, prenom, nom, email, password FROM `utilisateurs` WHERE email = '$email' LIMIT 1";
        $res = $pdo->query($emailTab);


        if ($res->rowCount() < 1) {
            $errors['email'] = "<span style='color:red;'>Erreur : Email ou mot de passe incorrect</span>";
        } else {
            $user = $res->fetch(PDO::FETCH_ASSOC);


            if (!password_verify($password, $user['password'])) {
                $errors['password'] = "<span style='color:red;'>Erreur : Email ou mot de passe incorrect</span>";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_nom'] = $user['nom'];
                $loginSuccess = true;
                $successMessage = "Connexion reussie. Redirection en cours...";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trombinoscope — Connexion</title>
    <?php if ($loginSuccess): ?>
        <meta http-equiv="refresh" content="2;url=index.php">
    <?php endif; ?>
    <link rel="stylesheet" href="./assets/css/style.css">
    <script src="./assets/js/script.js" defer></script>
</head>


<body>

    <nav>
        <a href="index.php" class="nav-logo">trombi<span>.</span></a>
        <button class="nav-toggle" aria-label="Ouvrir le menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="register.php" class="btn-nav">Inscription</a></li>
        </ul>
    </nav>


    <div class="container-sm">

        <?php if ($successMessage !== ''): ?>
            <div class="flash flash-success">
                <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-title">Se connecter</div>
            <div class="form-subtitle">Bon retour parmi nous.</div>


            <form action="" method="post">


                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="alice@exemple.fr"
                        required
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo $errors['email'] ?? ''; ?>
                </div>


                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                    <?php echo $errors['password'] ?? ''; ?>
                </div>


                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" value="1" <?php echo $remember ? 'checked' : ''; ?>>
                    <label for="remember">Se souvenir de moi</label>
                </div>


                <button type="submit" class="btn btn-primary">Se connecter</button>


            </form>


            <div class="form-footer">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </div>
        </div>
    </div>


    <footer>
        <div class="container">
            <p>Trombinoscope &mdash; Projet PHP &copy; <span class="footer-year"></span></p>
        </div>
    </footer>

</body>


</html>



