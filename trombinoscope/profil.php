<?php
session_start();
require_once 'config.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$isLoggedIn = isset($_SESSION['user_id']);
$profileId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$fakeKey = strtolower(trim($_GET['fake'] ?? ''));

$fakeProfiles = [
  'alice' => [
    'prenom' => 'Alice',
    'nom' => 'Martin',
    'specialite' => 'Developpeuse Web',
    'promo' => 'BUT1 2024',
    'bio' => 'Passionnee par le developpement front-end et les interfaces accessibles. Je cherche un stage pour juin 2025.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Alice&backgroundColor=b6e3f4',
    'publications' => [
      [
        'id' => -1,
        'contenu' => "Je viens de finir mon projet de BUT1 sur les APIs REST. Si quelqu'un veut un retour croise, n'hesitez pas !",
        'created_at' => 'il y a 2 heures',
        'comments' => [
          [
            'prenom' => 'Lucas',
            'nom' => 'Bernard',
            'contenu' => 'Super ! Je suis partant pour un echange de code review.',
            'fake_key' => 'lucas',
          ],
          [
            'prenom' => 'Sofia',
            'nom' => 'Dupont',
            'contenu' => 'Moi aussi, tu peux regarder le mien en echange ?',
            'fake_key' => 'sofia',
          ],
        ],
      ],
      [
        'id' => -2,
        'contenu' => "Quelqu'un a des ressources sur Docker pour debutants ? Je commence a m'y mettre serieusement.",
        'created_at' => 'il y a 3 jours',
        'comments' => [
          [
            'prenom' => 'Karim',
            'nom' => 'Ndiaye',
            'contenu' => 'La doc officielle de Docker est vraiment bien faite pour les debutants.',
            'fake_key' => 'karim',
          ],
        ],
      ],
    ],
  ],
  'lucas' => [
    'prenom' => 'Lucas',
    'nom' => 'Bernard',
    'specialite' => 'Designer UI',
    'promo' => 'BUT1 2024',
    'bio' => 'J adore concevoir des interfaces simples, claires et rapides a utiliser.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Lucas&backgroundColor=ffdfbf',
    'publications' => [
      [
        'id' => -3,
        'contenu' => 'Je teste une nouvelle grille responsive pour notre projet de trombinoscope.',
        'created_at' => 'il y a 1 jour',
        'comments' => [
          [
            'prenom' => 'Emma',
            'nom' => 'Leroy',
            'contenu' => 'Trop bien, partage un lien quand tu peux.',
            'fake_key' => 'emma',
          ],
        ],
      ],
    ],
  ],
  'sofia' => [
    'prenom' => 'Sofia',
    'nom' => 'Dupont',
    'specialite' => 'Data Analyst',
    'promo' => 'BUT2 2023',
    'bio' => 'Passionnee par les donnees et les visualisations claires.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Sofia&backgroundColor=d1f4d1',
    'publications' => [
      [
        'id' => -4,
        'contenu' => 'J ai prepare un dashboard pour suivre nos inscriptions.',
        'created_at' => 'il y a 5 heures',
        'comments' => [
          [
            'prenom' => 'Yasmine',
            'nom' => 'Benali',
            'contenu' => 'Tu peux montrer la maquette en cours ?',
            'fake_key' => 'yasmine',
          ],
        ],
      ],
    ],
  ],
  'karim' => [
    'prenom' => 'Karim',
    'nom' => 'Ndiaye',
    'specialite' => 'DevOps',
    'promo' => 'BUT2 2023',
    'bio' => 'Infrastructure, automatisation et bonnes pratiques.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Karim&backgroundColor=ffd5dc',
    'publications' => [
      [
        'id' => -5,
        'contenu' => 'J ai mis en place un script de sauvegarde des uploads.',
        'created_at' => 'il y a 4 jours',
        'comments' => [
          [
            'prenom' => 'Tom',
            'nom' => 'Faure',
            'contenu' => 'Top, on pourra l adapter pour les logs.',
            'fake_key' => 'tom',
          ],
        ],
      ],
    ],
  ],
  'emma' => [
    'prenom' => 'Emma',
    'nom' => 'Leroy',
    'specialite' => 'Product Manager',
    'promo' => 'BUT3 2022',
    'bio' => 'J aime cadrer les besoins et prioriser le bon livrable.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Emma&backgroundColor=e8d5ff',
    'publications' => [
      [
        'id' => -6,
        'contenu' => 'Quelles fonctionnalites vous semblent indispensables pour la v1 ?',
        'created_at' => 'il y a 2 jours',
        'comments' => [
          [
            'prenom' => 'Alice',
            'nom' => 'Martin',
            'contenu' => 'Un filtre par promo et une recherche rapide.',
            'fake_key' => 'alice',
          ],
        ],
      ],
    ],
  ],
  'noah' => [
    'prenom' => 'Noah',
    'nom' => 'Girard',
    'specialite' => 'Securite Reseau',
    'promo' => 'BUT3 2022',
    'bio' => 'Je veille a la securite et aux bonnes pratiques de code.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Noah&backgroundColor=fff3b0',
    'publications' => [
      [
        'id' => -7,
        'contenu' => 'Pensez a valider les uploads pour eviter les failles.',
        'created_at' => 'il y a 6 jours',
        'comments' => [
          [
            'prenom' => 'Lucas',
            'nom' => 'Bernard',
            'contenu' => 'Bien vu, je rajoute une verif MIME.',
            'fake_key' => 'lucas',
          ],
        ],
      ],
    ],
  ],
  'yasmine' => [
    'prenom' => 'Yasmine',
    'nom' => 'Benali',
    'specialite' => 'Developpeuse Mobile',
    'promo' => 'BUT1 2024',
    'bio' => 'Je code des applis mobiles et des interfaces fluides.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Yasmine&backgroundColor=c0f0f0',
    'publications' => [
      [
        'id' => -8,
        'contenu' => 'Je peux adapter le trombinoscope pour mobile.',
        'created_at' => 'il y a 8 heures',
        'comments' => [
          [
            'prenom' => 'Sofia',
            'nom' => 'Dupont',
            'contenu' => 'Oui, surtout la grille des profils !',
            'fake_key' => 'sofia',
          ],
        ],
      ],
    ],
  ],
  'tom' => [
    'prenom' => 'Tom',
    'nom' => 'Faure',
    'specialite' => 'Administrateur Sys.',
    'promo' => 'BUT2 2023',
    'bio' => 'Serveurs, maintenance et automatisation.',
    'avatar' => 'https://api.dicebear.com/7.x/personas/svg?seed=Tom&backgroundColor=ffd5b0',
    'publications' => [
      [
        'id' => -9,
        'contenu' => 'Si besoin, je peux configurer un acces SSH propre.',
        'created_at' => 'il y a 1 semaine',
        'comments' => [
          [
            'prenom' => 'Karim',
            'nom' => 'Ndiaye',
            'contenu' => 'Yes, on fera ca apres la soutenance.',
            'fake_key' => 'karim',
          ],
        ],
      ],
    ],
  ],
];

$isFakeProfile = ($fakeKey !== '' && isset($fakeProfiles[$fakeKey]));

if ($isFakeProfile) {
  $profil = $fakeProfiles[$fakeKey];
  $fullName = trim(($profil['prenom'] ?? '') . ' ' . ($profil['nom'] ?? ''));
  $avatarPath = $profil['avatar'] ?? 'default.svg';
  $specialite = $profil['specialite'] ?? '';
  $promo = $profil['promo'] ?? '';
  $bio = $profil['bio'] ?? '';
  $isOwnProfile = false;
  $publications = [];
  $commentsByPublication = [];
  foreach ($profil['publications'] as $publication) {
    $pubId = (int) $publication['id'];
    $publications[] = [
      'id' => $pubId,
      'utilisateur_id' => 0,
      'contenu' => $publication['contenu'] ?? '',
      'created_at' => $publication['created_at'] ?? '',
      'prenom' => $profil['prenom'] ?? '',
      'nom' => $profil['nom'] ?? '',
    ];
    $commentsByPublication[$pubId] = [];
    foreach ($publication['comments'] as $comment) {
      $commentsByPublication[$pubId][] = [
        'utilisateur_id' => 0,
        'prenom' => $comment['prenom'] ?? '',
        'nom' => $comment['nom'] ?? '',
        'contenu' => $comment['contenu'] ?? '',
        'fake_key' => $comment['fake_key'] ?? '',
      ];
    }
  }
} else {
  if ($profileId <= 0) {
    header('Location: index.php');
    exit();
  }

  $stmt = $pdo->prepare("SELECT id, prenom, nom, specialite, promo, bio, avatar FROM utilisateurs WHERE id = ? LIMIT 1");
  $stmt->execute([$profileId]);
  $profil = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$profil) {
    header('Location: index.php');
    exit();
  }

  $fullName = trim(($profil['prenom'] ?? '') . ' ' . ($profil['nom'] ?? ''));
  $avatar = $profil['avatar'] ?? 'default.svg';
  $avatarPath = preg_match('/^https?:\\/\\//', $avatar) ? $avatar : './uploads/' . $avatar;
  $specialite = $profil['specialite'] ?? '';
  $promo = $profil['promo'] ?? '';
  $bio = $profil['bio'] ?? '';
  $isOwnProfile = $isLoggedIn && ((int) $_SESSION['user_id'] === (int) $profil['id']);

  $stmt = $pdo->prepare(
    "SELECT p.id, p.utilisateur_id, p.contenu, p.created_at, u.prenom, u.nom " .
      "FROM publications p " .
      "JOIN utilisateurs u ON u.id = p.utilisateur_id " .
      "WHERE p.utilisateur_id = ? " .
      "ORDER BY p.created_at DESC"
  );
  $stmt->execute([$profileId]);
  $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $commentsByPublication = [];
  $stmt = $pdo->prepare(
    "SELECT c.id, c.publication_id, c.utilisateur_id, c.contenu, c.created_at, u.prenom, u.nom " .
      "FROM commentaires c " .
      "JOIN utilisateurs u ON u.id = c.utilisateur_id " .
      "JOIN publications p ON p.id = c.publication_id " .
      "WHERE p.utilisateur_id = ? " .
      "ORDER BY c.created_at ASC"
  );
  $stmt->execute([$profileId]);
  $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($comments as $comment) {
    $pubId = (int) $comment['publication_id'];
    if (!isset($commentsByPublication[$pubId])) {
      $commentsByPublication[$pubId] = [];
    }
    $commentsByPublication[$pubId][] = $comment;
  }
}

$flashSuccess = '';
if (isset($_SESSION['flash_success'])) {
  $flashSuccess = $_SESSION['flash_success'];
  unset($_SESSION['flash_success']);
}
$flashError = '';
if (isset($_SESSION['flash_error'])) {
  $flashError = $_SESSION['flash_error'];
  unset($_SESSION['flash_error']);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trombinoscope — Profil <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></title>
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
      <?php if ($isLoggedIn): ?>
        <li><a href="profil.php?id=<?php echo (int) $_SESSION['user_id']; ?>">Mon profil</a></li>
        <li><a href="logout.php" class="btn-nav">Deconnexion</a></li>
      <?php else: ?>
        <li><a href="register.php">Inscription</a></li>
        <li><a href="login.php" class="btn-nav">Connexion</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="container">

    <?php if ($flashSuccess !== ''): ?>
      <div class="flash flash-success">
        <?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
      <div class="flash flash-error">
        <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php
    $debug = '';
    if (isset($_SESSION['debug'])) {
      $debug = $_SESSION['debug'];
      unset($_SESSION['debug']);
    }
    if ($debug !== ''): ?>
      <div class="flash">
        Debug: <?php echo htmlspecialchars($debug, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="profile-header">
      <img
        class="profile-avatar"
        src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>"
        alt="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="profile-info">
        <h1><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($specialite !== '' || $promo !== ''): ?>
          <div class="role">
            <?php echo htmlspecialchars(trim($specialite . ' — ' . $promo), ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>
        <div class="bio"><?php echo nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8')); ?></div>
      </div>
      <div class="profile-actions">
        <?php if ($isOwnProfile): ?>
          <a href="edit-profil.php" class="btn btn-secondary btn-sm">Modifier le profil</a>
          <a href="logout.php" class="btn btn-danger btn-sm">Deconnexion</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="section-title">Publications</div>

    <?php if ($isOwnProfile): ?>
      <div class="form-card form-card-post">
        <form action="post.php" method="POST">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <textarea name="contenu" placeholder="Partagez quelque chose avec la promo..." rows="3"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-inline">Publier</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="post-list">
      <?php if (empty($publications)): ?>
        <div class="flash">
          Aucune publication pour le moment.
        </div>
      <?php endif; ?>

      <?php foreach ($publications as $publication): ?>
        <?php
        $pubId = (int) $publication['id'];
        $authorName = trim(($publication['prenom'] ?? '') . ' ' . ($publication['nom'] ?? ''));
        $isOwner = $isLoggedIn && ((int) $_SESSION['user_id'] === (int) $publication['utilisateur_id']);
        $pubComments = $commentsByPublication[$pubId] ?? [];
        ?>
        <div class="post-card">
          <div class="post-meta">
            <?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($isOwner): ?>
              <span class="badge-owner">Vous</span>
            <?php endif; ?>
            — <?php echo htmlspecialchars($publication['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <div class="post-content">
            <?php echo nl2br(htmlspecialchars($publication['contenu'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
          </div>
          <?php if ($isOwner): ?>
            <div class="post-actions">
              <a href="edit-post.php?id=<?php echo $pubId; ?>" class="btn btn-secondary btn-sm">Modifier</a>
              <form action="delete-post.php" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette publication ?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $pubId; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
              </form>
            </div>
          <?php endif; ?>

          <div class="comment-list">
            <?php foreach ($pubComments as $comment): ?>
              <?php
              $commentAuthor = trim(($comment['prenom'] ?? '') . ' ' . ($comment['nom'] ?? ''));
              $commentLink = 'profil.php?id=' . (int) ($comment['utilisateur_id'] ?? 0);
              if (isset($comment['fake_key']) && $comment['fake_key'] !== '') {
                $commentLink = 'profil.php?fake=' . urlencode($comment['fake_key']);
              }
              ?>
              <div class="comment">
                <div class="comment-author">
                  <a href="<?php echo htmlspecialchars($commentLink, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($commentAuthor, ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </div>
                <div class="comment-text">
                  <?php echo htmlspecialchars($comment['contenu'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($isLoggedIn): ?>
            <form action="comment.php" method="POST" class="comment-form">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="post_id" value="<?php echo $pubId; ?>">
              <input type="text" name="contenu" placeholder="Ajouter un commentaire..." required>
              <button type="submit">Envoyer</button>
            </form>
          <?php endif; ?>
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
