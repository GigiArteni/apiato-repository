<?php

namespace Apiato\Repository\Events;

class RepositoryEntityDeleting extends RepositoryEventBase
{
    protected string $action = "deleting";
}
