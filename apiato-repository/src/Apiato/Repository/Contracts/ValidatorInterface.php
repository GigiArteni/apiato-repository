<?php

namespace Apiato\Repository\Contracts;

/**
 * 100% Compatible with l5-repository ValidatorInterface
 */
interface ValidatorInterface
{
    const RULE_CREATE = 'create';
    const RULE_UPDATE = 'update';

    public function with(array $input);
    public function passesCreate();
    public function passesUpdate();
    public function passes($action = null);
    public function errors();
}
