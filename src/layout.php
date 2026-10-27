<?php
declare(strict_types=1);

function ka_dark_mode(): bool
{
    return ($_COOKIE['darkMode'] ?? '') === 'enabled';
}

/**
 * Kopfzeile wie bei MISS, KONA und VERA: links Logo, Vereinsname und Anwendungsname, in der Mitte der Bereich (Titel der Seite), rechts die Person.
 * $active: Reiter ('candidates', 'discussion', 'mine', 'admin').
 */
function ka_header(string $title, ?array $me = null, string $active = '', ?string $area = null): void
{
    $dark = ka_dark_mode();
    $orgName = ka_org_name();
    $nav = [];
    $election = ka_current_election();
    if ($me && $election) {
        $nav['candidates'] = ['index.php', 'Kandidaten'];
        $nav['discussion'] = ['discussion.php', 'Diskussion'];
        if (ka_candidate_of_member((int)$election['election_id'], (int)$me['member_id'])) $nav['mine'] = ['edit.php', 'Meine Kandidatenseite'];
    }
    if ($me && ka_is_admin($me)) $nav['admin'] = ['admin/index.php', 'Verwaltung'];
    echo '<!DOCTYPE html><html lang="de"' . ($dark ? ' class="dark-mode"' : '') . '><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><base href="' . h(ka_url()) . '">';
    echo '<script>(function(){if(document.cookie.indexOf("darkMode=")<0){try{if(localStorage.getItem("darkMode")==="enabled"){document.documentElement.className="dark-mode";document.cookie="darkMode=enabled;path=/;max-age=31536000";}}catch(e){}}})();</script>';
    echo '<title>' . h($title) . ' – ' . KA_APP_NAME . ' ' . h($orgName) . '</title><link rel="stylesheet" href="assets/ka.css?v=' . (int)@filemtime(__DIR__ . '/../assets/ka.css') . '"></head><body' . ($dark ? ' class="dark-mode"' : '') . '>';
    echo '<div class="header"><div class="header-inner header-dreiteilig"><a class="header-brand" href="index.php" title="Zur Startseite">' . ka_logo_html()
        . '<span class="brand"><span class="orgname">' . h($orgName) . '</span><span class="appname"><b>' . KA_APP_NAME . '</b> · ' . KA_APP_TAG . '</span></span></a>';
    echo '<div class="header-mitte"><h1>' . h($area ?? $title) . '</h1></div><div class="user-info"><div class="user-name">';
    if ($me) {
        echo '<span class="user-id"><span>' . h(ka_member_name($me)) . '</span>' . (ka_is_admin($me) ? '<span class="role-badge">Adminteam</span>' : '') . '</span>';
        echo '<span class="user-links">' . (KA_DEMO_MODE ? '<a class="logout-btn" href="logout.php">Abmelden</a>' : '') . '</span>';
    } elseif (KA_DEMO_MODE) echo '<span>Demo-Modus</span>';
    echo '</div><button type="button" class="dark-mode-toggle" id="darkModeToggle" title="Dunkelmodus umschalten"><span class="icon">' . ($dark ? '☀️' : '🌙') . '</span></button></div></div></div>';
    if ($nav) {
        echo '<nav class="tabs">';
        foreach ($nav as $k => [$url, $label]) echo '<a class="tab' . ($k === $active ? ' active' : '') . '" href="' . $url . '">' . h($label) . '</a>';
        echo '</nav>';
    }
    echo '<main class="content">';
    if (KA_DEMO_MODE && $me) echo '<div class="demo">Demo-Modus mit erfundenen Daten.</div>';
    elseif (defined('KA_TEST_INSTANCE') && KA_TEST_INSTANCE) echo '<div class="demo"><strong>Testsystem</strong> – alle Daten sind erfunden. Bitte keine echten personenbezogenen Daten eingeben.</div>';
}

/** Reiter der Verwaltung (zweite Leiste unter den Hauptreitern). */
function ka_admin_header(string $title, array $me, string $tab): void
{
    ka_header($title, $me, 'admin', 'Verwaltung');
    $tabs = ['index' => ['index.php', 'Wahl und Termine'], 'candidates' => ['candidates.php', 'Kandidaten'], 'lists' => ['lists.php', 'Ämter, Ressorts, Fragen'], 'documents' => ['documents.php', 'Unterlagen'], 'team' => ['team.php', 'Adminteam'], 'log' => ['log.php', 'Protokoll']];
    echo '<nav class="tabs sub"><strong class="subname">Verwaltung:</strong>';
    foreach ($tabs as $k => [$u, $l]) echo '<a class="btn small' . ($k === $tab ? ' on' : '') . '" href="admin/' . $u . '">' . h($l) . '</a>';
    echo '</nav>';
    ka_show_flash();
}

function ka_footer(): void
{
    $copy = ka_footer_setting('KA_FOOTER_COPYRIGHT') ?: '&copy; ' . h(ka_org_name());
    $links = '';
    foreach ([['KA_FOOTER_IMPRESSUM_URL', 'Impressum'], ['KA_FOOTER_PRIVACY_URL', 'Datenschutz']] as [$c, $label]) {
        $u = ka_footer_setting($c);
        if ($u !== '' && preg_match('~^(https?://|/)~i', $u)) $links .= ' | <a href="' . h($u) . '" target="_blank" rel="noopener">' . $label . '</a>';
    }
    echo '</main><footer class="page-footer">' . $copy . $links . '</footer><script>' . ka_page_js() . '</script></body></html>';
}

function ka_page_js(): string
{
    return <<<'JS'
(function(){
  function openHash(){var h=location.hash.slice(1);if(!h)return;var e=document.getElementById(h);while(e){if(e.tagName==='DETAILS')e.open=true;e=e.parentElement;}var t=document.getElementById(h);if(t)t.scrollIntoView();}
  openHash();window.addEventListener('hashchange',openHash);
  document.querySelectorAll('main.content table').forEach(function(t){var r=t.rows[0];if(r&&r.cells.length>=4)t.classList.add('wide');var w=document.createElement('div');w.className='tscroll';t.parentNode.insertBefore(w,t);w.appendChild(t);});
  var dm=document.getElementById('darkModeToggle');if(dm){var ic=dm.querySelector('.icon');dm.addEventListener('click',function(){var on=document.documentElement.classList.toggle('dark-mode');document.body.classList.toggle('dark-mode',on);ic.textContent=on?'☀️':'🌙';var v=on?'enabled':'disabled';document.cookie='darkMode='+v+';path=/;max-age=31536000';try{localStorage.setItem('darkMode',v);}catch(e){}});}
  document.querySelectorAll('input[data-autosubmit]').forEach(function(i){i.addEventListener('change',function(){if(i.form)i.form.submit();});});
  document.querySelectorAll('textarea[data-count]').forEach(function(t){var o=document.getElementById(t.dataset.count);function u(){if(o)o.textContent=t.value.length+' / '+t.maxLength;}t.addEventListener('input',u);u();});
})();
JS;
}
