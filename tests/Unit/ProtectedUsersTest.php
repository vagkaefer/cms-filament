<?php

namespace VagKaefer\CmsFilament\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VagKaefer\CmsFilament\Support\ProtectedUsers;

/**
 * O reconhecimento dos domínios protegidos: só o domínio real do e-mail conta,
 * e nada que meramente contenha o texto protegido passa.
 */
class ProtectedUsersTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedEmailProvider(): array
    {
        return [
            'cloudger.com.br' => ['suporte@cloudger.com.br'],
            'cloudger.host' => ['suporte@cloudger.host'],
            'kaefer.eng.br' => ['vagner@kaefer.eng.br'],
            'maiusculas' => ['Vagner@Kaefer.Eng.BR'],
            'com espaco em volta' => ['  vagner@kaefer.eng.br  '],
        ];
    }

    #[DataProvider('protectedEmailProvider')]
    public function testRecognizesProtectedDomains(string $email): void
    {
        $this->assertTrue(ProtectedUsers::isProtectedEmail($email));
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function unprotectedEmailProvider(): array
    {
        return [
            'outro dominio' => ['alex@rgbweb.com.br'],
            'dominio protegido como prefixo' => ['golpe@cloudger.com.br.exemplo.com'],
            'dominio protegido como sufixo' => ['golpe@naocloudger.com.br'],
            'dominio protegido no nome' => ['cloudger.com.br@exemplo.com'],
            'sem arroba' => ['cloudger.com.br'],
            'vazio' => [''],
            'nulo' => [null],
        ];
    }

    #[DataProvider('unprotectedEmailProvider')]
    public function testIgnoresEverythingElse(?string $email): void
    {
        $this->assertFalse(ProtectedUsers::isProtectedEmail($email));
    }

    public function testNullModelIsNotProtected(): void
    {
        $this->assertFalse(ProtectedUsers::isProtected(null));
    }
}
