<?php

/**
 * Account Creation Consent Screen
 * Displayed only when auto-create is enabled and the account does not exist.
 *
 * @license GPLv3+
 */

use GlpiPlugin\Googlesso\Authenticator;
use Glpi\Application\View\TemplateRenderer;

include(__DIR__ . '/../../../inc/includes.php');
global $CFG_GLPI;

// Check if there are pending claims in the session
if (!isset($_SESSION['googlesso_pending_claims'])) {
    // No pending claims: go back to login cleanly
    echo "<!DOCTYPE html><html><head><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($CFG_GLPI['root_doc']) . "/index.php?noAUTO=1'></head><body></body></html>";
    die();
}
$claims = $_SESSION['googlesso_pending_claims'];

// Confirmation Processing
if (isset($_GET['confirm'])) {
    Toolbox::logInFile('googlesso', "[CONSENT] Action received. confirm=" . ($_GET['confirm'] ?? 'N/A') . " csrf_present=" . (isset($_GET['googlesso_custom_csrf']) ? 'yes' : 'no') . "\n");
    
    // Custom CSRF Validation (Bypass for anonymous GLPI session)
    $reqCsrf = $_GET['googlesso_custom_csrf'] ?? '';
    if (!isset($_SESSION['googlesso_custom_csrf']) || $reqCsrf === '' || !hash_equals($_SESSION['googlesso_custom_csrf'], $reqCsrf)) {
        Toolbox::logInFile('googlesso', "[CONSENT] Custom CSRF validation failed.\n");
        echo "<!DOCTYPE html><html><body><h2>Security Error (CSRF)</h2><p>Your request has expired or is invalid. Please return to the login page.</p></body></html>";
        die();
    }

    Toolbox::logInFile('googlesso', "[CONSENT] Starting user creation...\n");
    
    // Try to create and log in the user with forceCreate = true
    try {
        Authenticator::login($claims, true);
    } catch (\Throwable $e) {
        // In GLPI 11, Html::redirect() throws RedirectException
        if (is_a($e, 'Glpi\Exception\RedirectException') || str_contains(get_class($e), 'RedirectException')) {
            unset($_SESSION['googlesso_pending_claims']);
            throw $e;
        }
        
        Toolbox::logInFile('googlesso', "[CONSENT] Exception in Authenticator::login: " . get_class($e) . " - " . $e->getMessage() . "\n");
        
        // Clean temporary session
        unset($_SESSION['googlesso_pending_claims']);
        
        $email = $claims['email'] ?? '';
        Toolbox::logInFile('googlesso', 'Failed to create account (Email: ' . $email . '): ' . $e->getMessage() . "\n");

        // Pure HTML to avoid 401 from Html::nullHeader()
        $title = __('Error Creating Account', 'googlesso');
        $backText = __('Back to Login', 'googlesso');
        $backUrl = htmlspecialchars($CFG_GLPI['root_doc']) . '/index.php?noAUTO=1';

        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>" . htmlspecialchars($title) . "</title></head>";
        echo "<body style='background:#f4f6f8; font-family:sans-serif; margin:0; padding:0;'>";
        echo "<div style='display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px;'>";
        echo "<div style='background:#fff; border-left:5px solid #d9534f; padding:40px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); max-width:500px; width:100%;'>";
        echo "<h2 style='color:#d9534f; margin-top:0;'>" . htmlspecialchars($title) . "</h2>";
        echo "<p style='font-size:16px; color:#444; line-height:1.6; margin-bottom:30px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='" . $backUrl . "' style='display:inline-block; padding:12px 24px; background:#1a73e8; color:#fff; text-decoration:none; border-radius:6px; font-weight:600;'>" . htmlspecialchars($backText) . "</a>";
        echo "</div></div></body></html>";
        die();
    }
}

// Preparation for displaying the screen
$name = trim((string) ($claims['name'] ?? ''));

$primaryEmail = $claims['email'] ?? '';

// Mask/Anonymize the email (e.g., and***@gmail.com)
$maskedEmail = 'not informed';
if ($primaryEmail !== '') {
    $parts = explode('@', $primaryEmail);
    if (count($parts) === 2) {
        $userPart = $parts[0];
        $domain = $parts[1];
        if (strlen($userPart) > 3) {
            $maskedEmail = substr($userPart, 0, 3) . str_repeat('*', strlen($userPart) - 3) . '@' . $domain;
        } else {
            $maskedEmail = substr($userPart, 0, 1) . str_repeat('*', strlen($userPart) - 1) . '@' . $domain;
        }
    }
}

Html::nullHeader(__('Account Creation Confirmation', 'googlesso'), $CFG_GLPI['root_doc'] . '/index.php');

if (!isset($_SESSION['googlesso_custom_csrf'])) {
    $_SESSION['googlesso_custom_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['googlesso_custom_csrf'];

$params = [
    'root_doc'   => $CFG_GLPI['root_doc'],
    'selfUrl'    => $CFG_GLPI['root_doc'] . '/plugins/googlesso/front/consent.php',
    'csrf'       => $csrf,
    'name'       => $name,
    'email'      => $maskedEmail
];

TemplateRenderer::getInstance()->display('@googlesso/consent.html.twig', $params);

Html::nullFooter();
