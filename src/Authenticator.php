<?php

namespace GlpiPlugin\Googlesso;

use Auth;
use Html;
use League\OAuth2\Client\Provider\GoogleUser;
use Session;
use Toolbox;
use User;

/**
 * Converte uma identidade Google validada em uma sessão GLPI.
 *
 * Usa exclusivamente o mecanismo oficial de autenticação externa do core
 * (Auth + Session::init), o mesmo caminho usado por SSO via X.509/variável
 * de ambiente. Nenhum comportamento do login local é alterado.
 */
final class Authenticator
{
    public static function login(GoogleUser $owner): void
    {
        $config = Config::getConfig();
        $claims = $owner->toArray();

        $email = strtolower(trim((string) $owner->getEmail()));
        if ($email === '' || empty($claims['email_verified'])) {
            self::fail(__('A conta Google não possui e-mail verificado.', 'googlesso'));
        }

        // Validação autoritativa do domínio (o parâmetro "hd" enviado ao
        // Google é apenas cosmético e pode ser contornado pelo usuário).
        if ($config['restrict_domain'] !== '') {
            $domain = strtolower(substr(strrchr($email, '@'), 1));
            if ($domain !== $config['restrict_domain']) {
                self::fail(sprintf(
                    __('Apenas contas do domínio %s são aceitas.', 'googlesso'),
                    $config['restrict_domain']
                ));
            }
        }

        $user = new User();
        if (!$user->getFromDBbyEmail($email)) {
            if (!$config['auto_create_users']) {
                self::fail(__('Nenhum usuário GLPI corresponde a esta conta Google.', 'googlesso'));
            }
            $user = self::createUser($email, $owner, $config);
        }

        if ((int) $user->fields['is_deleted'] === 1 || (int) $user->fields['is_active'] !== 1) {
            self::fail(__('Este usuário está desativado no GLPI.', 'googlesso'));
        }

        // Autenticação externa padrão do GLPI: marca extauth e delega ao
        // core a construção da sessão (perfis, entidades, CSRF, cookies).
        $auth                = new Auth();
        $auth->auth_succeded = true;
        $auth->user_present  = true;
        $auth->extauth       = 1;
        $auth->user          = $user;
        $auth->user->fields['authtype'] = Auth::EXTERNAL;

        Session::init($auth);

        if (Session::getLoginUserID() === false) {
            self::fail(__('Sessão não inicializada: o usuário não possui perfil habilitado.', 'googlesso'));
        }

        Toolbox::logInFile('googlesso', sprintf("Login Google OK: %s\n", $email));

        // Redireciona para central ou helpdesk conforme o perfil ativo
        Auth::redirectIfAuthenticated();
    }

    private static function createUser(string $email, GoogleUser $owner, array $config): User
    {
        $user  = new User();
        $input = [
            'name'        => $email,
            'realname'    => (string) ($owner->getLastName() ?? ''),
            'firstname'   => (string) ($owner->getFirstName() ?? ''),
            'authtype'    => Auth::EXTERNAL,
            'is_active'   => 1,
            '_useremails' => [-1 => $email],
        ];

        $domain = strtolower(substr(strrchr($email, '@'), 1));
        
        $profile_id = 0;
        $entity_id  = 0;
        
        foreach ($config['domain_rules'] as $rule) {
            if (str_ends_with($domain, $rule['domain'])) {
                $profile_id = $rule['profile_id'];
                $entity_id  = $rule['entity_id'];
                break;
            }
        }
        
        // Se não achou na regra específica, usa a regra padrão (ELSE)
        if ($profile_id === 0) {
            $profile_id = $config['default_profile_id'];
            $entity_id  = $config['default_entity_id'];
        }

        if ($profile_id > 0) {
            $input['_profiles_id']  = $profile_id;
            $input['_entities_id']  = $entity_id;
            $input['_is_recursive'] = 1;
        }

        $users_id = $user->add($input);
        if (!$users_id) {
            self::fail(__('Falha ao criar o usuário no GLPI.', 'googlesso'));
        }

        $user->getFromDB($users_id);
        return $user;
    }

    private static function fail(string $message): void
    {
        global $CFG_GLPI;

        Toolbox::logInFile('googlesso', $message . "\n");
        Session::addMessageAfterRedirect(htmlescape($message), false, ERROR);

        // Volta para a página de login nativa; nunca derruba a sessão de
        // outra aba nem interfere no formulário local.
        Html::redirect($CFG_GLPI['url_base'] . '/index.php');
    }
}
