<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Simulate a separate login when a test switches accounts, preserving same-account session checks. */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $name = $guard ?? config('auth.defaults.guard');
        $current = $this->app['auth']->guard($name)->user();
        if ($current && $current->getAuthIdentifier() !== $user->getAuthIdentifier()) {
            $this->app['session']->forget('password_hash_'.$name);
        }

        return parent::actingAs($user, $guard);
    }
}
