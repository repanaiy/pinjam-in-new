<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Siswa->value,
            'nis' => fake()->unique()->numerify('2024####'),
            'kelas' => 'XII IPA 1',
            'phone' => fake()->numerify('0812########'),
            'is_active' => true,
            'late_count' => 0,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => Role::Admin->value,
            'nis' => null,
            'kelas' => null,
            'employee_code' => fake()->unique()->bothify('ADM-###'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
