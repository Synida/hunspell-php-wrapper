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
 * @property string $dictionaryDir
 */
class DictionaryTest extends TestCase
{
    /**
     * Dictionary directory path
     *
     * @var string
     */
    protected $dictionaryDir;

    /**
     * DictionaryTest constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->dictionaryDir = __DIR__ . '/_output/';

        foreach (glob("{$this->dictionaryDir}/*.*") as $dictionaryFile) {
            unlink($dictionaryFile);
        }
    }

    /**
     * Tests the dictionary's functionality
     * TODO: this test is too long, separate the functionality tests into smaller tests
     *
     * @return void
     * @author Synida Pry
     */
    public function testFunctionality()
    {
        $dictionaryPath = $this->dictionaryDir . 'dictionary.dic';
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

        $fileWords = preg_replace('/(\r\n)|\r|\n/', '', $fileWords);

        $this->assertTrue(isset($fileWords[0]));
        $this->assertTrue(is_numeric($fileWords[0]));

        unset($fileWords[0]);

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

    /**
     * Testing the file operation methods; create/delete
     *
     * @return void
     * @author Synida Pry
     */
    public function testFileOperation()
    {
        $paths = [
            $this->dictionaryDir . 'dictionary.' . DictionaryEditor::DICTIONARY_EXTENSION,
            $this->dictionaryDir . 'template.' . DictionaryEditor::TEMPLATE_EXTENSION,
            $this->dictionaryDir . 'ruleset.' . DictionaryEditor::RULESET_EXTENSION
        ];

        foreach ($paths as $dictionaryFilePath) {
            $this->assertFileNotExists($dictionaryFilePath);
        }

        $dictionaryEditor = new DictionaryEditor();
        foreach ($paths as $dictionaryFilePath) {
            $result = $dictionaryEditor->create($dictionaryFilePath);
            $this->assertTrue($result);
            $this->assertFileExists($dictionaryFilePath);
        }

        foreach ($paths as $dictionaryFilePath) {
            $result = $dictionaryEditor->delete($dictionaryFilePath);
            $this->assertTrue($result);
            $this->assertFileNotExists($dictionaryFilePath);
        }
    }

    /**
     * Testing the invalid file extension for file operation methods.
     *
     * @return void
     * @author Synida Pry
     */
    public function testInvalidExtension()
    {
        $paths = [
            $this->dictionaryDir . 'dictionary.' . 'php',
            $this->dictionaryDir . 'dictionary.' . 'py'
        ];

        foreach ($paths as $dictionaryFilePath) {
            $this->assertFileNotExists($dictionaryFilePath);
        }

        $dictionaryEditor = new DictionaryEditor();
        foreach ($paths as $dictionaryFilePath) {
            $result = $dictionaryEditor->create($dictionaryFilePath);
            $this->assertFileNotExists($dictionaryFilePath);
            $this->assertFalse($result);
            $this->assertContains('Invalid extension', $dictionaryEditor->getMessage());
        }

        foreach ($paths as $dictionaryFilePath) {
            $result = $dictionaryEditor->delete($dictionaryFilePath);
            $this->assertFalse($result);
            $this->assertContains('Invalid extension', $dictionaryEditor->getMessage());
        }
    }
}
