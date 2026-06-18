<?php

/**
 * Inicia o fluxo OAuth2 (Authorization Code) redirecionando para o Google.
 *
 * GLPI 11: sem include de inc/includes.php — o kernel faz o bootstrap.
 * Acesso anônimo liberado via Firewall::STRATEGY_NO_CHECK no setup.php.
 */

use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Googlesso\Config;
use GlpiPlugin\Googlesso\Provider;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

// Já autenticado: não inicia novo fluxo
if (Session::getLoginUserID() !== false) {
    Html::redirect($CFG_GLPI['url_base'] . '/front/central.php');
}

$config = Config::getConfig();
if ($config['client_id'] === '' || $config['client_secret'] === '') {
    throw new NotFoundHttpException('Google SSO is not configured.');
}

$provider = Provider::create();
$auth_options = [
    'scope' => ['openid', 'email', 'profile'],
];
if ($config['restrict_domain'] !== '') {
    $auth_options['hd'] = $config['restrict_domain'];
}

$auth_url = $provider->getAuthorizationUrl($auth_options);

// Anti-CSRF do fluxo OAuth2: o state gerado fica na sessão anônima do GLPI
// e é conferido no callback (cookie SameSite=Lax sobrevive ao redirect GET).
$_SESSION['plugin_googlesso_oauth2_state'] = $provider->getState();

Html::redirect($auth_url);
