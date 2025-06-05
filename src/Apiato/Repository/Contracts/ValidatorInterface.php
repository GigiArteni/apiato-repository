<?php

namespace Apiato\Repository\Contracts;

/**
 * Validator Interface
 * Defines the contract for data validation
 */
interface ValidatorInterface
{
    const RULE_CREATE = 'create';
    const RULE_UPDATE = 'update';

    /**
     * Set data to validate
     *
     * @param array $input
     * @return $this
     */
    public function with(array $input);

    /**
     * Validate data for create action
     *
     * @return bool
     */
    public function passesCreate();

    /**
     * Validate data for update action
     *
     * @return bool
     */
    public function passesUpdate();

    /**
     * Validate data for given action
     *
     * @param string|null $action
     * @return bool
     */
    public function passes($action = null);

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors();
}
