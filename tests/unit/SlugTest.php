<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Testa a transliteração do slug em português (Fase 5).
 */
final class SlugTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('texto');
    }

    public function testSlugComAcentos(): void
    {
        $this->assertSame('soletracao-nacional', slug_pt('Soletração Nacional'));
    }

    public function testSlugRemoveCaracteresEspeciais(): void
    {
        $this->assertSame('edicao-2026', slug_pt('Edição — 2026!'));
    }

    public function testSlugCedilhaETil(): void
    {
        $this->assertSame('conducao-e-organizacao', slug_pt('Condução e Organização'));
    }
}
