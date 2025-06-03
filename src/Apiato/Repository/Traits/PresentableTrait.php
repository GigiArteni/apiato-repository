<?php

namespace Apiato\Repository\Traits;

use Apiato\Repository\Contracts\PresenterInterface;

/**
 * Trait PresentableTrait - l5-repository compatible
 */
trait PresentableTrait
{
    protected ?PresenterInterface $presenter = null;

    public function setPresenter(PresenterInterface $presenter)
    {
        $this->presenter = $presenter;
        return $this;
    }

    public function presenter()
    {
        if (isset($this->presenter) && $this->presenter instanceof PresenterInterface) {
            return $this->presenter->present($this);
        }

        return $this;
    }
}
