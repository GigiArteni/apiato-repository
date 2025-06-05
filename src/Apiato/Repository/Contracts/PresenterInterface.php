<?php

namespace Apiato\Repository\Contracts;

/**
 * Presenter Interface
 * Defines the contract for data presentation
 */
interface PresenterInterface
{
    /**
     * Prepare data to present
     *
     * @param mixed $data
     * @return mixed
     */
    public function present($data);
}
