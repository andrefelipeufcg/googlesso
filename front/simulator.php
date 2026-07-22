<?php

/**
 * Simulator of Account Creation Rules for Google SSO
 *
 * Simulates the complete flow to demonstrate exactly
 * what would happen in a real login.
 *
 * @license GPLv3+
 */

include_once '../../../inc/includes.php';

use GlpiPlugin\Googlesso\Config;
use Glpi\Application\View\TemplateRenderer;

global $CFG_GLPI, $DB;

// Verifica direitos
Session::checkRight('config', UPDATE);

Html::header(
    __('Rules Simulator', 'googlesso'),
    $_SERVER['PHP_SELF'],
    'config',
    'plugin'
);

$email = trim((string)($_POST['email'] ?? ''));
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

$result = null;

if ($isPost && $email !== '') {
    $timeline = []; // Array de etapas detalhadas
    $finalStatus = 'success';
    $finalMessage = '';
    $details = [];
    
    // --- Configuração Atual do Plugin ---
    $config = Config::getConfig();
    $restrictDomain = (string) $config['restrict_domain'];
    $autoCreate = $config['auto_create_users'];
    
    $timeline[] = [
        'step' => __('Configuration Read', 'googlesso'),
        'source' => 'Plugin',
        'icon' => '⚙️',
        'desc' => sprintf(
            __('Restrict domain: <strong>%s</strong> | Auto create users: <strong>%s</strong>', 'googlesso'),
            $restrictDomain !== '' ? htmlspecialchars($restrictDomain) : __('None', 'googlesso'),
            $autoCreate ? __('Yes', 'googlesso') : __('No', 'googlesso')
        )
    ];
    
    // Security Note: Atraso artificial para mascarar o tempo de resposta do banco e prevenir 
    // ataques de enumeração baseados em tempo (timing attacks) caso o simulador seja exposto.
    usleep(random_int(100000, 300000));

    // --- Etapa 1: Validação de E-mail ---
    $timeline[] = [
        'step' => __('Email Validation', 'googlesso'),
        'source' => 'Plugin',
        'icon' => '✅',
        'desc' => sprintf(__('Verified email present: <strong>%s</strong>', 'googlesso'), htmlspecialchars($email))
    ];

    // --- Etapa 2: Validação de Domínio Restrito ---
    $domainAllowed = true;
    if ($restrictDomain !== '') {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if ($domain !== $restrictDomain && !str_ends_with($domain, '.' . $restrictDomain)) {
            $domainAllowed = false;
            $timeline[] = [
                'step' => __('Domain Restriction Check', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '🚫',
                'desc' => sprintf(__('The email domain "<strong>%s</strong>" is not allowed. The plugin restricts access to the domain "<strong>%s</strong>" or its subdomains.', 'googlesso'), htmlspecialchars($domain), htmlspecialchars($restrictDomain))
            ];
            $finalStatus = 'error';
            $finalMessage = sprintf(__('Login blocked: Only accounts from the %s domain are accepted.', 'googlesso'), $restrictDomain);
        } else {
            $timeline[] = [
                'step' => __('Domain Restriction Check', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '✅',
                'desc' => sprintf(__('The email domain "<strong>%s</strong>" is allowed by the restriction rule.', 'googlesso'), htmlspecialchars($domain))
            ];
        }
    }

    if ($domainAllowed) {
        // --- Etapa 3: Busca do Usuário ---
        $user = new User();
        $found = false;
        
        if ($user->getFromDBbyEmail($email)) {
            $found = true;
            $timeline[] = [
                'step' => __('Database Search', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '🔍',
                'desc' => sprintf(__('User found by email: <strong>%s</strong> (ID: %d)', 'googlesso'), htmlspecialchars($email), $user->fields['id'])
            ];
        } else {
            $timeline[] = [
                'step' => __('Database Search', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '🔍',
                'desc' => sprintf(__('No user found with the email <strong>%s</strong>', 'googlesso'), htmlspecialchars($email))
            ];
        }
        
        // --- Etapa 4: Decisão de criação ou login ---
        if ($found) {
            $timeline[] = [
                'step' => __('Login to Existing Account', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '🔑',
                'desc' => sprintf(__('The system would log into the existing account <strong>%s</strong> (ID: %d). No new account would be created.', 'googlesso'), htmlspecialchars($user->fields['name']), $user->fields['id'])
            ];
            $finalStatus = 'success';
            $finalMessage = sprintf(__('User "%s" already exists. Login would be performed successfully.', 'googlesso'), htmlspecialchars($email));
        } elseif (!$autoCreate) {
            $timeline[] = [
                'step' => __('Auto Creation Disabled', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '⚠️',
                'desc' => __('The "Automatically create users" configuration is <strong>DISABLED</strong>. The login would be blocked.', 'googlesso')
            ];
            $finalStatus = 'warning';
            $finalMessage = sprintf(__('The user "%s" DOES NOT exist and auto-creation is disabled.', 'googlesso'), htmlspecialchars($email));
        } else {
            // --- Fluxo de Criação ---
            
            // Regras de Domínio
            $domain = strtolower(substr(strrchr($email, '@'), 1));
            
            $profile_id = 0;
            $entity_id  = 0;
            $ruleApplied = __('Default Rule (Fallback)', 'googlesso');
            
            foreach ($config['domain_rules'] as $rule) {
                if ($domain === $rule['domain'] || str_ends_with($domain, '.' . $rule['domain'])) {
                    $profile_id = (int)$rule['profile_id'];
                    $entity_id  = (int)$rule['entity_id'];
                    $ruleApplied = sprintf(__('Domain Rule for "%s"', 'googlesso'), $rule['domain']);
                    break;
                }
            }
            
            if ($profile_id === 0) {
                $profile_id = (int)$config['default_profile_id'];
                $entity_id  = (int)$config['default_entity_id'];
            }
            
            $timeline[] = [
                'step' => __('Domain Rules', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '📋',
                'desc' => sprintf(
                    __('Extracted domain: <strong>@%s</strong>. Applied rule: <strong>%s</strong>', 'googlesso'),
                    htmlspecialchars($domain),
                    $ruleApplied
                )
            ];
            
            $pluginProfileName = $profile_id > 0 ? Dropdown::getDropdownName('glpi_profiles', $profile_id) : __('(none)', 'googlesso');
            $pluginEntityName  = Dropdown::getDropdownName('glpi_entities', $entity_id);
            
            $timeline[] = [
                'step' => __('Profile Determined by Plugin', 'googlesso'),
                'source' => 'Plugin',
                'icon' => '🎯',
                'desc' => sprintf(
                    __('Profile: <strong>%s</strong> (ID: %d) | Entity: <strong>%s</strong> (ID: %d)', 'googlesso'),
                    htmlspecialchars($pluginProfileName), $profile_id,
                    htmlspecialchars($pluginEntityName), $entity_id
                )
            ];
            
            // Simulação do comportamento do Core (User::add)
            $timeline[] = [
                'step' => __('User::add() called', 'googlesso'),
                'source' => 'GLPI Core',
                'icon' => '🏗️',
                'desc' => sprintf(
                    __('The plugin calls <code>User::add()</code> to create the user "<strong>%s</strong>".', 'googlesso'),
                    htmlspecialchars($email)
                )
            ];

            $timeline[] = [
                'step' => __('Core Default Profile Assigned', 'googlesso'),
                'source' => 'GLPI Core',
                'icon' => '⚙️',
                'desc' => __('In GLPI 11, the core ignores the profile passed by external authentications and automatically assigns the global default profile (usually Self-Service) to the new user.', 'googlesso')
            ];
            
            if ($profile_id > 0) {
                $timeline[] = [
                    'step' => __('Profile Correction by Plugin', 'googlesso'),
                    'source' => 'Plugin',
                    'icon' => '🔧',
                    'desc' => sprintf(
                        __('The plugin removes the incorrect profiles assigned by GLPI and explicitly links the profile <strong>%s</strong> and entity <strong>%s</strong> using the <code>Profile_User</code> class.', 'googlesso'),
                        htmlspecialchars($pluginProfileName),
                        htmlspecialchars($pluginEntityName)
                    )
                ];
                
                $finalStatus = 'success';
                $finalMessage = sprintf(__('The user "%s" would be created successfully.', 'googlesso'), htmlspecialchars($email));
                $details = [
                    __('Login / Email', 'googlesso') => htmlspecialchars($email),
                    __('Final Profile', 'googlesso') => $pluginProfileName,
                    __('Final Entity', 'googlesso') => $pluginEntityName,
                    __('Rule Used', 'googlesso') => $ruleApplied,
                ];
            } else {
                $timeline[] = [
                    'step' => __('No Profile Configured', 'googlesso'),
                    'source' => 'Plugin',
                    'icon' => '⚠️',
                    'desc' => __('No valid profile (ID > 0) was found in the plugin rules. The user would be created WITHOUT a profile.', 'googlesso')
                ];
                $finalStatus = 'error';
                $finalMessage = sprintf(__('The user "%s" would be created, but without ANY profile. Access to GLPI would be denied. Please configure a valid profile in the Default Rule or Domain Rules.', 'googlesso'), htmlspecialchars($email));
            }
            
            // Etapa final: Session::init
            if ($finalStatus === 'success') {
                $timeline[] = [
                    'step' => __('Session::init() — Login Performed', 'googlesso'),
                    'source' => 'GLPI Core',
                    'icon' => '🔐',
                    'desc' => __('The plugin calls <code>Session::init()</code>. The GLPI core loads the user\'s profiles and starts the session. The user is redirected to the dashboard.', 'googlesso')
                ];
            }
        }
    }
    
    $result = [
        'status' => $finalStatus,
        'message' => $finalMessage,
        'details' => $details ?? [],
        'timeline' => $timeline,
    ];
}

$selfUrl = $CFG_GLPI['root_doc'] . '/plugins/googlesso/front/simulator.php';

$params = [
    'root_doc' => $CFG_GLPI['root_doc'],
    'selfUrl'  => $selfUrl,
    'email'    => htmlspecialchars($email),
    'result'   => $result,
];

TemplateRenderer::getInstance()->display('@googlesso/simulator.html.twig', $params);

Html::footer();
