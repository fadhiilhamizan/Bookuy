<?php

namespace Tests;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeCategory(): Category
    {
        return Category::firstOrCreate(['name' => 'Umum'], ['icon_path' => 'images/icon-kategori.png']);
    }

    protected function makeBook(?User $seller = null, array $attrs = []): Book
    {
        $seller = $seller ?: User::factory()->create();
        $category = $this->makeCategory();

        return Book::factory()->create(array_merge([
            'user_id'     => $seller->id,
            'category_id' => $category->id,
        ], $attrs));
    }
}
