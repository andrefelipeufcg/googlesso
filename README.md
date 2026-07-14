<div align="right">
  🇬🇧 <a href="#english">English</a> | 🇪🇸 <a href="#español">Español</a> | 🇫🇷 <a href="#français">Français</a> | 🇧🇷 <a href="#português">Português</a>
</div>

<a id="english"></a>
# Google SSO for GLPI 11

Alternative login via Google (OAuth2/OpenID Connect). The local GLPI login
form remains 100% functional — the plugin only adds a "Sign in with Google" button
on the login page.

## Installation

```bash
cd <GLPI>/plugins
cp -r googlesso .
cd googlesso
composer install --no-dev
# Fix permissions so the web server can read the vendor directory
chown -R www-data:www-data vendor/
chown www-data:www-data composer.lock
chmod -R 755 vendor/
```

Then: **Setup > Plugins** → install and activate "Google SSO".

## Google Cloud Console

1. Create a project at https://console.cloud.google.com/
2. **APIs & Services > Credentials > Create Credentials > OAuth client ID** (type *Web application*).
3. In *Authorized redirect URIs*, register exactly:
   `https://YOUR_GLPI/plugins/googlesso/front/callback.php`
   (the exact URL is displayed on the plugin configuration screen).
4. Copy the [GOOGLE_CLIENT_ID] and [GOOGLE_CLIENT_SECRET] to the plugin
   configuration in GLPI (**Setup > Plugins > Google SSO**).

## Behavior

- Existing user (GLPI email = verified Google email): authenticates via
  the core's external authentication mechanism (`Auth::EXTERNAL` + `Session::init`).
- Non-existing user: created automatically if the option is enabled,
  with the default profile/entity configured.
- OAuth2 flow errors are logged in `files/_log/googlesso.log` and the
  user is returned to the native login page.
- **Activation toggle**: Google login can be enabled or disabled globally in the plugin configuration.
- **Domain restrictions**: Configure domain rules to restrict access and auto-assign entities/profiles based on the user's Google Workspace domain.
- **Audit Logging**: Successful SSO logins are recorded in the `glpi_events` table for auditing.
- **Authhistory Compatibility**: Integrates seamlessly with the [`authhistory`](https://github.com/andrefelipeufcg/authhistory) plugin to track login sessions.

## Requirements

- GLPI >= 11.0
- PHP >= 8.2
- `url_base` correctly configured in **Setup > General**

## Authors / Contributors

This plugin was developed together by:
* **Daniel Ramos** - [@danielrramos](https://github.com/danielrramos)
* **Andre Felipe** - [@andrefelipeufcg](https://github.com/andrefelipeufcg)

---

<a id="español"></a>
# Google SSO para GLPI 11

Inicio de sesión alternativo a través de Google (OAuth2/OpenID Connect). El formulario de inicio de sesión
local de GLPI permanece 100% funcional — el plugin solo agrega un botón
"Iniciar sesión con Google" en la página de inicio de sesión.

## Instalación

```bash
cd <GLPI>/plugins
cp -r googlesso .
cd googlesso
composer install --no-dev
# Corrige los permisos para que el servidor web pueda leer el directorio vendor
chown -R www-data:www-data vendor/
chown www-data:www-data composer.lock
chmod -R 755 vendor/
```

Luego: **Configuración > Plugins** → instale y active "Google SSO".

## Google Cloud Console

1. Cree un proyecto en https://console.cloud.google.com/
2. **APIs y Servicios > Credenciales > Crear Credenciales > ID de cliente OAuth** (tipo *Aplicación web*).
3. En *URI de redireccionamiento autorizados*, registre exactamente:
   `https://SU_GLPI/plugins/googlesso/front/callback.php`
   (la URL exacta se muestra en la pantalla de configuración del plugin).
4. Copie el [GOOGLE_CLIENT_ID] y el [GOOGLE_CLIENT_SECRET] en la configuración
   del plugin en GLPI (**Configuración > Plugins > Google SSO**).

## Comportamiento

- Usuario existente (correo electrónico GLPI = correo electrónico de Google verificado): se autentica a través del
  mecanismo de autenticación externa del core (`Auth::EXTERNAL` + `Session::init`).
- Usuario inexistente: se crea automáticamente si la opción está habilitada,
  con el perfil/entidad predeterminado configurado.
- Los errores del flujo OAuth2 se registran en `files/_log/googlesso.log` y el
  usuario es devuelto a la página de inicio de sesión nativa.
- **Botón de activación**: El inicio de sesión de Google se puede habilitar o deshabilitar globalmente en la configuración del plugin.
- **Restricciones de dominio**: Configure reglas de dominio para restringir el acceso y asignar automáticamente entidades/perfiles según el dominio de Google Workspace del usuario.
- **Registro de auditoría**: Los inicios de sesión SSO exitosos se registran en la tabla `glpi_events` para auditoría.
- **Compatibilidad con Authhistory**: Se integra perfectamente con el plugin [`authhistory`](https://github.com/andrefelipeufcg/authhistory) para rastrear las sesiones de inicio de sesión.

## Requisitos

- GLPI >= 11.0
- PHP >= 8.2
- `url_base` configurado correctamente en **Configuración > General**

## Autores / Colaboradores

Este plugin fue desarrollado conjuntamente por:
* **Daniel Ramos** - [@danielrramos](https://github.com/danielrramos)
* **Andre Felipe** - [@andrefelipeufcg](https://github.com/andrefelipeufcg)

---

<a id="français"></a>
# Google SSO pour GLPI 11

Connexion alternative via Google (OAuth2/OpenID Connect). Le formulaire de connexion
local de GLPI reste 100% fonctionnel — le plugin ajoute simplement un bouton
"Se connecter avec Google" sur la page de connexion.

## Installation

```bash
cd <GLPI>/plugins
cp -r googlesso .
cd googlesso
composer install --no-dev
# Corriger les permissions pour que le serveur web puisse lire le répertoire vendor
chown -R www-data:www-data vendor/
chown www-data:www-data composer.lock
chmod -R 755 vendor/
```

Ensuite : **Configuration > Plugins** → installer et activer "Google SSO".

## Google Cloud Console

1. Créez un projet sur https://console.cloud.google.com/
2. **APIs & Services > Credentials > Create Credentials > OAuth client ID** (type *Web application*).
3. Dans *Authorized redirect URIs*, enregistrez exactement :
   `https://VOTRE_GLPI/plugins/googlesso/front/callback.php`
   (l'URL exacte est affichée sur l'écran de configuration du plugin).
4. Copiez le [GOOGLE_CLIENT_ID] et le [GOOGLE_CLIENT_SECRET] dans la configuration
   du plugin dans GLPI (**Configuration > Plugins > Google SSO**).

## Comportement

- Utilisateur existant (e-mail GLPI = e-mail Google vérifié) : s'authentifie via
  le mécanisme d'authentification externe du cœur (`Auth::EXTERNAL` + `Session::init`).
- Utilisateur inexistant : créé automatiquement si l'option est activée,
  avec le profil/l'entité par défaut configuré.
- Les erreurs du flux OAuth2 sont enregistrées dans `files/_log/googlesso.log` et l'
  utilisateur est renvoyé à la page de connexion native.
- **Bouton d'activation** : La connexion Google peut être activée ou désactivée globalement dans la configuration du plugin.
- **Restrictions de domaine** : Configurez des règles de domaine pour restreindre l'accès et attribuer automatiquement des entités/profils en fonction du domaine Google Workspace de l'utilisateur.
- **Journal d'audit** : Les connexions SSO réussies sont enregistrées dans la table `glpi_events` pour l'audit.
- **Compatibilité Authhistory** : S'intègre de manière transparente avec le plugin [`authhistory`](https://github.com/andrefelipeufcg/authhistory) pour suivre les sessions de connexion.

## Prérequis

- GLPI >= 11.0
- PHP >= 8.2
- `url_base` configuré correctement dans **Configuration > Générale**

## Auteurs / Contributeurs

Ce plugin a été développé conjointement par :
* **Daniel Ramos** - [@danielrramos](https://github.com/danielrramos)
* **Andre Felipe** - [@andrefelipeufcg](https://github.com/andrefelipeufcg)

---

<a id="português"></a>
# Google SSO para GLPI 11

Login alternativo via Google (OAuth2/OpenID Connect). O formulário de login
local do GLPI permanece 100% funcional — o plugin apenas adiciona um botão
"Entrar com Google" na página de login.

## Instalação

```bash
cd <GLPI>/plugins
cp -r googlesso .
cd googlesso
composer install --no-dev
# Corrija as permissões para que o servidor web possa ler o diretório vendor
chown -R www-data:www-data vendor/
chown www-data:www-data composer.lock
chmod -R 755 vendor/
```

Depois: **Configuração > Plugins** → instalar e ativar "Google SSO".

## Google Cloud Console

1. Crie um projeto em https://console.cloud.google.com/
2. **APIs & Services > Credentials > Create Credentials > OAuth client ID** (tipo *Web application*).
3. Em *Authorized redirect URIs*, cadastre exatamente:
   `https://SEU_GLPI/plugins/googlesso/front/callback.php`
   (a URL exata é exibida na tela de configuração do plugin).
4. Copie o [GOOGLE_CLIENT_ID] e o [GOOGLE_CLIENT_SECRET] para a configuração
   do plugin no GLPI (**Configuração > Plugins > Google SSO**).

## Comportamento

- Usuário existente (e-mail do GLPI = e-mail Google verificado): autentica via
  mecanismo de autenticação externa do core (`Auth::EXTERNAL` + `Session::init`).
- Usuário inexistente: criado automaticamente se a opção estiver habilitada,
  com o perfil/entidade padrão configurados.
- Erros do fluxo OAuth2 são registrados em `files/_log/googlesso.log` e o
  usuário é devolvido à página de login nativa.
- **Ativação/Desativação**: O login via Google pode ser ativado ou desativado globalmente na configuração do plugin.
- **Restrições de domínio**: Configure regras de domínio para restringir o acesso e atribuir automaticamente entidades/perfis com base no domínio do Google Workspace do usuário.
- **Registro de Auditoria**: Logins SSO bem-sucedidos são registrados na tabela `glpi_events` para auditoria.
- **Compatibilidade com Authhistory**: Integra-se perfeitamente ao plugin [`authhistory`](https://github.com/andrefelipeufcg/authhistory) para rastrear as sessões de login.

## Requisitos

- GLPI >= 11.0
- PHP >= 8.2
- `url_base` configurado corretamente em **Configuração > Geral**

## Autores / Contribuidores

Este plugin foi desenvolvido em conjunto por:
* **Daniel Ramos** - [@danielrramos](https://github.com/danielrramos)
* **Andre Felipe** - [@andrefelipeufcg](https://github.com/andrefelipeufcg)
