<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

namespace HunSpellPhpWrapper\config;

use HunSpellPhpWrapper\Installer;

$maxThreads = Installer::configureMaxThreads();

return [
    'MAX_THREADS' => $maxThreads,
    'MIN_WORD_PER_THREAD' => Installer::configureWordPerThreadRatio($maxThreads)
];
