<?php

namespace GlpiPlugin\Googlesso\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Testes de segurança para a lógica de matching de domínios no Google SSO.
 *
 * Verifica proteção contra os principais vetores de ataque em sistemas
 * de autenticação baseados em domínio de email:
 *
 * - Domain suffix spoofing (evil-ufcg.edu.br passa como ufcg.edu.br)
 * - Double-@ injection (user@gmail.com@ufcg.edu.br)
 * - Null byte injection (user@ufcg.edu.br\0@gmail.com)
 * - Unicode/homograph attacks (caracteres visuais parecidos)
 * - Whitespace injection (espaços escondidos no domínio)
 * - Input malformado e edge cases
 *
 * Para rodar no servidor de homologação:
 *   sudo php phpunit plugins/googlesso/tests/AuthenticatorSecurityTest.php --testdox
 */
class AuthenticatorSecurityTest extends TestCase
{
    /**
     * Replica a lógica de matching de domínio do Authenticator::createUser().
     *
     * IMPORTANTE: esta função deve ser mantida IDÊNTICA à lógica real
     * do Authenticator.php (linhas 88-105). Se o código de produção
     * mudar, este método deve ser atualizado junto.
     */
    private function resolveProfile(string $email, array $config): array
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        $profile_id = 0;
        $entity_id  = 0;

        foreach ($config['domain_rules'] as $rule) {
            // Matching seguro: exige igualdade exata OU que o domínio
            // seja um subdomínio real (separado por ponto).
            if ($domain === $rule['domain'] || str_ends_with($domain, '.' . $rule['domain'])) {
                $profile_id = $rule['profile_id'];
                $entity_id  = $rule['entity_id'];
                break;
            }
        }

        if ($profile_id === 0) {
            $profile_id = $config['default_profile_id'];
            $entity_id  = $config['default_entity_id'];
        }

        return [$profile_id, $entity_id];
    }

    private function getMockConfig(): array
    {
        return [
            'auto_create_users' => 1,
            'restrict_domain'   => '',
            'default_profile_id' => 1,
            'default_entity_id'  => 0,
            'domain_rules' => [
                ['domain' => 'professor.ufcg.edu.br', 'profile_id' => 4, 'entity_id' => 0],
                ['domain' => 'tecnico.ufcg.edu.br',   'profile_id' => 3, 'entity_id' => 0],
                ['domain' => 'estudante.ufcg.edu.br',  'profile_id' => 2, 'entity_id' => 0],
                ['domain' => 'ufcg.edu.br',            'profile_id' => 5, 'entity_id' => 0],
            ],
        ];
    }

    // ===================================================================
    // 1. DOMAIN SUFFIX SPOOFING
    //    Ataque: registrar um domínio que TERMINA com o domínio alvo.
    //    Ex: "evil-ufcg.edu.br" termina com "ufcg.edu.br"
    //    Se usar str_ends_with simples, o atacante ganha perfil indevido.
    // ===================================================================

    public function testSuffixSpoofingDominioBase(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('hacker@evil-ufcg.edu.br', $config);
        $this->assertEquals(1, $profile,
            'VULNERABILIDADE: evil-ufcg.edu.br NÃO deve receber perfil UFCG (5). Deve cair no padrão (1).');
    }

    public function testSuffixSpoofingNotUfcg(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('hacker@notufcg.edu.br', $config);
        $this->assertEquals(1, $profile,
            'VULNERABILIDADE: notufcg.edu.br NÃO deve receber perfil UFCG.');
    }

    public function testSuffixSpoofingSubdominioProfessor(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('hacker@fake-professor.ufcg.edu.br', $config);
        // fake-professor.ufcg.edu.br É um subdomínio legítimo de ufcg.edu.br,
        // mas NÃO é professor.ufcg.edu.br. Deve cair na regra genérica (5).
        $this->assertEquals(5, $profile,
            'fake-professor.ufcg.edu.br deve cair na regra genérica de ufcg.edu.br, não na de professor.');
    }

    public function testSuffixSpoofingDominioTecnico(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('hacker@evil-tecnico.ufcg.edu.br', $config);
        $this->assertEquals(5, $profile,
            'evil-tecnico.ufcg.edu.br deve cair na regra genérica de ufcg.edu.br, não na de técnico.');
    }

    // ===================================================================
    // 2. DOUBLE-@ INJECTION
    //    Ataque: usar múltiplos "@" para confundir o parser.
    //    Ex: "user@gmail.com@ufcg.edu.br" — strrchr pega o último @.
    // ===================================================================

    public function testDoubleAtInjection(): void
    {
        $config = $this->getMockConfig();
        // strrchr('@') pega o ÚLTIMO @, então o domínio extraído seria "ufcg.edu.br"
        // Em teoria, o Google OAuth NUNCA enviaria um email assim.
        // Mas se chegar, o matching deve ser seguro.
        [$profile, ] = $this->resolveProfile('user@gmail.com@ufcg.edu.br', $config);
        // Este caso é discutível: o domínio extraído É ufcg.edu.br.
        // O importante é que o Google OAuth nunca geraria isso.
        // Documentamos o comportamento esperado:
        $this->assertEquals(5, $profile,
            'Double-@ extrai o último domínio. O Google OAuth previne isso na origem.');
    }

    public function testDoubleAtSpoofingProfessor(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('user@gmail.com@professor.ufcg.edu.br', $config);
        $this->assertEquals(4, $profile,
            'Double-@ extrai o último domínio (professor.ufcg.edu.br). Google OAuth previne na origem.');
    }

    // ===================================================================
    // 3. WHITESPACE INJECTION
    //    Ataque: inserir espaços, tabs ou caracteres invisíveis no email
    //    para tentar burlar o matching ou passar por validações.
    // ===================================================================

    public function testEspacoNoDominio(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('user@ ufcg.edu.br', $config);
        $this->assertEquals(1, $profile,
            'Espaço no domínio deve invalidar o matching — cair no perfil padrão.');
    }

    public function testEspacoNoFinal(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('user@ufcg.edu.br ', $config);
        $this->assertEquals(1, $profile,
            'Espaço no final do domínio deve invalidar o matching.');
    }

    public function testTabNoDominio(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile("user@\tufcg.edu.br", $config);
        $this->assertEquals(1, $profile,
            'Tab no domínio deve invalidar o matching.');
    }

    // ===================================================================
    // 4. NULL BYTE INJECTION
    //    Ataque clássico em linguagens C-like: inserir \0 para truncar
    //    strings. PHP moderno não é vulnerável, mas testamos por segurança.
    // ===================================================================

    public function testNullByteNoDominio(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile("user@ufcg.edu.br\0@gmail.com", $config);
        // strrchr pega o último @, que seria "@gmail.com"
        // Mas com null byte, o comportamento pode variar.
        // O importante é que NÃO receba perfil de ufcg.edu.br.
        $this->assertNotEquals(5, $profile,
            'Null byte injection não deve conceder acesso ao domínio UFCG.');
    }

    // ===================================================================
    // 5. UNICODE / HOMOGRAPH ATTACKS
    //    Ataque: usar caracteres Unicode visualmente idênticos a ASCII.
    //    Ex: "а" cirílico (U+0430) em vez de "a" latino.
    //    Em domínios reais isso seria Punycode, mas testamos o matching.
    // ===================================================================

    public function testHomographCirilico(): void
    {
        $config = $this->getMockConfig();
        // "а" aqui é o caractere cirílico U+0430, visualmente igual ao "a" latino
        $emailFalso = "user@ufcg.edu.b" . "\xD1\x80"; // "р" cirílico no lugar de "r"
        [$profile, ] = $this->resolveProfile($emailFalso, $config);
        $this->assertEquals(1, $profile,
            'Caracteres Unicode/cirílicos similares NÃO devem dar match no domínio.');
    }

    // ===================================================================
    // 6. INPUTS MALFORMADOS E EDGE CASES
    //    Testa o comportamento com emails inválidos, vazios,
    //    ou em formatos inesperados.
    // ===================================================================

    public function testEmailSemDominio(): void
    {
        $config = $this->getMockConfig();
        // "user@" → strrchr retorna "@", substr(..., 1) retorna ""
        [$profile, ] = $this->resolveProfile('user@', $config);
        $this->assertEquals(1, $profile,
            'Email sem domínio deve cair no perfil padrão.');
    }

    public function testApenasArroba(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('@', $config);
        $this->assertEquals(1, $profile,
            'Apenas "@" deve cair no perfil padrão.');
    }

    public function testEmailVazio(): void
    {
        $config = $this->getMockConfig();
        // Email vazio: strrchr('', '@') retorna false.
        // substr(false, 1) retorna string vazia "".
        // String vazia não bate nenhuma regra → cai no perfil padrão (1).
        // Isso é seguro porque o método login() já rejeita emails vazios
        // ANTES de chamar createUser(). Aqui só confirmamos que a lógica
        // de matching não dá crash nem concede acesso indevido.
        [$profile, ] = $this->resolveProfile('', $config);
        $this->assertEquals(1, $profile,
            'Email vazio deve cair no perfil padrão, sem conceder acesso especial.');
    }

    public function testDominioMuitoLongo(): void
    {
        $config = $this->getMockConfig();
        $dominioLongo = str_repeat('a', 500) . '@ufcg.edu.br';
        [$profile, ] = $this->resolveProfile($dominioLongo, $config);
        // Deve funcionar normalmente, sem crash (ex: overflow)
        $this->assertEquals(5, $profile,
            'Domínio com local-part muito longo deve ser tratado normalmente.');
    }

    // ===================================================================
    // 7. PATH TRAVERSAL / SPECIAL CHARACTERS
    //    Testa se caracteres especiais no email poderiam causar
    //    problemas em downstream (logs, SQL, etc).
    // ===================================================================

    public function testCaracteresEspeciaisNoEmail(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile("user'OR'1'='1@ufcg.edu.br", $config);
        // A lógica de domínio não deve ser afetada por SQL injection no local-part
        $this->assertEquals(5, $profile,
            'SQL injection no local-part não deve afetar o matching de domínio.');
    }

    public function testHTMLInjectionNoEmail(): void
    {
        $config = $this->getMockConfig();
        [$profile, ] = $this->resolveProfile('<script>alert(1)</script>@ufcg.edu.br', $config);
        $this->assertEquals(5, $profile,
            'HTML/XSS no local-part não deve afetar o matching de domínio.');
    }

    // ===================================================================
    // 8. ORDEM DAS REGRAS (PRIORITY BYPASS)
    //    Verifica que a ordem das regras no config é respeitada.
    //    Um atacante poderia tentar explorar precedência para
    //    ganhar um perfil mais privilegiado.
    // ===================================================================

    public function testOrdemRegrasMaisEspecificaPrimeiro(): void
    {
        $config = $this->getMockConfig();
        // professor.ufcg.edu.br TAMBÉM termina com ufcg.edu.br
        // Mas a regra de professor vem ANTES, então deve ter prioridade.
        [$profile, ] = $this->resolveProfile('user@professor.ufcg.edu.br', $config);
        $this->assertEquals(4, $profile,
            'A regra mais específica (professor) deve ter prioridade sobre a genérica (ufcg).');
    }

    public function testOrdemRegrasInvertida(): void
    {
        // Simula um config onde a regra genérica vem ANTES da específica
        $config = [
            'default_profile_id' => 1,
            'default_entity_id'  => 0,
            'domain_rules' => [
                ['domain' => 'ufcg.edu.br',            'profile_id' => 5, 'entity_id' => 0],
                ['domain' => 'professor.ufcg.edu.br',  'profile_id' => 4, 'entity_id' => 0],
            ],
        ];
        [$profile, ] = $this->resolveProfile('user@professor.ufcg.edu.br', $config);
        // Com a regra genérica primeiro, professor.ufcg.edu.br bate na regra de ufcg.edu.br
        // e recebe perfil 5 ao invés de 4. Isso documenta que a ORDEM IMPORTA.
        $this->assertEquals(5, $profile,
            'Com regras na ordem errada, professor pega perfil genérico. A ORDEM DAS REGRAS IMPORTA.');
    }
}
