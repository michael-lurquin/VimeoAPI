<?php

namespace MichaelLurquin\Vimeo\Tests;

use PHPUnit\Framework\TestCase;
use MichaelLurquin\Vimeo\Vimeo;

class VimeoTest extends TestCase
{
    /** @test */
    public function bar()
    {
        $d = (new Vimeo())->foo();

        var_dump($d);

        $this->assertTrue(true);
    }
}