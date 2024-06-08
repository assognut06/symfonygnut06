<nav class="navbar navbar-expand-xxl bg-dark fixed-top" data-bs-theme="dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="?page=index"><img src="images/logo_trans-min.png" alt="Logo de Association Gnut 06" class="logoNavbar"> Gnut 06</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse fs-5" id="navbarNavDropdown">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
      <li class="nav-item">
          <a class="nav-link <?= (empty($_GET['page']) || ($_GET['page'] == "admin")) ? "active" : "" ?>" aria-current="page" href="?page=admin">Accueil</a>
        </li>
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) { ?>
          <li class="nav-item">
            <a class="nav-link" href="?page=deconnecter">Déconnexion</a>
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>