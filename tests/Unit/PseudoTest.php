<?php

namespace Tests\Unit;

use App\Support\Pseudo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PseudoTest extends TestCase
{
    #[Test]
    public function la_forme_normalisee_ignore_accents_majuscules_et_separateurs(): void
    {
        $this->assertSame('elise', Pseudo::key('Élise'));
        $this->assertSame('elise', Pseudo::key('  elise '));
        $this->assertSame('eliserose', Pseudo::key('Élise-Rose'));
        $this->assertSame('lecorbeau', Pseudo::key("Le Corbeau"));
    }

    #[Test]
    public function le_format_autorise_lettres_chiffres_et_ponctuation_simple(): void
    {
        $this->assertSame(1, preg_match(Pseudo::PATTERN, 'Élise-Rose'));
        $this->assertSame(1, preg_match(Pseudo::PATTERN, "O'Hara 2"));
        $this->assertSame(0, preg_match(Pseudo::PATTERN, '<script>'));
        $this->assertSame(0, preg_match(Pseudo::PATTERN, '-tiret'));
    }

    #[Test]
    public function les_espaces_multiples_sont_reduits(): void
    {
        $this->assertSame('Le Corbeau', Pseudo::clean("  Le   Corbeau "));
    }
}
