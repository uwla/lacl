<?php

namespace Tests\App\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = \Tests\App\Models\Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'author' => $this->faker->name(),
            'title' => $this->faker->sentence(),
            'body' => $this->faker->text()
        ];
    }
}
