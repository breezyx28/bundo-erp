<?php

namespace Tests;

use App\Http\Middleware\EnsureOnboardingComplete;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Most feature tests assume a fully onboarded tenant; opt-in per test when needed.
        $this->withoutMiddleware(EnsureOnboardingComplete::class);
    }
}
