<?php

/**
 * Página de configuração do plugin (somente perfil com direito "config").
 * Rota protegida pela estratégia padrão do firewall (autenticado).
 */

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Googlesso\Config;
use GlpiPlugin\Googlesso\Provider;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
    Config::saveConfig($_POST);
    Session::addMessageAfterRedirect(htmlescape(__('Configuration updated.', 'googlesso')), false, INFO);
    Html::back();
}

Html::header(__('Google SSO', 'googlesso'), '', 'config', 'plugins');

global $DB;

$profiles = [];
foreach ($DB->request('glpi_profiles') as $p) {
    $profiles[$p['id']] = $p['name'];
}
asort($profiles);

$entities = [];
foreach ($DB->request('glpi_entities') as $e) {
    $entities[$e['id']] = $e['completename'];
}
asort($entities);

TemplateRenderer::getInstance()->display('@googlesso/config.html.twig', [
    'config'       => Config::getConfig(),
    'redirect_uri' => Provider::getRedirectUri(),
    'action'       => $CFG_GLPI['root_doc'] . '/plugins/googlesso/front/config.form.php',
    'profiles'     => $profiles,
    'entities'     => $entities,
]);

Html::footer();
