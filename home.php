<?php
session_start();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function is_logged_in(): bool {
  return isset($_SESSION['user_id']) || isset($_SESSION['email']) || isset($_SESSION['uid']) || isset($_SESSION['user']);
}
$loggedIn = is_logged_in();

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/' || $base === '.') $base = '';
if ($base !== '' && $base[0] !== '/') $base = '/' . $base;

// routes (adjust if your repo differs)
$homeUrl     = $base . '/home.php';
$loginUrl    = $base . '/login.php';
$registerUrl = $base . '/register.php';
$userHomeUrl = $base . '/userhome.php';
$logoutUrl   = $base . '/log.php?logout=1';

$joinProjectUrl = '#projects';
$patternsUrl    = $base . '/explore/index.php'; // ok if placeholder

$projects = [
  [
    'title' => 'Liberating Voices',
    'desc'  => 'Empowering marginalized communities through participatory design and collaborative storytelling patterns that amplify unheard perspectives.',
    'href'  => $base . '/projects/liberating_voices.php',
    'ph'    => 'Liberating Voices Project Image',
  ],
  [
    'title' => 'Limits Within Computing',
    'desc'  => 'Exploring sustainable computing practices and patterns for responsible technology development within planetary boundaries.',
    'href'  => '#',
    'ph'    => 'Limits Within Computing Image',
  ],
  [
    'title' => 'Post-growth HCI',
    'desc'  => 'Reimagining human-computer interaction beyond growth paradigms, focusing on wellbeing, equity, and ecological sustainability.',
    'href'  => '#',
    'ph'    => 'Post-growth HCI Image',
  ],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pattern Sphere</title>

  <link rel="stylesheet" href="<?=h($base)?>/assets/css/home.css">
  <script defer>
    // UI-only search for now
    document.addEventListener("DOMContentLoaded", () => {
      const form = document.querySelector(".topsearch");
      if (!form) return;
      form.addEventListener("submit", (e) => {
        e.preventDefault();
      });
    });
  </script>
</head>

<body class="page">

<header class="topbar">
  <div class="container topbar__inner">
    <a class="brand" href="<?=h($homeUrl)?>">
      <span class="brand__title">Pattern<br>Sphere</span>
    </a>

    <nav class="nav" aria-label="Primary">
      <a class="nav__link" href="#">Labs</a>
      <a class="nav__link" href="<?=h($userHomeUrl)?>">MyPS</a>
      <a class="nav__link" href="#">Messages</a>
      <a class="nav__link" href="#projects">Projects</a>
      <a class="nav__link" href="<?=h($patternsUrl)?>">Patterns</a>
      <a class="nav__link" href="#">Resources</a>
      <a class="nav__link" href="#">About</a>
      <a class="nav__link nav__link--em" href="#">Get Involved</a>
    </nav>

    <form class="topsearch" action="#" method="get" role="search" aria-label="Search patterns and projects">
      <input class="topsearch__input" type="search" name="q" placeholder="Search patterns, projects..." autocomplete="off">
      <button class="topsearch__btn" type="submit" aria-label="Search">
        <span class="icon icon--search" aria-hidden="true"></span>
      </button>
    </form>

    <div class="auth">
      <?php if ($loggedIn): ?>
        <a class="btn btn--ghost btn--sm" href="<?=h($userHomeUrl)?>">Dashboard</a>
        <a class="btn btn--solid btn--sm" href="<?=h($logoutUrl)?>">Log Out</a>
      <?php else: ?>
        <a class="btn btn--ghost btn--sm" href="<?=h($registerUrl)?>">Register</a>
        <a class="btn btn--solid btn--sm" href="<?=h($loginUrl)?>">Log In</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main>

  <!-- HERO -->
  <section class="hero">
    <div class="container hero__grid">
      <div class="hero__left">
        <h1 class="hero__title">Collaborative tools to solve<br>complex social and<br>environmental problems.</h1>

        <p class="hero__lead">
          Join a global community using patterns to create lasting change.<br>
          Discover, adapt, and contribute to patterns that work.
        </p>

        <div class="hero__actions">
          <a class="btn btn--solid" href="<?=h($joinProjectUrl)?>">Join a Project</a>
          <a class="btn btn--ghost" href="<?=h($patternsUrl)?>">Patterns</a>
        </div>
      </div>

      <div class="hero__right">
        <div class="heroCard">
          <div class="heroCard__caption">
            Abstract Network Visualization: Pattern Language Interconnections
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="rule"></div>

  <!-- EXPLORE PATTERN TYPES -->
  <section class="section">
    <div class="container">
      <h2 class="section__title">Explore Pattern Types</h2>
      <p class="section__sub">
        Discover eight key pattern categories that form the foundation of collaborative<br>
        problem-solving.
      </p>

      <div class="collageCard">

        <div class="collageCard__title">Visual Collage: 8 Key Pattern Types</div>

        <div class="collageCard__desc">
          Interconnected visual representation showing Innovation, Governance, Community Building, Resource<br>
          Management, Communication, Learning Systems, Environmental Design, and Social Infrastructure patterns.
        </div>

        <a class="btn btn--solid" href="<?=h($patternsUrl)?>">Explore All Patterns</a>
      </div>
    </div>
  </section>

  <div class="rule"></div>

  <!-- WHY PATTERNS MATTER -->
  <section class="section">
    <div class="container">
      <h2 class="section__title">Why Patterns Matter</h2>

      <div class="grid2">
        <article class="card quote">
          <div class="quote__mark">“</div>
          <p class="quote__text">
            “Each pattern describes a problem which occurs over and over again in our environment, and then describes the core of the solution
            to that problem, in such a way that you can use this solution a million times over, without ever doing it the same way twice.”
          </p>
          <div class="quote__by">— Christopher Alexander</div>
        </article>

        <article class="card quote">
          <div class="quote__mark">“</div>
          <p class="quote__text">
            “No pattern is an isolated entity. Each pattern can exist in the world only to the extent that it is supported by other patterns:
            the larger patterns in which it is embedded, the patterns of the same size that surround it, and the smaller patterns which are embedded in it.”
          </p>
          <div class="quote__by">— Christopher Alexander</div>
        </article>

        <article class="card">
          <h3 class="card__title">Proven Approaches, Not Solutions</h3>
          <p class="card__text">
            Patterns are time-tested approaches that can be adapted to unique contexts. They provide frameworks for understanding complex problems and
            creating contextual responses that work within specific communities and environments.
          </p>
        </article>

        <article class="card icons">
          <div class="icons__row">
            <div class="icons__item">
              <span class="iconSvg" aria-hidden="true">
                <!-- Adaptation -->
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-puzzle-fill" viewBox="0 0 16 16">
                  <path d="M3.112 3.645A1.5 1.5 0 0 1 4.605 2H7a.5.5 0 0 1 .5.5v.382c0 .696-.497 1.182-.872 1.469a.5.5 0 0 0-.115.118l-.012.025L6.5 4.5v.003l.003.01q.005.015.036.053a.9.9 0 0 0 .27.194C7.09 4.9 7.51 5 8 5c.492 0 .912-.1 1.19-.24a.9.9 0 0 0 .271-.194.2.2 0 0 0 .036-.054l.003-.01v-.008l-.012-.025a.5.5 0 0 0-.115-.118c-.375-.287-.872-.773-.872-1.469V2.5A.5.5 0 0 1 9 2h2.395a1.5 1.5 0 0 1 1.493 1.645L12.645 6.5h.237c.195 0 .42-.147.675-.48.21-.274.528-.52.943-.52.568 0 .947.447 1.154.862C15.877 6.807 16 7.387 16 8s-.123 1.193-.346 1.638c-.207.415-.586.862-1.154.862-.415 0-.733-.246-.943-.52-.255-.333-.48-.48-.675-.48h-.237l.243 2.855A1.5 1.5 0 0 1 11.395 14H9a.5.5 0 0 1-.5-.5v-.382c0-.696.497-1.182.872-1.469a.5.5 0 0 0 .115-.118l.012-.025.001-.006v-.003l-.003-.01a.2.2 0 0 0-.036-.053.9.9 0 0 0-.27-.194C8.91 11.1 8.49 11 8 11s-.912.1-1.19.24a.9.9 0 0 0-.271.194.2.2 0 0 0-.036.054l-.003.01v.002l.001.006.012.025c.016.027.05.068.115.118.375.287.872.773.872 1.469v.382a.5.5 0 0 1-.5.5H4.605a1.5 1.5 0 0 1-1.493-1.645L3.356 9.5h-.238c-.195 0-.42.147-.675.48-.21.274-.528.52-.943.52-.568 0-.947-.447-1.154-.862C.123 9.193 0 8.613 0 8s.123-1.193.346-1.638C.553 5.947.932 5.5 1.5 5.5c.415 0 .733.246.943.52.255.333.48.48.675.48h.238z"/>
                </svg>
              </span>
              <div class="icons__label">Adaptation</div>
            </div>

            <div class="icons__item">
              <span class="iconSvg" aria-hidden="true">
                <!-- Growth -->
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-stars" viewBox="0 0 16 16">
                  <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.73 1.73 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
                </svg>
              </span>
              <div class="icons__label">Growth</div>
            </div>

            <div class="icons__item">
              <span class="iconSvg" aria-hidden="true">
                <!-- Connection -->
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-share-fill" viewBox="0 0 16 16">
                  <path d="M11 2.5a2.5 2.5 0 1 1 .603 1.628l-6.718 3.12a2.5 2.5 0 0 1 0 1.504l6.718 3.12a2.5 2.5 0 1 1-.488.876l-6.718-3.12a2.5 2.5 0 1 1 0-3.256l6.718-3.12A2.5 2.5 0 0 1 11 2.5"/>
                </svg>
              </span>
              <div class="icons__label">Connection</div>
            </div>
          </div>
          <div class="icons__caption">Patterns evolve through collaborative refinement</div>
        </article>
      </div>
    </div>
  </section>

  <div class="rule"></div>

  <!-- FEATURED PROJECTS -->
  <section class="section" id="projects">
    <div class="container">
      <h2 class="section__title">Featured Projects</h2>
      <p class="section__sub">Active collaborations making real-world impact</p>

      <div class="projects">
        <?php foreach ($projects as $p): ?>
          <article class="project">
            <div class="project__ph"><?=h($p['ph'])?></div>
            <div class="project__body">
              <h3 class="project__title"><?=h($p['title'])?></h3>
              <p class="project__desc"><?=h($p['desc'])?></p>
              <a class="btn btn--ghost btn--full" href="<?=h($p['href'])?>">Explore Project</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA BAND (black) -->
  <section class="cta">
    <div class="container cta__inner">
      <h2 class="cta__title">Find, Create, and Use Patterns</h2>
      <p class="cta__sub">
        Join a global community of changemakers working together to address complex challenges<br>
        through collaborative pattern languages.
      </p>
      <a class="btn btn--solid btn--light" href="<?= $loggedIn ? h($userHomeUrl) : h($registerUrl) ?>">Get Started Today</a>
    </div>
  </section>

</main>

<footer class="footer">
  <div class="container footer__grid">
    <div class="footer__brand">
      <div class="footer__head">Pattern Sphere</div>
      <div class="footer__text">Collaborative tools for social and<br>environmental change.</div>
    </div>

    <div class="footer__col">
      <div class="footer__head">Platform</div>
      <a class="footer__link" href="#">About</a>
      <a class="footer__link" href="#">Contact</a>
      <a class="footer__link" href="#">Accessibility</a>
    </div>

    <div class="footer__col">
      <div class="footer__head">Legal</div>
      <a class="footer__link" href="#">Privacy Policy</a>
      <a class="footer__link" href="#">Terms of Service</a>
    </div>

    <div class="footer__col">
      <div class="footer__head">Connect</div>
        <div class="footer__icons">
            <a class="footer__icon" href="#" aria-label="LinkedIn">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
                <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/>
              </svg>
            </a>

            <a class="footer__icon" href="#" aria-label="GitHub">
              </svg>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-github" viewBox="0 0 16 16">
                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8"/>
              </svg>
            </a>
          </div>
      </div>
    </div>


  <div class="container footer__bottom">
    <div class="footer__rule"></div>
    <div class="footer__copy">© <?=date('Y')?> Pattern Sphere. All rights reserved.</div>
  </div>
</footer>

</body>
</html>

