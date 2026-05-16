<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific
| PHPUnit test case class. By default, that class is "PHPUnit\Framework\TestCase".
| Of course, you may need to change it using the "uses()" function to bind a
| different classes or traits to your test cases.
|
*/

/*
|--------------------------------------------------------------------------
| Test Groups
|--------------------------------------------------------------------------
|
| Test groups are defined in Pest.php files within each test directory.
| You can run specific groups using: vendor/bin/pest --group=group-name
|
| Available groups:
| - feature: All feature tests
| - unit: All unit tests
| - commands: Command tests
| - middleware: Middleware tests
| - models: Model tests
| - services: Service tests
| - concerns: Trait/Concern tests
| - discovery: Discovery service tests
|
*/

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain
| conditions. The "expect()" function gives you access to a set of "expectations"
| methods that you can use to assert different things. Of course, you may extend
| the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code
| specific to your project that you don't want to repeat in every file. Here
| you can also expose helpers as global functions to help you to reduce the
| number of lines of code in your test files.
|
*/

function createTestUser(array $attributes = [])
{
    return test()->createUser($attributes);
}

function createTestSuperAdmin(array $attributes = [])
{
    return test()->createSuperAdmin($attributes);
}
