<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

namespace HunSpellPhpWrapper;

use Composer\Script\Event;
use HunSpellPhpWrapper\exception\InvalidResponseTypeException;
use HunSpellPhpWrapper\exception\InvalidThreadNumberException;
use HunSpellPhpWrapper\helper\ConfigurationHelper;

/**
 * Class Installer
 * @package HunSpellPhpWrapper
 */
class Installer
{
    /**
     * @param Event $event
     */
    public static function configure(Event $event)
    {
        $event->getIO()->write('Running post-update hooks.');

        $templatePath = __DIR__ . '/config/config-template.txt';
        $configurationOutputPath = __DIR__ . '/config/Configuration.php';

        $template = file_get_contents($templatePath);

        $phpConstants = '';
        $constantNames = include(__DIR__ . '/config/constants.php');
        foreach ($constantNames as $constantName => $value) {
            $constantRows[] = "    const {$constantName} = {$value};";
            $phpConstants = implode("\r\n", $constantRows);
        }

        $phpContents = strtr($template, [
            '__CONSTANTS__' => $phpConstants
        ]);

        if (file_put_contents($configurationOutputPath, $phpContents) === false) {
            $event->getIO()->writeError("Can't write content into {$configurationOutputPath}");
        }

        $event->getIO()->write('Done and done.');
    }

    /**
     * Checking the available CPU threads from the env variables
     *
     * @return int
     * @author Synida Pry
     */
    public static function configureMaxThreads()
    {
        if (!extension_loaded('parallel')) {
            return 1;
        }

        $methods = [
            // Tries to get the CPU thread number form the CLI.
            'getCPUThreadNumberFromCli',
            // Tries to get the CPU thread number form the environment variables.
            'getCPUThreadNumberFromEnvVariables',
            // Tries to predict the CPU thread number
            'predictCPUThreadNumber'
        ];

        $configurationHelper = new ConfigurationHelper();
        foreach ($methods as $method) {
            $processorCount = $configurationHelper->{$method}();

            if (is_numeric($processorCount) && $processorCount > 0) {
                return $processorCount;
            }
        }

        return 1;
    }

    /**
     * Configures the minimum optimal word per thread ratio.
     *
     * @param int $threads
     * @return int
     * @throws InvalidResponseTypeException
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public static function configureWordPerThreadRatio($threads = 2)
    {
        if (!extension_loaded('parallel')) {
            return 1;
        }

        $configurationHelper = new ConfigurationHelper();

        // Tries to predict the minimal optimal word per thread ratio.
        return $configurationHelper->predictOptimalWordPerThreadRatio($threads);
    }
}
