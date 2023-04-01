<?php

declare(strict_types=1);

namespace ValidatorNamespace;

use Respect\Validation\Rules;

class ValidatorName extends Rules\AllOf
{
    private bool $initialized = false;

    public function init(): void
    {
        if(!$this->initialized) {
            // Implementation

            $this->initialized = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate($input): bool
    {
        $this->init();
        return parent::validate($input);
    }

    /**
     * {@inheritdoc}
     */
    public function check($input): void
    {
        $this->init();
        parent::check($input);
    }
}