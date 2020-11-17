<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

namespace HunSpellPhpWrapper;

use Exception;

/**
 * Class DictionaryEditor
 * @package HunSpellPhpWrapper
 *
 * @property string $message
 */
class DictionaryEditor
{
    /**
     * Acceptable dictionary extension for hunspell
     *
     * @const string
     */
    const DICTIONARY_EXTENSION = 'dic';

    /**
     * Ruleset extension
     *
     * @const string
     */
    const RULESET_EXTENSION = 'aff';

    /**
     * Template file extension for custom dictionary words.
     * In the template files one can store the custom words which would be used for generating
     * custom dictionaries.
     *
     * @const string
     */
    const TEMPLATE_EXTENSION = 'tpl';

    /**
     * Contains messages about the last logged un/successful object operation.
     *
     * @var string
     */
    protected $message = '';

    /**
     * Creates a dictionary file on the given path.
     *
     * @param string $path
     * @return bool
     * @author Synida Pry
     */
    public function create($path)
    {
        $pathParts = explode('.', $path);
        $extension = end($pathParts);

        if (!in_array(
            $extension,
            [
                static::DICTIONARY_EXTENSION,
                static::RULESET_EXTENSION,
                static::TEMPLATE_EXTENSION
            ]
        )) {
            $this->message = "Invalid extension({$extension})";
            return false;
        }

        try {
            $file = fopen($path, 'w');

            fwrite($file, '');

            fclose($file);
        } catch (Exception $exception) {
            $this->message = 'Failed to create new dictionary: ' . $exception->getMessage();
            return false;
        }

        $result = is_writable($path) && is_readable($path);

        if (!$result) {
            $this->message = 'The created dictionary is not writeable/readable.';
        }

        return $result;
    }

    /**
     * Deletes the dictionary on the given path if exists.
     *
     * @param string $path
     * @return bool
     * @author Synida Pry
     */
    public function delete($path)
    {
        $pathParts = explode('.', $path);
        $extension = end($pathParts);

        if (!in_array(
            $extension,
            [
                static::DICTIONARY_EXTENSION,
                static::RULESET_EXTENSION,
                static::TEMPLATE_EXTENSION
            ]
        )) {
            $this->message = "Invalid extension({$extension})";
            return false;
        }

        if (file_exists($path)) {
            unlink($path);
            return true;
        }

        $this->message = "Path({$path}) is invalid";
        return false;
    }

    /**
     * Adds a word to a specific dictionary.
     *
     * @param string $path
     * @param string $word
     * @return bool
     * @author Synida Pry
     */
    public function addWord($path, $word)
    {
        // Check empty or invalid word
        if ($this->isInvalidWord($word)) {
            $this->message = 'This word is invalid';
            return false;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        // Returns with the words of the dictionary or template found on the specified path.
        $words = $this->getDictionaryWords($path);

        foreach ($words as $currentWord) {
            $wordParts = explode('/', $currentWord);
            if (strtolower($wordParts[0]) === strtolower($word)) {
                $this->message = 'The word already exists in the database';
                return false;
            }
        }

        $words[] = $word;

        $words = array_filter(
            $words,
            function ($value) {
                return !is_null($value) && $value !== '' && $value !== PHP_EOL;
            }
        );

        natcasesort($words);

        if ($ext !== static::TEMPLATE_EXTENSION) {
            array_unshift($words, count($words));
        }

        $wordsString = ltrim(implode(PHP_EOL, $words), PHP_EOL);
        file_put_contents($path, $wordsString);

        return true;
    }

    /**
     * Check empty or invalid word
     *
     * @param string $word
     * @return bool
     * @author Synida Pry
     */
    protected function isInvalidWord($word)
    {
        if (empty(trim($word))) {
            return true;
        }

        return false;
    }

    /**
     * Deletes a word from the dictionary if exists.
     *
     * @param string $path
     * @param string $word
     * @return bool
     * @author Synida Pry
     */
    public function deleteWord($path, $word)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        // Returns with the words of the dictionary or template found on the specified path.
        $words = $this->getDictionaryWords($path);

        $found = false;
        foreach ($words as $key => $currentWord) {
            $wordParts = explode('/', $currentWord);

            if (strtolower($wordParts[0]) === strtolower($word)) {
                $found = true;
                unset($words[$key]);
                break;
            }
        }

        if (!$found) {
            $this->message = "The defined dictionary({$path}) does not contain this word({$word})";
            return false;
        }

        $words = array_filter(
            $words,
            function ($value) {
                return !is_null($value) && $value !== '' && $value !== PHP_EOL;
            }
        );

        if ($ext !== static::TEMPLATE_EXTENSION) {
            array_unshift($words, count($words));
        }

        file_put_contents($path, ltrim(implode(PHP_EOL, $words), PHP_EOL));

        return true;
    }

    /**
     * Modifies and existing word in a dictionary.
     *
     * @param string $path
     * @param string $word
     * @param string $modifiedWord
     * @return bool
     * @author Synida Pry
     */
    public function editWord($path, $word, $modifiedWord)
    {
        // Check empty or invalid word
        if ($this->isInvalidWord($modifiedWord)) {
            $this->message = 'This word is invalid';
            return false;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        // Returns with the words of the dictionary or template found on the specified path.
        $words = $this->getDictionaryWords($path);

        $found = false;
        foreach ($words as $key => $currentWord) {
            $wordParts = explode('/', $currentWord);

            if (strtolower($wordParts[0]) === strtolower($word)) {
                $found = true;
                break;
            }
        }

        if (!$found || !isset($key)) {
            $this->message = "The defined dictionary({$path}) does not contain this word({$word})";
            return false;
        }

        if (in_array($modifiedWord, $words)) {
            $this->message = 'This word is already in the dictionary';
            return false;
        }

        $words[$key] = $modifiedWord;

        $words = array_filter(
            $words,
            function ($value) {
                return !is_null($value) && $value !== '' && $value !== PHP_EOL;
            }
        );

        if ($ext !== static::TEMPLATE_EXTENSION) {
            array_unshift($words, count($words));
        }

        file_put_contents($path, ltrim(implode(PHP_EOL, $words), PHP_EOL));

        return true;
    }

    /**
     * Lists the existing word in a dictionary.
     *
     * @param string $path
     * @return array
     * @author Synida Pry
     */
    public function listWords($path)
    {
        $dictionaryContent = file_get_contents($path);

        $dictionaryContent = preg_replace('/(\r\n)|\r|\n/', PHP_EOL, $dictionaryContent);
        $result = explode(PHP_EOL, $dictionaryContent);

        if (isset($result[0]) && is_numeric($result[0])) {
            unset($result[0]);
        }

        return is_string($result) ? [$result] : $result;
    }

    /**
     * Returns with the logged un/successful object message
     *
     * @return string
     * @author Synida Pry
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns with the words of the dictionary or template found on the specified path.
     *
     * @param string $path
     * @return array
     * @author Synida Pry
     */
    protected function getDictionaryWords($path)
    {
        $dictionaryContent = file_get_contents($path);

        $dictionaryContent = preg_replace('/(\r\n)|\r|\n/', PHP_EOL, $dictionaryContent);
        $words = explode(PHP_EOL, $dictionaryContent);

        if (isset($words[0]) && is_numeric($words[0])) {
            unset($words[0]);
        }

        return $words;
    }
}
