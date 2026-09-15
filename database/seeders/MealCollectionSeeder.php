<?php

namespace Database\Seeders;

use App\Models\Children;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MealCollectionSeeder extends Seeder
{
    private const SCHOOL_NAME = 'SJK (C) Batu 11 Cheras';

    private const QR_CODE = 'demo-meal-collection-batu-11';

    private const DEMO_DESCRIPTION = 'Chicken Bihun meal-collection status demonstration.';

    /**
     * Seed a linked parent and student with paid meal-collection demo records.
     */
    public function run(): void
    {
        $school = School::query()->where('name', self::SCHOOL_NAME)->first();

        if (! $school) {
            throw new RuntimeException('Run SchoolSeeder before MealCollectionSeeder.');
        }

        DB::transaction(function () use ($school): void {
            $parent = User::query()->updateOrCreate(
                ['phone_number' => '0123456899'],
                [
                    'name' => 'Adrian Lim',
                    'username' => null,
                    'email' => 'adrian.lim@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'school_id' => null,
                    'is_active' => true,
                ],
            );

            $student = Student::query()->updateOrCreate(
                ['qr_code' => self::QR_CODE],
                [
                    'school_id' => $school->id,
                    'name' => 'Aiden Lim',
                    'class' => '4 Bestari',
                    'guardian_phone_number' => $parent->phone_number,
                ],
            );

            Children::query()->updateOrCreate(
                ['student_id' => $student->id],
                ['user_id' => $parent->id],
            );

            $today = Carbon::now('Asia/Kuala_Lumpur')->startOfDay();
            $day = strtolower($today->format('l'));
            $order = $this->demoOrder($parent, $today);
            $total = 0;

            // Remove this seeder's previous time slots so it can be safely rerun.
            $order->orderItems()->delete();
            MenuItem::query()->whereIn('description', [
                self::DEMO_DESCRIPTION,
                'Five-minute meal-collection slot for system demonstration.',
            ])->delete();

            $chickenBihun = MenuItem::query()
                ->where('name', 'Chicken Bihun (Morning)')
                ->first(['image_drive_id', 'image_url']);

            // DEMO TIME SETTINGS: Change the setTime(hour, minute) values below if the
            // presentation is delayed, then rerun only this seeder. The grace period
            // starts after the second time and ends 15 minutes later. For example,
            // an end time of 14:40 gives a grace period until 14:55.
            $slots = [
                ['Missed Demo', $today->copy()->setTime(13, 0), $today->copy()->setTime(14, 0)],
                ['Grace Period Demo', $today->copy()->setTime(14, 20), $today->copy()->setTime(14, 40)],
                ['Upcoming Demo', $today->copy()->setTime(15, 30), $today->copy()->setTime(16, 30)],
            ];

            foreach ($slots as [$statusLabel, $start, $end]) {
                $menuItem = MenuItem::query()->updateOrCreate(
                    ['name' => 'Chicken Bihun ('.$statusLabel.')'],
                    [
                        'description' => self::DEMO_DESCRIPTION,
                        'price' => 5.00,
                        'available_start_time' => $start->format('H:i:s'),
                        'available_end_time' => $end->format('H:i:s'),
                        'image_drive_id' => $chickenBihun?->image_drive_id,
                        'image_url' => $chickenBihun?->image_url,
                        'is_available' => true,
                        'calories_kcal' => 290,
                        'protein_g' => 22,
                        'carbohydrates_g' => 35,
                        'fat_g' => 3,
                        'fibre_g' => 2,
                        'sodium_mg' => 340,
                    ],
                );

                $menuItem->schools()->syncWithoutDetaching([$school->id]);
                $menuItem->days()->updateOrCreate(
                    ['day_of_week' => $day],
                    [],
                );

                OrderItem::query()->updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'student_id' => $student->id,
                        'menu_item_id' => $menuItem->id,
                        'scheduled_time' => $start,
                    ],
                    [
                        'unit_price' => $menuItem->price,
                        'quantity' => 1,
                    ],
                );

                $total += (float) $menuItem->price;
            }

            $order->update(['total_amount' => $total, 'order_status' => 'paid']);
            Payment::query()->updateOrCreate(
                ['stripe_session_id' => 'cs_test_meal_collection_demo_'.$today->format('Ymd')],
                [
                    'order_id' => $order->id,
                    'user_id' => $parent->id,
                    'amount' => $total,
                    'payment_date' => $today->copy()->subDay()->setTime(18, 0),
                    'method' => 'stripe',
                    'status' => 'paid',
                    'stripe_payment_intent_id' => 'pi_test_meal_collection_demo_'.$today->format('Ymd'),
                ],
            );
        });
    }

    private function demoOrder(User $parent, Carbon $today): Order
    {
        $sessionId = 'cs_test_meal_collection_demo_'.$today->format('Ymd');
        $payment = Payment::query()->where('stripe_session_id', $sessionId)->first();

        if ($payment) {
            return Order::query()->findOrFail($payment->order_id);
        }

        return Order::query()->create([
            'user_id' => $parent->id,
            'total_amount' => 0,
            'order_status' => 'paid',
        ]);
    }
}
