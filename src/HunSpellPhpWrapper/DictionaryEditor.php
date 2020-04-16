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

        if ($extension !== static::DICTIONARY_EXTENSION) {
            $this->message = "Invalid extension({$extension})";
            return false;
        }

        try {
            $file = fopen($path, 'w');

            fwrite($file,'');

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

        if ($extension !== static::DICTIONARY_EXTENSION) {
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
        $dictionaryContent = file_get_contents($path);

        if (strpos($dictionaryContent, $word) !== false) {
            $this->message = 'The word already exists in the database';
            return false;
        }

        preg_replace('/(\r\n)|\r/', "\n", $dictionaryContent);
        $words = explode("\n", $dictionaryContent);
        $words[] = $word;
        natcasesort($words);
        $wordsString = ltrim(implode("\n", $words), "\n");
        file_put_contents($path, $wordsString);

        return true;
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
        $dictionaryContent = file_get_contents($path);

        if (strpos($dictionaryContent, $word) !== false) {
            preg_replace('/(\r\n)|\r/', "\n", $dictionaryContent);
            $words = explode("\n", $dictionaryContent);
            foreach ($words as $wordKey => $currentWord) {
                if ($word === $currentWord) {
                    unset($words[$wordKey]);
                    break;
                }
            }
            file_put_contents($path, ltrim(implode("\n", $words), "\n"));

            return true;
        }

        $this->message = "The defined dictionary({$path}) does not contain this word({$word})";
        return false;
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
        $dictionaryContent = file_get_contents($path);

        if (strpos($dictionaryContent, $word) !== false) {
            preg_replace('/(\r\n)|\r/', "\n", $dictionaryContent);
            $words = explode("\n", $dictionaryContent);
            foreach ($words as $wordKey => $currentWord) {
                if ($word === $currentWord) {
                    $words[$wordKey] = $modifiedWord;
                    break;
                }
            }
            file_put_contents($path, ltrim(implode("\n", $words), "\n"));

            return true;
        }

        $this->message = "The defined dictionary({$path}) does not contain this word({$word})";
        return false;
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

        preg_replace('/(\r\n)|\r/', "\n", $dictionaryContent);
        return explode("\n", $dictionaryContent);
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
}
