<?php
/**
 * Created by Synida Pry.
 * Copyright © 2020. All rights reserved.
 */

namespace HunSpellPhpWrapper\validation;

use HunSpellPhpWrapper\HunSpell;
use HunSpellPhpWrapper\exception\InvalidResponseTypeException;
use HunSpellPhpWrapper\exception\InvalidThreadNumberException;

/**
 * Class InputValidator
 */
class InputValidator
{
    /**
     * Validates the thread number.
     *
     * @param mixed $threadNumber
     * @return void
     * @throws InvalidThreadNumberException
     * @author Synida Pry
     */
    public function validateThreadNumber($threadNumber)
    {
        // Validates the thread number parameter.
        if (!$this->isValidThreadNumber($threadNumber)) {
            throw new InvalidThreadNumberException(
                'Thread number must be a positive integer'
            );
        }
    }

    /**
     * Validates the response type
     *
     * @param mixed $responseType
     * @return void
     * @throws InvalidResponseTypeException
     * @author Synida Pry
     */
    public function validateResponseType($responseType)
    {
        // Checking if the response type is valid or not.
        if (!$this->isValidResponseType($responseType)) {
            throw new InvalidResponseTypeException(sprintf(
                'Response type(%s) is invalid. Use %s or %s instead',
                $responseType,
                HunSpell::JSON_RESPONSE,
                HunSpell::ARRAY_RESPONSE
            ));
        }
    }

    /**
     * Validates the thread number parameter.
     *
     * @param mixed $threadNumber
     * @return bool
     * @author Synida Pry
     */
    protected function isValidThreadNumber($threadNumber)
    {
        return is_int($threadNumber) && $threadNumber > 0;
    }

    /**
     * Checking if the response type is valid or not.
     *
     * @param mixed $responseType
     * @return bool
     * @author Synida Pry
     */
    protected function isValidResponseType($responseType)
    {
        return in_array($responseType, [HunSpell::ARRAY_RESPONSE, HunSpell::JSON_RESPONSE], true);
    }
}
