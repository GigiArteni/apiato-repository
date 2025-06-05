<?php

namespace Apiato\Repository\Traits;

use Apiato\Repository\Contracts\PresenterInterface;

/**
 * Presentable Trait
 * Provides presentation functionality to any class
 */
trait PresentableTrait
{
    protected ?PresenterInterface $presenter = null;

    /**
     * Set Presenter
     */
    public function setPresenter(PresenterInterface $presenter)
    {
        $this->presenter = $presenter;
        return $this;
    }

    /**
     * Get Presenter
     */
    public function presenter()
    {
        if (isset($this->presenter) && $this->presenter instanceof PresenterInterface) {
            return $this->presenter->present($this);
        }

        return $this;
    }

    /**
     * Present data using the configured presenter
     */
    public function present($data = null)
    {
        $data = $data ?? $this;
        
        if (isset($this->presenter) && $this->presenter instanceof PresenterInterface) {
            return $this->presenter->present($data);
        }

        return $data;
    }
}
