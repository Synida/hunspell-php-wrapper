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
 * Class SuggestionTest
 */
class SuggestionTest extends TestCase
{
    /**
     * Testing the basic suggestions.
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws Exception
     * @author Synida Pry
     */
    public function testBasic()
    {
        $spellChecker = new HunSpell('en_US.utf8', 'en_US');

        $result = $spellChecker->suggest('Haters gonn hate. Potatoes gonna potate. Crocodiles gonna crocodile.');

        $this->assertNotEmpty($result, 'Result is not empty');

        $arrayResult = json_decode($result, true);

        $this->assertNotEmpty($arrayResult, 'Result array is not empty');
        $this->assertIsArray($arrayResult, 'Array conversion complete');
        foreach ($arrayResult as $suggestion) {
            $this->assertArrayHasKey(HunSpell::MISSPELLED_WORD_KEY, $suggestion);
            $this->assertArrayHasKey(HunSpell::SUGGESTION_COUNT_KEY, $suggestion);
            $this->assertArrayHasKey(HunSpell::WORD_POSITION_KEY, $suggestion);
            $this->assertArrayHasKey(HunSpell::SUGGESTIONS_KEY, $suggestion);
            $this->assertIsArray($suggestion[HunSpell::SUGGESTIONS_KEY]);
        }
    }

    /**
     * Testing the json response for incorrect word.
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws Exception
     * @author Synida Pry
     */
    public function testIncorrect()
    {
        $spellChecker = new HunSpell('en_US.utf8', 'en_US');

        $result = $spellChecker->suggest('asdasdasdasdasdasd');

        $this->assertNotEmpty($result, 'Result is not empty');

        $arrayResult = json_decode($result, true);

        $this->assertNotEmpty($arrayResult, 'Result array is not empty');
        $this->assertIsArray($arrayResult, 'Array conversion complete');
        $this->assertArrayHasKey(0, $arrayResult);
        $this->assertArrayHasKey(HunSpell::MISSPELLED_WORD_KEY, $arrayResult[0]);
        $this->assertArrayHasKey(HunSpell::WORD_POSITION_KEY, $arrayResult[0]);
    }

    /**
     * Testing the array response.
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws Exception
     * @author Synida Pry
     */
    public function testArrayResponse()
    {
        $spellChecker = new HunSpell('en_US.utf8', 'en_US', HunSpell::ARRAY_RESPONSE);

        $result = $spellChecker->suggest('You shall passsss here');

        $this->assertNotEmpty($result);
        $this->assertIsArray($result);
    }

    /**
     * Testing the single thread functionality, when parallel module is no enabled
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws Exception
     * @author Synida Pry
     */
    public function testSingleThreading()
    {
        if (extension_loaded('parallel')) {
            return;
        }

        $this->assertNotEmpty((new HunSpell())->suggest('penguins'), 'Works fine without parallel module');
    }

    /**
     * Testing the multi threading when the parallel module is enabled
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws Exception
     * @author Synida Pry
     */
    public function testMultiThreading()
    {
        if (!extension_loaded('parallel')) {
            return;
        }

        $this->assertNotEmpty(
            (new HunSpell('en_US.utf8', 'en_US'))->suggest('penguins'),
            'Works fine with parallel module'
        );
    }

    /**
     * Testing the quotation mark bug.
     *
     * @return void
     * @throws InvalidResponseTypeException
     * @throws InvalidThreadNumberException
     * @throws Exception
     * @author Synida Pry
     */
    public function testQuotationMarkBug()
    {
        $result = (new HunSpell('en_US.utf8', 'en_US'))
            ->suggest('"be on not to be. This is the qwestion"');
        $this->assertNotEmpty($result, 'No problem with quotation marks');
    }
}
