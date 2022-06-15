<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ulid\Ulid;
use Database\Seeders\CategorySeeder;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $title = $this->faker->optional(0.8)->text(45);
        $content = $this->faker->optional(0.8)->text(1024);

        if($title && $content){
            $is_draft = $this->faker->boolean();
            $is_publish = $this->faker->boolean();
        }else{
            // タイトルとコンテントがどちらか null の場合は公開状態にならない
            $is_draft = true;
            $is_publish = false;
        }


        return [
            'ulid' => Ulid::generate(false),
            'category_uuid' => CategorySeeder::CATEGORIES[rand(0,count(CategorySeeder::CATEGORIES)-1)]['uuid'],
            'title' => $title,
            'content' => $content,
            'is_draft' => $is_draft,
            'is_publish' => $is_publish,
            'is_deleted' => $this->faker->boolean(),
        ];
    }
}
