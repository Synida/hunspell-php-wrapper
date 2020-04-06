<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. TakeNote. All rights reserved.
 */

use HunSpellPhpWrapper\DictionaryEditor;
use PHPUnit\Framework\TestCase;

/**
 * Class DictionaryTest
 * TODO: Test the exceptions too
 */
class DictionaryTest extends TestCase
{
    /**
     * Tests the dictionary's functionality
     * TODO: this test is too long, separate the functionality tests into smaller tests
     *
     * @return void
     * @author Synida Pry
     */
    public function testFunctionality()
    {
        $dictionaryPath = __DIR__ . '/_output/dictionary.dic';
        $words = [
            'himalaya',
            'kiwi'
        ];

        $dictionaryEditor = new DictionaryEditor();

        $dictionaryEditor->create($dictionaryPath);

        $this->assertFileExists($dictionaryPath);

        foreach ($words as $word) {
            $dictionaryEditor->addWord($dictionaryPath, $word);
        }

        $fileContent = file_get_contents($dictionaryPath);
        $fileWords = explode("\n", $fileContent);

        foreach ($fileWords as $word) {
            $this->assertContains($word, $words);
        }

        $dictionaryEditor->deleteWord($dictionaryPath, $words[0]);

        $fileContent = file_get_contents($dictionaryPath);
        $fileWords = explode("\n", $fileContent);

        $this->assertNotContains($words[0], $fileWords);

        $dictionaryWords = $dictionaryEditor->listWords($dictionaryPath);
        $this->assertNotEmpty($dictionaryWords);

        $modifiedWord = 'apple';
        $dictionaryEditor->editWord($dictionaryPath, $words[1], $modifiedWord);
        $fileContent = file_get_contents($dictionaryPath);
        $fileWords = explode("\n", $fileContent);

        $this->assertContains($modifiedWord, $fileWords);

        $dictionaryEditor->delete($dictionaryPath);

        $this->assertFileNotExists($dictionaryPath);
    }
}
