<?php

namespace Apiato\Repository\Events;

class RepositoryEntityDeleted extends RepositoryEventBase
{
    protected string $action = "deleted";
}
