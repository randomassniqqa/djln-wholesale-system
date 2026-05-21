<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-30 days', 'now');
        $dueDate = fake()->dateTimeBetween($startDate, '+180 days');

        return [
            'name' => fake()->catchPhrase(),
            'description' => fake()->text(200),
            'manager_id' => User::factory()->projectManager(),  // ✅ FIX: This now returns a User instance, Eloquent handles it
            'status' => fake()->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'start_date' => $startDate,
            'due_date' => $dueDate,
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the project is in planning.
     */
    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
        ]);
    }

    /**
     * Indicate that the project is on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_hold',
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
