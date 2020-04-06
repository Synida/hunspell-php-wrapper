<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. TakeNote. All rights reserved.
 */

use HunSpellPhpWrapper\DictionaryEditor;
use PHPUnit\Framework\TestCase;

/**
 * Class DictionaryTest
 *
 */
class DictionaryTest extends TestCase
{
    /**
     * Tests the dictionary's functionality
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

        $dictionaryEditor->delete($dictionaryPath);

        $this->assertFileNotExists($dictionaryPath);
    }
}
