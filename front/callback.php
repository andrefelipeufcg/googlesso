<?php

/**
 * Callback OAuth2: recebe o "code" do Google, valida o state, obtém o
 * perfil do usuário e delega a autenticação ao Authenticator.
 *
 * GLPI 11: sem include de inc/includes.php — o kernel faz o bootstrap.
 * Acesso anônimo liberado via Firewall::STRATEGY_NO_CHECK no setup.php.
 */

use GlpiPlugin\Googlesso\Authenticator;
use GlpiPlugin\Googlesso\Provider;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

// Já autenticado (ex.: callback aberto duas vezes): segue para a home
if (Session::getLoginUserID() !== false) {
    Html::redirect($CFG_GLPI['url_base'] . '/front/central.php');
}

$abort = static function (string $message) use ($CFG_GLPI): void {
    Toolbox::logInFile('googlesso', $message . "\n");
    Session::addMessageAfterRedirect(htmlescape($message), false, ERROR);
    Html::redirect($CFG_GLPI['url_base'] . '/index.php');
};

// Usuário cancelou o consentimento ou o Google retornou erro
if (isset($_GET['error'])) {
    $abort(sprintf(__('Google login cancelled: %s', 'googlesso'), $_GET['error']));
}

// Validação do state (anti-CSRF). Consome o valor da sessão em qualquer caso.
$state    = (string) ($_GET['state'] ?? '');
$expected = (string) ($_SESSION['plugin_googlesso_oauth2_state'] ?? '');
unset($_SESSION['plugin_googlesso_oauth2_state']);

if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
    $abort(__('Invalid OAuth2 state. Try again from the login page.', 'googlesso'));
}

if (empty($_GET['code'])) {
    $abort(__('Google response without authorization code.', 'googlesso'));
}

try {
    $provider = Provider::create();

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
    ]);

    /** @var \League\OAuth2\Client\Provider\GoogleUser $owner */
    $owner = $provider->getResourceOwner($token);

    // Autentica (ou cria) o usuário e redireciona; não retorna em sucesso.
    Authenticator::login($owner);
} catch (\Throwable $e) {
    // No GLPI 11, os redirecionamentos (Html::redirect) funcionam lançando a RedirectException.
    // Nós não podemos engolir essa exceção, ela deve subir para o router do GLPI executar o redirect.
    if (is_a($e, 'Glpi\\Exception\\RedirectException') || str_contains(get_class($e), 'RedirectException')) {
        throw $e;
    }

    $errorMessage = sprintf('%s (File: %s, Line: %d)', $e->getMessage(), $e->getFile(), $e->getLine());
    Toolbox::logInFile('googlesso_errors', "Exceção: " . get_class($e) . "\nMensagem: " . $errorMessage . "\nStack Trace:\n" . $e->getTraceAsString() . "\n");
    
    $abort(sprintf(__('Google authentication failed: %s', 'googlesso'), $errorMessage));
}
