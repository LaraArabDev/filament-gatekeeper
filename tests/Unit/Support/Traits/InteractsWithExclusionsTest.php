<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Traits;

use LaraArabDev\FilamentGatekeeper\Support\Traits\InteractsWithExclusions;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConcreteExclusionClass
{
    use InteractsWithExclusions;

    protected array $exclusions = [];

    public function setExclusions(array $exclusions): void
    {
        $this->exclusions = $exclusions;
    }

    public function filter(array $items): array
    {
        return $this->filterExclusions($items, $this->exclusions);
    }

    protected function getExclusionList(): array
    {
        return $this->exclusions;
    }
}

class InteractsWithExclusionsTest extends TestCase
{
    #[Test]
    public function it_returns_all_items_when_exclusion_list_is_empty(): void
    {
        $obj = new ConcreteExclusionClass;
        $items = ['UserResource', 'PostResource', 'CommentResource'];

        $result = $obj->filter($items);

        $this->assertSame($items, array_values($result));
    }

    #[Test]
    public function it_filters_out_items_matching_exclusion(): void
    {
        $obj = new ConcreteExclusionClass;
        $obj->setExclusions(['App\Filament\Resources\PostResource']);
        $items = ['UserResource', 'PostResource', 'CommentResource'];

        $result = array_values($obj->filter($items));

        $this->assertContains('UserResource', $result);
        $this->assertNotContains('PostResource', $result);
        $this->assertContains('CommentResource', $result);
    }

    #[Test]
    public function it_filters_multiple_exclusions(): void
    {
        $obj = new ConcreteExclusionClass;
        $obj->setExclusions([
            'App\Filament\Resources\PostResource',
            'App\Filament\Resources\CommentResource',
        ]);
        $items = ['UserResource', 'PostResource', 'CommentResource'];

        $result = array_values($obj->filter($items));

        $this->assertSame(['UserResource'], $result);
    }

    #[Test]
    public function it_returns_empty_array_when_all_items_excluded(): void
    {
        $obj = new ConcreteExclusionClass;
        $obj->setExclusions(['App\Resources\UserResource']);
        $items = ['UserResource'];

        $result = $obj->filter($items);

        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_empty_array_when_given_empty_items(): void
    {
        $obj = new ConcreteExclusionClass;
        $obj->setExclusions(['SomeResource']);

        $result = $obj->filter([]);

        $this->assertEmpty($result);
    }
}
