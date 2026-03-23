<?php
require_once 'config.php';

$promo = trim($_GET['promo'] ?? '');
$showAll = ($promo === '');
$sql = "SELECT id, prenom, nom, specialite, promo, avatar FROM utilisateurs";
$params = [];
if ($promo !== '') {
  $sql .= " WHERE promo = ?";
  $params[] = $promo;
}
$sql .= " ORDER BY nom ASC, prenom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trombinoscope — Accueil</title>
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
      <li><a href="register.php">Inscription</a></li>
      <li><a href="login.php" class="btn-nav">Connexion</a></li>
    </ul>
  </nav>

  <div class="container">

    <div class="hero">
      <h1>Le trombinoscope<br>de <em>votre promo <u>B1</u></em></h1>
      <p>Retrouvez tous vos camarades, partagez vos publications et échangez des commentaires.</p>
      <a href="register.php" class="btn btn-primary btn-inline">Rejoindre la promo</a>
    </div>

    <div class="flash flash-success">
      Bienvenue sur le trombinoscope ! Inscrivez-vous pour rejoindre la promo.
    </div>

    <div class="filter-bar">
      <?php $isAll = ($promo === ''); ?>
      <a href="index.php" class="filter-btn <?php echo $isAll ? 'active' : ''; ?>">Tous</a>
      <a href="index.php?promo=BUT1+2024" class="filter-btn <?php echo $promo === 'BUT1 2024' ? 'active' : ''; ?>">BUT1 2024</a>
      <a href="index.php?promo=BUT2+2023" class="filter-btn <?php echo $promo === 'BUT2 2023' ? 'active' : ''; ?>">BUT2 2023</a>
      <a href="index.php?promo=BUT3+2022" class="filter-btn <?php echo $promo === 'BUT3 2022' ? 'active' : ''; ?>">BUT3 2022</a>
    </div>

    <div class="trombi-grid">

      <?php if ($showAll || $promo === 'BUT1 2024'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Alice&backgroundColor=b6e3f4" alt="Alice Martin">
          <div class="card-body">
            <div class="card-name">Alice Martin</div>
            <div class="card-role">Développeuse Web</div>
            <span class="card-promo">BUT1 2024</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT1 2024'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Lucas&backgroundColor=ffdfbf" alt="Lucas Bernard">
          <div class="card-body">
            <div class="card-name">Lucas Bernard</div>
            <div class="card-role">Designer UI</div>
            <span class="card-promo">BUT1 2024</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT2 2023'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Sofia&backgroundColor=d1f4d1" alt="Sofia Dupont">
          <div class="card-body">
            <div class="card-name">Sofia Dupont</div>
            <div class="card-role">Data Analyst</div>
            <span class="card-promo">BUT2 2023</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT2 2023'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Karim&backgroundColor=ffd5dc" alt="Karim Ndiaye">
          <div class="card-body">
            <div class="card-name">Karim Ndiaye</div>
            <div class="card-role">DevOps</div>
            <span class="card-promo">BUT2 2023</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT3 2022'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Emma&backgroundColor=e8d5ff" alt="Emma Leroy">
          <div class="card-body">
            <div class="card-name">Emma Leroy</div>
            <div class="card-role">Product Manager</div>
            <span class="card-promo">BUT3 2022</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT3 2022'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Noah&backgroundColor=fff3b0" alt="Noah Girard">
          <div class="card-body">
            <div class="card-name">Noah Girard</div>
            <div class="card-role">Sécurité Réseau</div>
            <span class="card-promo">BUT3 2022</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT1 2024'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Yasmine&backgroundColor=c0f0f0" alt="Yasmine Benali">
          <div class="card-body">
            <div class="card-name">Yasmine Benali</div>
            <div class="card-role">Développeuse Mobile</div>
            <span class="card-promo">BUT1 2024</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($showAll || $promo === 'BUT2 2023'): ?>
      <div class="trombi-card card">
        <a href="profil.php">
          <img class="card-img" src="https://api.dicebear.com/7.x/personas/svg?seed=Tom&backgroundColor=ffd5b0" alt="Tom Faure">
          <div class="card-body">
            <div class="card-name">Tom Faure</div>
            <div class="card-role">Administrateur Sys.</div>
            <span class="card-promo">BUT2 2023</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <?php foreach ($utilisateurs as $utilisateur): ?>
        <?php
        $id = (int) $utilisateur['id'];
        $prenom = $utilisateur['prenom'] ?? '';
        $nom = $utilisateur['nom'] ?? '';
        $specialite = $utilisateur['specialite'] ?? '';
        $promo = $utilisateur['promo'] ?? '';
        $avatar = $utilisateur['avatar'] ?? 'default.svg';
        $avatarPath = './uploads/' . $avatar;
        $fullName = trim($prenom . ' ' . $nom);
        ?>
        <div class="trombi-card card">
          <a href="profil.php?id=<?php echo $id; ?>">
            <img class="card-img" src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="card-body">
              <div class="card-name"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="card-role"><?php echo htmlspecialchars($specialite, ENT_QUOTES, 'UTF-8'); ?></div>
              <span class="card-promo"><?php echo htmlspecialchars($promo, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

  <footer>
    <div class="container">
      <p>Trombinoscope &mdash; Projet PHP &copy; <span class="footer-year"></span></p>
    </div>
  </footer>

</body>

</html>


