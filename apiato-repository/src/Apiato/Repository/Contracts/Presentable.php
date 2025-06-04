<?php

namespace Apiato\Repository\Contracts;

/**
 * 100% Compatible with l5-repository Presentable
 */
interface Presentable
{
    public function setPresenter(PresenterInterface $presenter);
    public function presenter();
}
