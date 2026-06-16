<?php

namespace GlpiPlugin\Googlesso;

use League\OAuth2\Client\Provider\Google;

final class Provider
{
    public static function getRedirectUri(): string
    {
        global $CFG_GLPI;

        // GLPI 11: URL pública canônica do plugin é /plugins/<chave>/...
        return $CFG_GLPI['url_base'] . '/plugins/googlesso/front/callback.php';
    }

    public static function create(): Google
    {
        $config = Config::getConfig();

        $options = [
            'clientId'     => $config['client_id'],
            'clientSecret' => $config['client_secret'],
            'redirectUri'  => self::getRedirectUri(),
        ];

        if ($config['restrict_domain'] !== '') {
            // Filtra a tela de seleção de contas do Google (parâmetro "hd").
            // A validação definitiva do domínio é refeita no Authenticator.
            $options['hostedDomain'] = $config['restrict_domain'];
        }

        return new Google($options);
    }
}
