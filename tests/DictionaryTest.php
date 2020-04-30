<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
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
     * Failing to create a dictionary file
     *
     * @return void
     * @author Synida Pry
     */
    public function testCanNotCreateFile()
    {
        $path = $this->dictionaryDir . 'not/exists/dictionary.' . DictionaryEditor::DICTIONARY_EXTENSION;

        $dictionaryEditor = new DictionaryEditor();

        $result = $dictionaryEditor->create($path);
        $this->assertFalse($result);
        $this->assertStringContainsString(
            'Failed to create new dictionary:',
            $dictionaryEditor->getMessage()
        );
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

    /**
     * Testing the delete function with invalid path
     *
     * @return void
     * @author Synida Pry
     */
    public function testInvalidPathDelete()
    {
        $randomPath = $this->dictionaryDir . 'randomPath123.' . DictionaryEditor::DICTIONARY_EXTENSION;

        $dictionaryEditor = new DictionaryEditor();
        $dictionaryEditor->delete($randomPath);

        $this->assertStringContainsString("Path({$randomPath}) is invalid", $dictionaryEditor->getMessage());
    }

    /**
     * Trying to duplicate a word in a dictionary with the add existing word method
     *
     * @return void
     * @author Synida Pry
     */
    public function testAddExistingWordToDictionary()
    {
        $dictionaryPath = $this->dictionaryDir . 'test.' . DictionaryEditor::DICTIONARY_EXTENSION;

        $this->assertFileNotExists($dictionaryPath);

        $dictionaryEditor = new DictionaryEditor();
        $dictionaryEditor->create($dictionaryPath);

        $this->assertFileExists($dictionaryPath);

        $word = 'sandcrawler';
        $result = $dictionaryEditor->addWord($dictionaryPath, $word);

        $this->assertTrue($result);

        $dictionaryWords = explode(PHP_EOL, file_get_contents($dictionaryPath));
        $this->assertIsArray($dictionaryWords);
        $this->assertCount(2, $dictionaryWords);
        $this->assertEquals(1, $dictionaryWords[0]);
        $this->assertEquals($word, trim($dictionaryWords[1]));

        $result = $dictionaryEditor->addWord($dictionaryPath, $word);

        $this->assertFalse($result);
        $this->assertEquals('The word already exists in the database', $dictionaryEditor->getMessage());
    }

    /**
     * Trying to delete non existing word from the dictionary
     *
     * @return void
     * @author Synida Pry
     */
    public function testDeleteNonExistingWord()
    {
        $dictionaryPath = $this->dictionaryDir . 'test.' . DictionaryEditor::DICTIONARY_EXTENSION;

        $dictionaryEditor = new DictionaryEditor();
        $dictionaryEditor->create($dictionaryPath);

        $wordToDelete = 'flux-condenser';
        $result = $dictionaryEditor->deleteWord($dictionaryPath, $wordToDelete);

        $this->assertFalse($result);
        $this->assertEquals(
            "The defined dictionary({$dictionaryPath}) does not contain this word({$wordToDelete})",
            $dictionaryEditor->getMessage()
        );

        $dictionaryEditor->delete($dictionaryPath);
    }

    /**
     * Trying to modify non existing word
     *
     * @return void
     * @author Synida Pry
     */
    public function testEditNonExistingWord()
    {
        $dictionaryPath = $this->dictionaryDir . 'test.' . DictionaryEditor::DICTIONARY_EXTENSION;

        $dictionaryEditor = new DictionaryEditor();
        $dictionaryEditor->create($dictionaryPath);

        $wordToModify = 'flex-condenser';
        $targetWord = 'flux-condenser';
        $result = $dictionaryEditor->editWord($dictionaryPath, $wordToModify, $targetWord);

        $this->assertFalse($result);
        $this->assertEquals(
            "The defined dictionary({$dictionaryPath}) does not contain this word({$wordToModify})",
            $dictionaryEditor->getMessage()
        );

        $dictionaryEditor->delete($dictionaryPath);
    }

    /**
     * Trying to edit a word to an another existing word
     *
     * @return void
     * @author Synida Pry
     */
    public function testEditToExistingWord()
    {
        $dictionaryPath = $this->dictionaryDir . 'test.' . DictionaryEditor::DICTIONARY_EXTENSION;

        $wordToEdit = 'word1';
        $targetWord = 'word2';
        file_put_contents($dictionaryPath, $wordToEdit . PHP_EOL . $targetWord . PHP_EOL);

        $dictionaryEditor = new DictionaryEditor();

        $result = $dictionaryEditor->editWord($dictionaryPath, $wordToEdit, $targetWord);

        $this->assertFalse($result);
        $this->assertEquals('This word is already in the dictionary', $dictionaryEditor->getMessage());

        unlink($dictionaryPath);
    }
}
