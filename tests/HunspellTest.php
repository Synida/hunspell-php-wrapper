<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

use HunSpellPhpWrapper\exception\InvalidResponseTypeException;
use HunSpellPhpWrapper\exception\InvalidThreadNumberException;
use HunSpellPhpWrapper\HunSpell;
use PHPUnit\Framework\TestCase;

/**
 * Class HunspellTest
 */
class HunspellTest extends TestCase
{
    /**
     * Testing the response setter/getter
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws Exception
     * @author Synida Pry
     */
    public function testResponseTypeSetterGetter()
    {
        $spellChecker = new HunSpell();

        $exception = false;
        try {
            $spellChecker->setResponseType('asd');
        } catch (InvalidResponseTypeException $e) {
            $exception = true;
            $this->assertStringContainsString('is invalid', $e->getMessage());
        }

        $this->assertTrue($exception);

        $spellChecker->setResponseType(HunSpell::ARRAY_RESPONSE);

        $responseType = $spellChecker->getResponseType();
        $this->assertEquals(HunSpell::ARRAY_RESPONSE, $responseType);
    }

    /**
     * Testing the thread number setter/getter
     *
     * @return void
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public function testThreadNumberSetterGetter()
    {
        $spellChecker = new HunSpell();

        $exception = false;
        try {
            $spellChecker->setMaxThreads('asd');
        } catch (InvalidThreadNumberException $e) {
            $exception = true;
            $this->assertStringContainsString(
                'Thread number must be a positive integer',
                $e->getMessage()
            );
        }

        $this->assertTrue($exception);

        $spellChecker->setMaxThreads(2);

        $maxThreads = $spellChecker->getMaxThreads();
        $this->assertEquals(2, $maxThreads);
    }

    /**
     * Testing the dictionary setter/getter
     *
     * @return void
     * @author Synida Pry
     */
    public function testDictionarySetterGetter()
    {
        $spellChecker = new HunSpell();

        $dictionary = 'en_GB';
        $spellChecker->setDictionary($dictionary);

        $userDictionary = $spellChecker->getDictionary();
        $this->assertEquals($dictionary, $userDictionary);
    }

    /**
     * Testing the encoding setter/getter
     *
     * @return void
     * @author Synida Pry
     */
    public function testEncodingSetterGetter()
    {
        $spellChecker = new HunSpell();

        $encoding = 'utf-8';
        $spellChecker->setEncoding($encoding);

        $usedEncoding = $spellChecker->getEncoding();
        $this->assertEquals($encoding, $usedEncoding);
    }
}
