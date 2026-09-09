<?php

namespace Tests\Feature\Integration\Support;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Children;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class IntegrationFixtures
{
    public static function school(array $attributes = []): School
    {
        return School::create(array_merge([
            'name' => 'School '.Str::uuid(),
            'address' => 'Test address '.Str::uuid(),
            'ordering_deadline_day' => 'saturday',
            'is_active' => true,
        ], $attributes));
    }

    public static function user(string $role, ?School $school = null, array $attributes = []): User
    {
        $token = Str::lower(Str::random(10));

        return User::create(array_merge([
            'name' => ucfirst($role).' Test',
            'username' => $role === 'operator' ? 'operator_'.$token : null,
            'email' => $role === 'operator' ? null : $role.'_'.$token.'@example.test',
            'password' => Hash::make('password'),
            'phone_number' => '012'.random_int(1000000, 9999999),
            'role' => $role,
            'school_id' => $school?->id,
            'is_active' => true,
        ], $attributes));
    }

    public static function student(School $school, array $attributes = []): Student
    {
        return Student::create(array_merge([
            'school_id' => $school->id,
            'name' => 'Student '.Str::random(6),
            'class' => '1 Amanah',
            'guardian_phone_number' => null,
            'qr_code' => (string) Str::uuid(),
        ], $attributes));
    }

    public static function child(User $customer, Student $student): Children
    {
        return Children::create([
            'user_id' => $customer->id,
            'student_id' => $student->id,
        ]);
    }

    public static function menu(School $school, array $attributes = [], array $days = ['monday']): MenuItem
    {
        $menu = MenuItem::create(array_merge([
            'name' => 'Meal '.Str::random(6),
            'description' => 'Integration test meal',
            'price' => 4.50,
            'available_start_time' => '10:00',
            'available_end_time' => '11:00',
            'image_drive_id' => 'drive-'.Str::random(8),
            'image_url' => 'https://example.test/meal.png',
            'is_available' => true,
            'calories_kcal' => 500,
            'protein_g' => 20,
            'carbohydrates_g' => 70,
            'fat_g' => 12,
            'fibre_g' => 5,
            'sodium_mg' => 600,
        ], $attributes));

        $menu->schools()->attach($school->id);
        foreach ($days as $day) {
            $menu->days()->create(['day_of_week' => $day]);
        }

        $category = MenuCategory::create(['name' => 'Category '.Str::uuid()]);
        $menu->menuCategories()->attach($category->id);
        $menu->components()->create([
            'component_type' => 'staple',
            'name' => 'Rice',
            'amount_g' => 150,
            'sort_order' => 0,
        ]);

        return $menu;
    }

    /** @return array{Order, OrderItem, Payment} */
    public static function orderItem(
        User $customer,
        Student $student,
        MenuItem $menu,
        Carbon|string $scheduledAt,
        int $quantity = 1,
        string $paymentStatus = 'paid',
    ): array {
        $scheduledAt = $scheduledAt instanceof Carbon ? $scheduledAt : Carbon::parse($scheduledAt, 'Asia/Kuala_Lumpur');
        $total = (float) $menu->price * $quantity;
        $order = Order::create([
            'user_id' => $customer->id,
            'total_amount' => $total,
            'order_status' => $paymentStatus === 'paid' ? 'paid' : 'pending',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'student_id' => $student->id,
            'menu_item_id' => $menu->id,
            'unit_price' => $menu->price,
            'scheduled_time' => $scheduledAt,
            'quantity' => $quantity,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => $total,
            'payment_date' => now(),
            'method' => 'stripe',
            'status' => $paymentStatus,
        ]);

        return [$order, $item, $payment];
    }

    public static function cartItem(User $customer, Student $student, MenuItem $menu, Carbon|string $scheduledAt, int $quantity = 1): CartItem
    {
        $cart = Cart::firstOrCreate(['user_id' => $customer->id]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'student_id' => $student->id,
            'menu_item_id' => $menu->id,
            'quantity' => $quantity,
            'scheduled_time' => $scheduledAt,
        ]);
    }
}
