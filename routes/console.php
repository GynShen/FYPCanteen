<?php

use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\StudentMealEvent;
use App\Notifications\CustomerEventNotification;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('drive:purge-menu-images {--force : Delete without asking for confirmation}', function () {
    $imageCount = MenuItem::query()->whereNotNull('image_drive_id')->count();

    if ($imageCount === 0) {
        $this->info('No Google Drive menu images were found.');

        return Command::SUCCESS;
    }

    $this->warn("This will permanently delete {$imageCount} menu image(s) from Google Drive.");

    if (! $this->option('force') && ! $this->confirm('Do you want to continue?')) {
        $this->info('No images were deleted.');

        return Command::SUCCESS;
    }

    $googleDrive = app(GoogleDriveService::class);
    $deleted = 0;
    $failed = 0;

    MenuItem::query()
        ->whereNotNull('image_drive_id')
        ->select(['id', 'image_drive_id'])
        ->chunkById(50, function ($menuItems) use ($googleDrive, &$deleted, &$failed): void {
            foreach ($menuItems as $menuItem) {
                try {
                    $googleDrive->delete($menuItem->image_drive_id);
                    $menuItem->forceFill([
                        'image_drive_id' => null,
                        'image_url' => null,
                    ])->save();
                    $deleted++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error("Menu item {$menuItem->id}: {$exception->getMessage()}");
                }
            }
        });

    $this->info("Deleted {$deleted} Google Drive menu image(s).");

    if ($failed > 0) {
        $this->warn("Failed to delete {$failed} image(s). Their database references were kept.");

        return Command::FAILURE;
    }

    return Command::SUCCESS;
})->purpose('Delete all Google Drive images referenced by menu items');

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
