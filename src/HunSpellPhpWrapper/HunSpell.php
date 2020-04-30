<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

namespace HunSpellPhpWrapper;

use Closure;
use Exception;
use HunSpellPhpWrapper\config\Configuration;
use HunSpellPhpWrapper\exception\InvalidResponseTypeException;
use HunSpellPhpWrapper\exception\InvalidThreadNumberException;
use HunSpellPhpWrapper\validation\InputValidator;
use parallel\Channel;
use parallel\Error;
use parallel\Runtime;

/**
 * Class HunSpell
 * @package HunSpellPhpWrapper
 *
 * @property int $maxThreads
 * @property int $minWordPerThread
 * @property string $dictionaries
 * @property string $encoding
 * @property string $responseType
 * @property Closure $findCommandClosure
 * @property InputValidator $inputValidator
 */
class HunSpell
{
    /**
     * Misspelled word with suggestions
     *
     * @const string
     */
    const MISSED = '&';

    /**
     * Incorrect word - no suggestions
     *
     * @const string
     */
    const INCORRECT = '#';

    /**
     * Dictionary stem
     *
     * @const string
     */
    const STEM = '*';

    /**
     * Affixed forms of the following dictionary stem
     *
     * @const string
     */
    const AFFIXED_FORM = '+';

    /**
     * The array index key for the misspelled word
     *
     * @const string
     */
    const MISSPELLED_WORD_KEY = 'm';

    /**
     * The array index key for the missed word
     *
     * @const string
     */
    const WORD_POSITION_KEY = 'p';

    /**
     * The array index key for the suggestion count
     *
     * @const string
     */
    const SUGGESTION_COUNT_KEY = 'c';

    /**
     * The array index key for the suggestions
     *
     * @const string
     */
    const SUGGESTIONS_KEY = 's';

    /**
     * Json response type
     *
     * @const string
     */
    const JSON_RESPONSE = 'json';

    /**
     * Array response type
     *
     * @const string
     */
    const ARRAY_RESPONSE = 'array';

    /**
     * Selected dictionary or dictionaries separated by coma(,)
     *
     * @var string
     */
    protected $dictionaries = 'en_GB';

    /**
     * Encoding of the text
     *
     * @var string
     */
    protected $encoding = 'en_GB.utf8';

    /**
     * Response type of the wrapper, can be array or json
     *
     * @var string
     */
    protected $responseType = 'json';

    /**
     * Contains the find command closure
     *
     * @var Closure
     */
    protected $findCommandClosure;

    /**
     * Contains the maximum thread number we can work with
     *
     * @var int
     */
    protected $maxThreads;

    /**
     * Minimal word a thread should work with for the optimal performance before creating new thread
     *
     * @var int
     */
    protected $minWordPerThread;

    /**
     * @var InputValidator
     */
    protected $inputValidator;

    /**
     * HunSpell constructor.
     *
     * @param string $encoding
     * @param string $dictionaries
     * @param string $responseType
     * @param int $threads
     * @param int $wordThreadRatio
     * @throws InvalidResponseTypeException
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public function __construct(
        $encoding = 'en_GB.utf8',
        $dictionaries = 'en_GB',
        $responseType = 'json',
        $threads = 1,
        $wordThreadRatio = 1
    ) {
        $this->inputValidator = new InputValidator();

        // Validates the response type
        $this->inputValidator->validateResponseType($responseType);

        // Validates the thread number.
        $this->inputValidator->validateThreadNumber($threads);

        $this->responseType = $responseType;
        $this->encoding = $encoding;
        $this->dictionaries = $dictionaries;

        $this->maxThreads = $threads ?: Configuration::MAX_THREADS;
        $this->minWordPerThread = $wordThreadRatio ?: Configuration::MIN_WORD_PER_THREAD;

        $this->findCommandClosure = extension_loaded('parallel')
            ? static function (Channel $channel, $text, $encoding, $dictionaries) {
                $encode = strncasecmp(PHP_OS, 'WIN', 3) === 0 ? '' : "LANG=\"{$encoding}\"; ";

                $channel->send(shell_exec("{$encode}echo \"{$text}\" | hunspell -d \"{$dictionaries}\""));
            } : null;
    }

    /**
     * Returns with the response type.
     *
     * @return string
     * @author Synida Pry
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * Sets the response type.
     *
     * @param string $responseType
     * @return void
     * @throws InvalidResponseTypeException
     * @author Synida Pry
     */
    public function setResponseType($responseType)
    {
        // Validates the response type
        $this->inputValidator->validateResponseType($responseType);

        $this->responseType = $responseType;
    }

    /**
     * Returns with the selected dictionaries
     *
     * @return string
     * @author Synida Pry
     */
    public function getDictionaries()
    {
        return $this->dictionaries;
    }

    /**
     * Sets the dictionary/dictionaries
     *
     * @param string $dictionaries
     * @return void
     * @author Synida Pry
     */
    public function setDictionaries($dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    /**
     * Returns with the encoding
     *
     * @return string
     * @author Synida Pry
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Sets the encoding.
     *
     * @param string $encoding
     * @return void
     * @author Synida Pry
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * Tries to find words from the dictionary. Returns only with the suggestions if any of them is incorrect.
     *
     * @param string $text
     * @return array|string
     * @throws Exception
     * @author Synida Pry
     */
    public function suggest($text)
    {
        $result = [];

        $text = str_replace('"', '', $text);

        // Counts the words in the text.
        $wordCount = $this->getWordCount($text);

        $spellCheckResults =
            extension_loaded('parallel') && $this->maxThreads > 1 && $wordCount > $this->minWordPerThread
                // Splits the text into smaller chunks and process them with threads.
                ? $this->findWithThreading($text, $wordCount)
                // Executes the find command on a text.
                : $this->findCommand($text);

        preg_replace('/(\r\n)|\r|\n/', "\n", $spellCheckResults);
        $resultLines = explode("\n", trim($spellCheckResults));
        unset($resultLines[0]);

        foreach ($resultLines as $line) {
            if (!isset($line[0]) || !in_array($line[0], [self::INCORRECT, self::MISSED], true)) {
                continue;
            }

            if ($line[0] === self::INCORRECT) {
                $lineParts = explode(' ', $line);

                if (!isset($lineParts[1], $lineParts[2])) {
                    continue;
                }

                $result[] = [
                    self::MISSPELLED_WORD_KEY => $lineParts[1],
                    self::WORD_POSITION_KEY => $lineParts[2]
                ];

                continue;
            }

            $lineParts = explode(':', $line);
            if (!isset($lineParts[0], $lineParts[1])) {
                continue;
            }

            $frontParts = explode(' ', $lineParts[0]);
            if (count($frontParts) < 4) {
                continue;
            }

            $result[] = [
                self::MISSPELLED_WORD_KEY => $frontParts[1],
                self::SUGGESTION_COUNT_KEY => $frontParts[2],
                self::WORD_POSITION_KEY => $frontParts[3],
                self::SUGGESTIONS_KEY => explode(', ', trim($lineParts[1]))
            ];
        }

        return $this->responseType === self::JSON_RESPONSE
            ? json_encode($result)
            : $result;
    }

    /**
     * Counts the words in the text.
     *
     * @param string $text
     * @return int
     * @author Synida Pry
     */
    public function getWordCount($text)
    {
        return str_word_count($text);
    }

    /**
     * Splits the text into smaller chunks and process them with threads.
     *
     * @param string $text
     * @param int $wordCount
     * @return string
     * @throws Exception
     * @author Synida Pry
     */
    protected function findWithThreading($text, $wordCount)
    {
        // Returns with the configured max thread count.
        $optimalThread = $this->getOptimalThreads($wordCount);

        $words = explode(' ', $text);
        $chunkSize = (int)ceil($wordCount / $optimalThread);

        $channel = new Channel();

        $result = '';
        $threads = [];
        try {
            $threadCount = (int)ceil(
                $wordCount / $chunkSize > $optimalThread ? $optimalThread : $wordCount / $chunkSize
            );
            for ($i = 0; $i < $threadCount; $i++) {
                $chunk = '';
                $initPosition = $i * $chunkSize;
                $endPosition = ($i + 1) * $chunkSize;
                for ($j = $initPosition; $j < $endPosition; $j++) {
                    if (!isset($words[$j])) {
                        break;
                    }
                    $chunk .= $words[$j] . ' ';
                }

                $threads[] = (new Runtime())
                    ->run($this->findCommandClosure, [$channel, $chunk, $this->encoding, $this->dictionaries]);
            }

            for ($i = 0; $i < $threadCount; $i++) {
                $result .= $channel->recv();
            }

            $channel->close();
        } catch (Error $error) {
            throw new Error($error->getMessage());
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        return $result;
    }

    /**
     * Returns with the optimal thread count.
     *
     * @param int $wordCount
     * @return int
     * @author Synida Pry
     */
    public function getOptimalThreads($wordCount)
    {
        return $wordCount / $this->minWordPerThread >= $this->maxThreads
            ? $this->maxThreads : (int)$wordCount / $this->minWordPerThread;
    }

    /**
     * Executes the find command on a text.
     *
     * @param string $text
     * @return string
     * @author Synida Pry
     */
    protected function findCommand($text)
    {
        $encode = strncasecmp(PHP_OS, 'WIN', 3) === 0 ? '' : "LANG=\"{$this->encoding}\"; ";

        return shell_exec("{$encode}echo \"{$text}\" | hunspell -d \"{$this->dictionaries}\"");
    }

    /**
     * Returns with the max threads
     *
     * @return int
     * @author Synida Pry
     */
    public function getMaxThreads()
    {
        return $this->maxThreads;
    }

    /**
     * Sets the optimal thread number.
     *
     * @param int $maxThreads
     * @return void
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public function setMaxThreads($maxThreads)
    {
        // Validates the thread number.
        $this->inputValidator->validateThreadNumber($maxThreads);

        $this->maxThreads = $maxThreads;
    }
}
