<?php

namespace Apiato\Repository\Contracts;

/**
 * Presentable Interface
 * Defines the contract for objects that can be presented
 */
interface Presentable
{
    /**
     * Set Presenter
     *
     * @param PresenterInterface $presenter
     * @return mixed
     */
    public function setPresenter(PresenterInterface $presenter);

    /**
     * Get Presenter
     *
     * @return mixed
     */
    public function presenter();
}
