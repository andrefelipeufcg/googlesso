<?php

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Googlesso\Config;

function plugin_googlesso_install(): bool
{
    Config::install();
    return true;
}

function plugin_googlesso_uninstall(): bool
{
    Config::uninstall();
    return true;
}

/**
 * Hook DISPLAY_LOGIN: chamado pelo template Twig da página de login do core.
 * Apenas imprime HTML adicional; o formulário nativo não é alterado.
 */
function plugin_googlesso_display_login(): void
{
    global $CFG_GLPI;

    // Sem configuração ou sem dependências: não renderiza nada e a página
    // de login permanece exatamente como o GLPI a entrega.
    if (!class_exists(\League\OAuth2\Client\Provider\Google::class)) {
        return;
    }

    $config = Config::getConfig();
    if ($config['client_id'] === '' || $config['client_secret'] === '') {
        return;
    }

    TemplateRenderer::getInstance()->display('@googlesso/login_button.html.twig', [
        'authorize_url' => $CFG_GLPI['root_doc'] . '/plugins/googlesso/front/authorize.php',
    ]);
}
