<?php

namespace Apiato\Repository\Events;

class RepositoryEntityUpdated extends RepositoryEventBase
{
    protected string $action = "updated";
}
