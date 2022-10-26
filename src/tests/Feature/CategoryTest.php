<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setUp();
        $this->createTestCategory();
    }

    /**
     * Test `/categories`
     *
     * @return void
     */
    public function test_getCategories()
    {
        $this->getJson('categories')->assertJsonFragment([
            'uuid' => self::TESTING_CATEGORY_UUID,
            'name' => self::TESTING_CATEGORY_NAME,
        ]);
    }
}
