<?php

namespace Uwla\Lacl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = \Uwla\Lacl\Models\Permission::class;

    const genericModelNames = [
        "article", "comment", "course", "ebook", "playlist", "product", "tag",
        "user", "video"
    ];

    const actions = [
        "create", "delete", "deleteAny", "forceDelete", "forceDeleteAny",
        "update", "updateAny", "view", "viewAny",
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $modelName = $this->faker->randomElement($this::genericModelNames);
        $action = $this->faker->randomElement($this::actions);
        $model = "Uwla\\Lacl\\Models\\" . strtoupper($modelName);
        $name = $modelName . "." . $action;

        return [
            'name' => $name,
            'model' => $model,
        ];
    }
}
