<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

namespace HunSpellPhpWrapper\helper;

use Exception;
use HunSpellPhpWrapper\exception\InvalidResponseTypeException;
use HunSpellPhpWrapper\exception\InvalidThreadNumberException;
use HunSpellPhpWrapper\HunSpell;

/**
 * Class ConfigurationHelper
 * @package HunSpellPhpWrapper\helper
 *
 * @property string[] $correctWords
 * @property string[] $incorrectWords
 */
class ConfigurationHelper
{
    /**
     * Max thread we are going to predict with
     *
     * @const int
     */
    const MAX_THREAD = 128;

    /**
     * Max optimal word per thread we are going to predict with
     *
     * @const int
     */
    const OPTIMAL_WORD_PER_THREAD = 128;

    /**
     * Average typist error rate
     *
     * @const float
     */
    const TYPIST_ERROR_RATE = 0.91;

    /**
     * Contains random correct words
     *
     * @var string[]
     */
    protected $correctWords = [
        'banana',
        'papaya',
        'peach',
        'mango',
        'tomato'
    ];

    /**
     * Contains random correct words
     *
     * @var string[]
     */
    protected $incorrectWords = [
        'ranom',
        'potate',
        'asdasd',
        'noot',
        'lolipop'
    ];

    /**
     * Tries to get the CPU thread number form the environment variables.
     *
     * @return int
     * @author Synida Pry
     */
    public function getCPUThreadNumberFromEnvVariables()
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            $processorCount = getenv('NUMBER_OF_PROCESSORS');
        } else {
            $processorCount = getenv('_NPROCESSORS_ONLN');
        }

        return $processorCount;
    }

    /**
     * Tries to get the CPU thread number form the CLI.
     *
     * @return int
     * @author Synida Pry
     */
    public function getCPUThreadNumberFromCli()
    {
        $processorCount = null;
        if (function_exists('shell_exec')) {
            if (stripos(PHP_OS, 'WIN') === 0) {
                $response = trim(shell_exec('wmic cpu get NumberOfLogicalProcessors/Format:List'));
                $responseParts = explode('=', $response);
                $processorCount = isset($responseParts[1]) ? $responseParts[1] : null;
            } else {
                $processorCount = shell_exec('grep -c ^processor /proc/cpuinfo');
            }
        }

        return $processorCount;
    }

    /**
     * Tries to predict the CPU thread number
     *
     * @return int
     * @throws InvalidResponseTypeException
     * @throws InvalidThreadNumberException
     * @throws Exception
     * @author Synida Pry
     */
    public function predictCPUThreadNumber()
    {
        $spellChecker = new HunSpell('en_US.utf8', 'en_US', HunSpell::ARRAY_RESPONSE, 1);

        // Generates a text from the incorrect words.
        $text = $this->generateText();

        $performances = [];
        for ($i = 1; $i < static::MAX_THREAD; $i++) {
            $performances[$i] = $this->measurePerformance($spellChecker, $text, $i);

            if (min($performances) < $performances[$i]) {
                return $performances[array_search(min($performances), $performances, true)];
            }
        }

        return 1;
    }

    /**
     * Generates a text from the incorrect words.
     *
     * @param int $length
     * @param bool $useCorrectWords
     * @return string
     * @author Synida Pry
     */
    protected function generateText($length = -1, $useCorrectWords = false)
    {
        if ($length === -1) {
            $length = static::OPTIMAL_WORD_PER_THREAD;
        }

        $words = $useCorrectWords ? $this->correctWords : $this->incorrectWords;

        $text = '';
        $wordCount = count($words);
        for ($i = 0; $i < $length; $i++) {
            $text .= $words[$i % $wordCount] . ' ';
        }

        return $text;
    }

    /**
     * Measures the performance
     *
     * @param HunSpell $spellChecker
     * @param string $text
     * @param int $threads
     * @return double
     * @throws InvalidThreadNumberException
     * @throws Exception
     * @author Synida Pry
     */
    protected function measurePerformance($spellChecker, $text, $threads = 1)
    {
        // Sets the optimal thread number.
        $spellChecker->setMaxThreads((int)$threads);

        $before = microtime(true);
        $spellChecker->suggest($text);
        $after = microtime(true);

        return ($after - $before);
    }

    /**
     * Tries to predict the minimal optimal word per thread ratio.
     *
     * @param int $threads
     * @return int
     * @throws InvalidResponseTypeException
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public function predictOptimalWordPerThreadRatio($threads)
    {
        $spellChecker = new HunSpell('en_US.utf8', 'en_US', HunSpell::ARRAY_RESPONSE);

        $singlePerformanceIncorrectWords = $threadPerformanceIncorrectWords = 0;
        for ($j = $threads; $j < static::OPTIMAL_WORD_PER_THREAD; $j++) {
            $text = $this->generateText($j * $threads);
            $singlePerformanceIncorrectWords = $this->measurePerformance($spellChecker, $text, 1);

            $threadPerformanceIncorrectWords = $this->measurePerformance($spellChecker, $text, $threads);
            if ($threadPerformanceIncorrectWords < $singlePerformanceIncorrectWords) {
                break;
            }
        }
        $incorrectWeight = abs($threadPerformanceIncorrectWords - $singlePerformanceIncorrectWords / $threads);

        $singlePerformance = $threadPerformance = 0;
        for ($i = $threads; $i < static::OPTIMAL_WORD_PER_THREAD; $i++) {
            // Generates a text from the incorrect words.
            $text = $this->generateText($i * $threads, true);
            $singlePerformance = $this->measurePerformance($spellChecker, $text, 1);

            $threadPerformance = $this->measurePerformance($spellChecker, $text, $threads);

            if ($threadPerformance < $singlePerformance) {
                break;
            }
        }
        $correctWeight = abs($threadPerformance - $singlePerformance / $threads);
        $totalWeight = $correctWeight + $incorrectWeight;

        $correctWeight /= $totalWeight;
        $incorrectWeight /= $totalWeight;

        $result =  (int)(($i * static::TYPIST_ERROR_RATE * $incorrectWeight
                + $j * (1 - static::TYPIST_ERROR_RATE) * $correctWeight) / $threads / 2);

        return $result < 1 ? 1 : $result;
    }
}
