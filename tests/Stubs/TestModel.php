<?php

declare(strict_types=1);

namespace Apiato\Repository\Tests\Stubs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'status'];

    protected static function newFactory()
    {
        return new TestModelFactory();
    }
}
