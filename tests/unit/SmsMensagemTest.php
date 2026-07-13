<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\Sms\SmsMensagem;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Testes da normalização de telefone e segmentação de SMS (Fase 7).
 * Exemplo do padrão de testes recomendado na Fase 10: testar as regras
 * puras dos services/libraries, onde vive a correção do sistema.
 */
final class SmsMensagemTest extends CIUnitTestCase
{
    public function testNormalizaTelefoneLocal(): void
    {
        $msg = new SmsMensagem('923123456', 'Ola');
        $this->assertSame('+244923123456', $msg->telefone);
    }

    public function testNormalizaTelefoneComPrefixo(): void
    {
        $msg = new SmsMensagem('+244 923 123 456', 'Ola');
        $this->assertSame('+244923123456', $msg->telefone);
    }

    public function testTelefoneInvalidoLancaExcecao(): void
    {
        $this->expectException(\RuntimeException::class);
        new SmsMensagem('12345', 'Ola');
    }

    public function testMensagemGsmUmaParte(): void
    {
        $msg = new SmsMensagem('923123456', str_repeat('a', 150));
        $this->assertSame('GSM-7', $msg->codificacao);
        $this->assertSame(1, $msg->partes);
    }

    public function testMensagemComAcentoUsaUcs2(): void
    {
        // 'ã' não está no GSM básico → UCS-2 (por isso os templates SMS
        // semeados evitam acentuação, para não triplicar o custo).
        $msg = new SmsMensagem('923123456', 'inscrição validada');
        $this->assertSame('UCS-2', $msg->codificacao);
    }
}
