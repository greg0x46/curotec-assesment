<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id'       => User::factory(),
            'assigned_to_id' => null,
            'title'          => $this->faker->sentence,
            'description'    => $this->faker->paragraph,
            'priority'       => $this->faker->randomElement(TaskPriority::cases())->value,
            'due_date'       => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'status'         => $this->faker->randomElement(TaskStatus::cases())->value,
        ];
    }
}
