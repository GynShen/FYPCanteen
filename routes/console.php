<?php

use App\Models\OrderItem;
use App\Models\StudentMealEvent;
use App\Notifications\CustomerEventNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('meals:notify-missed', function () {
    $now = Carbon::now('Asia/Kuala_Lumpur');

    OrderItem::query()
        ->whereDate('scheduled_time', '<=', $now->toDateString())
        ->whereHas('order.payment', fn ($query) => $query->where('status', 'paid'))
        ->whereDoesntHave('studentMealEvents', fn ($query) => $query->where('event_type', 'missed_collection'))
        ->with(['menuItem:id,name,available_end_time', 'student.child.user', 'collection'])
        ->chunkById(100, function ($items) use ($now): void {
            foreach ($items as $item) {
                $date = $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString();
                $endTime = $item->menuItem?->available_end_time ?: '23:59:59';
                $deadline = Carbon::parse($date.' '.$endTime, 'Asia/Kuala_Lumpur')->addMinutes(15);
                $collected = (int) ($item->collection?->collected_quantity ?? 0);

                if ($now->lte($deadline) || $collected >= (int) $item->quantity) {
                    continue;
                }

                $event = StudentMealEvent::firstOrCreate([
                    'order_item_id' => $item->id,
                    'event_type' => 'missed_collection',
                ], [
                    'student_id' => $item->student_id,
                    'meal_collection_id' => $item->collection?->id,
                    'reported_at' => $now,
                ]);

                if ($event->wasRecentlyCreated) {
                    $remaining = (int) $item->quantity - $collected;
                    $item->student->child?->user?->notify(new CustomerEventNotification([
                        'event' => 'meal_missed',
                        'title' => 'Meal missed',
                        'message' => $item->student->name.' missed '.$remaining.' × '.$item->menuItem->name.'. The collection period and 15-minute grace period have ended.',
                        'student_id' => $item->student_id,
                        'order_item_id' => $item->id,
                    ]));
                }
            }
        });

    $this->info('Missed meal notifications checked.');
})->purpose('Notify parents about meals not collected before the grace period ended');

Schedule::command('meals:notify-missed')->everyMinute()->withoutOverlapping();
