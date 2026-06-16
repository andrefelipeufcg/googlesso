<?php

namespace GlpiPlugin\Googlesso;

use Config as CoreConfig;
use GLPIKey;

/**
 * Configuração do plugin armazenada na tabela glpi_configs
 * (contexto "plugin:googlesso"). Não exige tabela própria.
 */
final class Config
{
    public const CONTEXT = 'plugin:googlesso';

    private const DEFAULTS = [
        'client_id'          => '',
        'client_secret'      => '',
        'restrict_domain'    => '',
        'auto_create_users'  => 0,
        'default_profile_id' => 0,
        'default_entity_id'  => 0,
        'domain_rules'       => '[]',
    ];

    /**
     * @return array{client_id: string, client_secret: string, restrict_domain: string,
     *               auto_create_users: int, default_profile_id: int, default_entity_id: int,
     *               domain_rules: array}
     */
    public static function getConfig(): array
    {
        $values = CoreConfig::getConfigurationValues(self::CONTEXT) + self::DEFAULTS;

        if ($values['client_secret'] !== '') {
            $values['client_secret'] = (string) (new GLPIKey())->decrypt($values['client_secret']);
        }

        $values['domain_rules'] = json_decode($values['domain_rules'] ?? '[]', true) ?: [];

        return $values;
    }

    public static function saveConfig(array $input): void
    {
        $values = [
            'client_id'          => trim($input['client_id'] ?? ''),
            'restrict_domain'    => strtolower(trim($input['restrict_domain'] ?? '')),
            'auto_create_users'  => (int) ($input['auto_create_users'] ?? 0),
            'default_profile_id' => (int) ($input['default_profile_id'] ?? 0),
            'default_entity_id'  => (int) ($input['default_entity_id'] ?? 0),
        ];

        // Campo em branco preserva o secret já gravado (cifrado com a GLPIKey)
        $secret = trim($input['client_secret'] ?? '');
        if ($secret !== '') {
            $values['client_secret'] = (new GLPIKey())->encrypt($secret);
        }

        // Extrai as regras de domínio do formulário
        $rules = [];
        if (isset($input['domain_rule_domain']) && is_array($input['domain_rule_domain'])) {
            foreach ($input['domain_rule_domain'] as $key => $domain) {
                $domain = strtolower(trim($domain));
                // Remove prefixos como @ se o admin colocar
                $domain = ltrim($domain, '@');
                
                if ($domain !== '') {
                    $rules[] = [
                        'domain'     => $domain,
                        'profile_id' => (int)($input['domain_rule_profile_id'][$key] ?? 0),
                        'entity_id'  => (int)($input['domain_rule_entity_id'][$key] ?? 0),
                    ];
                }
            }
        }
        $values['domain_rules'] = json_encode($rules);

        CoreConfig::setConfigurationValues(self::CONTEXT, $values);
    }

    public static function install(): void
    {
        CoreConfig::setConfigurationValues(self::CONTEXT, self::DEFAULTS);
    }

    public static function uninstall(): void
    {
        $config = new CoreConfig();
        $config->deleteByCriteria(['context' => self::CONTEXT]);
    }
}
