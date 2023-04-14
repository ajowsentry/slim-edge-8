<?php

declare(strict_types=1);

namespace SlimEdge\Support;

final class CharacterList
{
    /**
     * Alphanumeric without uppercase letters
     */
    public const AlnumLower = '1234567890qwertyuiopasdfghjklzxcvbnm';
    
    /**
     * Aphanumeric with uppercase
     */
    public const Alnum = '1234567890QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm';

    /**
     * Numeric characters
     */
    public const Numeric = '1234567890';

    /**
     * Alphanumeric without uppercase letters and i,o,s,l
     */
    public const AlnumAlternative = '1234567890qwertyupadfghjkzxcvbnm';

    /**
     * Special characters
     */
    public const SpecialChars = '~!@#$%^&*()_+=-`[]\\;\',./{}|:"<>?';

    private function __construct() { }
}