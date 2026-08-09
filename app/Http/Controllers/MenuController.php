<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\School;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MenuController extends Controller
{
    public function __construct(private GoogleDriveService $googleDrive) {}

    public function index(Request $request)
    {
        $this->authorizeOwner($request);

        return inertia('Owner/MenuManagement', [
            'menuItems' => MenuItem::with(['menuCategories', 'days', 'schools', 'sizes'])->latest()->get(),
            'categories' => MenuCategory::orderBy('name')->get(),
            'schools' => School::where('is_active', true)->orderBy('name')->get(),
            'previewSchoolId' => $request->filled('school_id')
                && School::whereKey($request->integer('school_id'))->where('is_active', true)->exists()
                    ? $request->integer('school_id')
                    : 'all',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);

        $validated = $this->validateMenu($request, true);
        $driveImage = $this->uploadImage($request);

        try {
            DB::transaction(function () use ($validated, $driveImage): void {
                $menuItem = MenuItem::create([
                    ...$this->menuAttributes($validated),
                    'image_drive_id' => $driveImage['id'],
                    'image_url' => $driveImage['url'],
                ]);

                $menuItem->menuCategories()->sync($validated['menu_category_ids']);
                $menuItem->schools()->sync($validated['school_ids']);
                $menuItem->days()->createMany($this->dayRecords($validated['days']));
                $this->syncSizes($menuItem, $validated['sizes'] ?? []);
            });
        } catch (Throwable $exception) {
            $this->deleteDriveImageQuietly($driveImage['id']);
            throw $exception;
        }

        return redirect()->route('owner.menu')->with('success', 'Meal added successfully.');
    }

    public function update(Request $request, MenuItem $menu): RedirectResponse
    {
        $this->authorizeOwner($request);

        $validated = $this->validateMenu($request, false);
        $newDriveImage = $request->hasFile('image') ? $this->uploadImage($request) : null;
        $oldDriveId = $menu->image_drive_id;

        try {
            DB::transaction(function () use ($menu, $validated, $newDriveImage): void {
                $attributes = $this->menuAttributes($validated);

                if ($newDriveImage) {
                    $attributes['image_drive_id'] = $newDriveImage['id'];
                    $attributes['image_url'] = $newDriveImage['url'];
                }

                $menu->update($attributes);
                $menu->menuCategories()->sync($validated['menu_category_ids']);
                $menu->schools()->sync($validated['school_ids']);
                $menu->days()->delete();
                $menu->days()->createMany($this->dayRecords($validated['days']));
                $this->syncSizes($menu, $validated['sizes'] ?? []);
            });
        } catch (Throwable $exception) {
            $this->deleteDriveImageQuietly($newDriveImage['id'] ?? null);
            throw $exception;
        }

        if ($newDriveImage) {
            $this->deleteDriveImageQuietly($oldDriveId);
        }

        return redirect()->route('owner.menu')->with('success', 'Meal updated successfully.');
    }

    public function destroy(Request $request, MenuItem $menu): RedirectResponse
    {
        $this->authorizeOwner($request);

        $driveId = $menu->image_drive_id;
        $menu->delete();
        $this->deleteDriveImageQuietly($driveId);

        return redirect()->route('owner.menu')->with('success', 'Meal deleted successfully.');
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('owner'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMenu(Request $request, bool $imageRequired): array
    {
        $requestedSizes = $request->input('sizes', []);
        $hasSmallSize = is_array($requestedSizes) && in_array('S', $requestedSizes, true);

        return $request->validate([
            'menu_category_ids' => ['required', 'array', 'min:1'],
            'menu_category_ids.*' => ['required', 'integer', 'distinct', Rule::exists('menu_categories', 'id')],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['required', 'integer', 'distinct', Rule::exists('schools', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'distinct', Rule::in([
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ])],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:'.($hasSmallSize ? '0.51' : '0.01'), 'max:99999999.99'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['required', 'distinct', Rule::in(['S', 'L'])],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_available' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function menuAttributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_available' => $validated['is_available'],
        ];
    }

    /**
     * @param  list<string>  $days
     * @return list<array{day_of_week: string}>
     */
    private function dayRecords(array $days): array
    {
        return array_map(
            fn (string $day): array => ['day_of_week' => $day],
            $days,
        );
    }

    /** @param list<string> $sizes */
    private function syncSizes(MenuItem $menuItem, array $sizes): void
    {
        $menuItem->sizes()->delete();

        if ($sizes !== []) {
            $records = [['size' => 'M', 'price_adjustment' => 0.00]];

            if (in_array('S', $sizes, true)) {
                $records[] = ['size' => 'S', 'price_adjustment' => -0.50];
            }

            if (in_array('L', $sizes, true)) {
                $records[] = ['size' => 'L', 'price_adjustment' => 0.50];
            }

            $menuItem->sizes()->createMany($records);
        }
    }

    /**
     * @return array{id: string, url: string}
     */
    private function uploadImage(Request $request): array
    {
        try {
            return $this->googleDrive->uploadPublicImage($request->file('image'));
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'image' => 'The image could not be uploaded to Google Drive. '.$exception->getMessage(),
            ]);
        }
    }

    private function deleteDriveImageQuietly(?string $fileId): void
    {
        try {
            $this->googleDrive->delete($fileId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
