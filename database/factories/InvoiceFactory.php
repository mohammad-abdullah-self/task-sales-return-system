<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->name(),
            'invoice_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'taxable_amount' => 0,
            'discount_amount' => 0,
            'vat_amount' => 0,
            'total_amount' => 0,
            'status' => $this->faker->randomElement(['paid', 'unpaid']),
        ];
    }
}
