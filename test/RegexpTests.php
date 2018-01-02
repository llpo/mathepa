<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Lexer;

/**
 * Class RegexpTest
 */
class RegexpTest extends TestCase
{
    /**
     * @test
     */
    public function regularExpressionToMatchIeee754FormatedNumbers()
    {
        $exp = '/^/' . Lexer::LITERALS_REGEXS[2] . '$/';

        $this->assertRegExp($exp, '-7E-20');
        $this->assertRegExp($exp, '-7E+20');
        $this->assertRegExp($exp, '+7E-20');
        $this->assertRegExp($exp, '+7E+20');
        $this->assertRegExp($exp, '10.e24');
        $this->assertRegExp($exp, '+2.e18');
        $this->assertRegExp($exp, '+3.e02');
        $this->assertRegExp($exp, '1.92E+30');

        $this->assertNotRegExp($exp, '-.19');
        $this->assertNotRegExp($exp, '10');
        $this->assertNotRegExp($exp, '1.0a1');
        $this->assertNotRegExp($exp, '10.a');
        $this->assertNotRegExp($exp, 'm10.');
        $this->assertNotRegExp($exp, '+10');
        $this->assertNotRegExp($exp, '99.99');
    }

    /**
     * @test
     */
    public function regularExpressionToMatchDecimals()
    {
        $exp = '/^/' . Lexer::LITERALS_REGEXS[1] . '$/';

        $this->assertRegExp($exp, '-10.5');
        $this->assertRegExp($exp, '+.12');
        $this->assertRegExp($exp, '-.19');
        $this->assertRegExp($exp, '+10.3');
        $this->assertRegExp($exp, '+11.');
        $this->assertRegExp($exp, '99.99');

        $this->assertNotRegExp($exp, '10');
        $this->assertNotRegExp($exp, '1.0e1');
        $this->assertNotRegExp($exp, '10.a');
        $this->assertNotRegExp($exp, 'm10.');
        $this->assertNotRegExp($exp, '+10');
        $this->assertNotRegExp($exp, '10-');
    }

    /**
     * @test
     */
    public function regularExpressionToMatchIntegers()
    {
        $exp = '/^/' . Lexer::LITERALS_REGEXS[0] . '$/';

        $this->assertRegExp($exp, '10');
        $this->assertRegExp($exp, '00');
        $this->assertRegExp($exp, '+10');
        $this->assertRegExp($exp, '-10');
        $this->assertRegExp($exp, '999');

        $this->assertNotRegExp($exp, '.10');
        $this->assertNotRegExp($exp, '10b');
        $this->assertNotRegExp($exp, '10.');
        $this->assertNotRegExp($exp, '1.0');
        $this->assertNotRegExp($exp, '10-');
    }
}
