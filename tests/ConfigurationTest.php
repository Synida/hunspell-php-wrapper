<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

use HunSpellPhpWrapper\exception\InvalidResponseTypeException;
use HunSpellPhpWrapper\exception\InvalidThreadNumberException;
use HunSpellPhpWrapper\Installer;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurationTest
 */
class ConfigurationTest extends TestCase
{
    /**
     * Testing the configuration function
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public function testConfiguration()
    {
        $cpuThreadNumber = Installer::configureMaxThreads();
        $this->assertIsNumeric($cpuThreadNumber);
        $this->assertGreaterThan(0, $cpuThreadNumber);

        $wordPerThreadRatio = Installer::configureWordPerThreadRatio($cpuThreadNumber);
        $this->assertIsNumeric($wordPerThreadRatio);
        $this->assertGreaterThan(0, $wordPerThreadRatio);
    }
}
