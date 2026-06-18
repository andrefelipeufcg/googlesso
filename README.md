<div align="right">
  🇬🇧 <a href="#english">English</a> | 🇫🇷 <a href="#français">Français</a> | 🇧🇷 <a href="#português">Português</a>
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

## Requirements

- GLPI >= 11.0
- PHP >= 8.2
- `url_base` correctly configured in **Setup > General**

## Authors / Contributors

This plugin was developed together by:
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

## Requisitos

- GLPI >= 11.0
- PHP >= 8.2
- `url_base` configurado corretamente em **Configuração > Geral**

## Autores / Contribuidores

Este plugin foi desenvolvido em conjunto por:
* **Daniel Ramos** - [@danielrramos](https://github.com/danielrramos)
* **Andre Felipe** - [@andrefelipeufcg](https://github.com/andrefelipeufcg)
