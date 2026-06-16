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
