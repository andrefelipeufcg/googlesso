<?php

/**
 * Plugin Google SSO para GLPI 11
 *
 * Login alternativo via Google (OAuth2/OIDC). O formulário de login local
 * permanece intacto: este plugin apenas ADICIONA um botão na página de login
 * e expõe duas rotas próprias (authorize/callback).
 */

use Glpi\Http\Firewall;
use Glpi\Plugin\Hooks;

define('PLUGIN_GOOGLESSO_VERSION', '1.0.1');
define('PLUGIN_GOOGLESSO_MIN_GLPI', '11.0.0');
//define('PLUGIN_GOOGLESSO_MAX_GLPI', '11.0.99');

function plugin_init_googlesso(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['googlesso'] = true;

    // Dependências Composer do próprio plugin (league/oauth2-google)
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (is_readable($autoload)) {
        require_once $autoload;
    }

    // Injeção ADITIVA do botão "Login com Google" na página de login.
    // O template Twig do core (pages/login.html.twig) chama este hook sem
    // tocar no formulário usuário/senha nativo.
    $PLUGIN_HOOKS[Hooks::DISPLAY_LOGIN]['googlesso'] = 'plugin_googlesso_display_login';

    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['googlesso'] = 'front/config.form.php';

    // GLPI 11: por padrão o firewall exige sessão autenticada em qualquer
    // script legado de plugin. As rotas OAuth2 precisam ser anônimas,
    // senão o callback do Google é bloqueado antes de autenticar.
    Firewall::addPluginStrategyForLegacyScripts(
        'googlesso',
        '#^/front/(authorize|callback)\.php$#',
        Firewall::STRATEGY_NO_CHECK
    );
}

function plugin_version_googlesso(): array
{
    return [
        'name'         => 'Google SSO',
        'version'      => PLUGIN_GOOGLESSO_VERSION,
        'author'       => 'Daniel Ramos, Andre Felipe',
        'license'      => 'GPL v2+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_GOOGLESSO_MIN_GLPI,
                //'max' => PLUGIN_GOOGLESSO_MAX_GLPI,
            ],
            'php'  => [
                'min' => '8.2',
            ],
        ],
    ];
}

function plugin_googlesso_check_prerequisites(): bool
{
    if (!is_readable(__DIR__ . '/vendor/autoload.php')) {
        echo 'Execute "composer install --no-dev" no diretório do plugin antes de instalá-lo.';
        return false;
    }
    return true;
}
