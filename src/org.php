<?php
declare(strict_types=1);

/** Vereinsname für Kopfzeile, Mails und Fußzeile (aus der config.php, unabhängig von MISS). */
function ka_org_name(): string
{
    return defined('KA_ORG_NAME') && trim((string)KA_ORG_NAME) !== '' ? trim((string)KA_ORG_NAME) : 'Verein';
}

/** Logo: KA_ORG_LOGO oder das Standardwappen (logo.php) mit dem Anfangsbuchstaben des Vereins. */
function ka_logo_html(): string
{
    $logo = defined('KA_ORG_LOGO') ? trim((string)KA_ORG_LOGO) : '';
    $src = $logo !== '' ? $logo : ka_url('logo.php?i=' . rawurlencode(mb_strtoupper(mb_substr(ka_org_name(), 0, 1))));
    return '<img class="logo" src="' . h($src) . '" alt="" width="36" height="36">';
}

function ka_footer_setting(string $const): string
{
    return defined($const) ? trim((string)constant($const)) : '';
}
