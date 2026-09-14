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
        $filters = $request->only(['search', 'category_id', 'filter_school_id', 'day', 'is_available']);

        return inertia('Owner/MenuManagement', [
            'menuItems' => MenuItem::query()
                ->with(['menuCategories', 'days', 'schools', 'components'])
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = '%'.$request->string('search')->trim().'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('description', 'like', $search)->orWhere('id', $request->input('search')));
                })
                ->when($request->filled('category_id'), fn ($query) => $query->whereHas('menuCategories', fn ($query) => $query->whereKey($request->integer('category_id'))))
                ->when($request->filled('filter_school_id'), fn ($query) => $query->whereHas('schools', fn ($query) => $query->whereKey($request->integer('filter_school_id'))))
                ->when($request->filled('day'), fn ($query) => $query->whereHas('days', fn ($query) => $query->where('day_of_week', $request->input('day'))))
                ->when($request->filled('is_available'), fn ($query) => $query->where('is_available', $request->input('is_available') === 'yes'))
                ->latest()->get(),
            'categories' => MenuCategory::orderBy('name')->get(),
            'schools' => School::orderBy('name')->get(),
            'filters' => $filters,
            'editMenuId' => $request->filled('edit') ? $request->integer('edit') : null,
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
                $this->syncComponents($menuItem, $validated['components']);
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
                $this->syncComponents($menu, $validated['components']);
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
        return $request->validate([
            'menu_category_ids' => ['required', 'array', 'min:1'],
            'menu_category_ids.*' => ['required', 'integer', 'distinct', Rule::exists('menu_categories', 'id')],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['required', 'integer', 'distinct', Rule::exists('schools', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'distinct', Rule::in([
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ])],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'available_start_time' => ['nullable', 'date_format:H:i', 'required_with:available_end_time'],
            'available_end_time' => ['nullable', 'date_format:H:i', 'required_with:available_start_time', 'after:available_start_time'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_available' => ['required', 'boolean'],
            'calories_kcal' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'protein_g' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'carbohydrates_g' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'fat_g' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'fibre_g' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'sodium_mg' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.component_type' => ['required', Rule::in(['staple', 'protein', 'vegetable', 'fruit', 'other'])],
            'components.*.name' => ['required', 'string', 'max:255'],
            'components.*.amount_g' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
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
            'available_start_time' => $validated['available_start_time'] ?? null,
            'available_end_time' => $validated['available_end_time'] ?? null,
            'is_available' => $validated['is_available'],
            'calories_kcal' => $validated['calories_kcal'],
            'protein_g' => $validated['protein_g'],
            'carbohydrates_g' => $validated['carbohydrates_g'],
            'fat_g' => $validated['fat_g'],
            'fibre_g' => $validated['fibre_g'],
            'sodium_mg' => $validated['sodium_mg'],
        ];
    }

    /** @param list<array{component_type: string, name: string, amount_g: numeric-string|float|int}> $components */
    private function syncComponents(MenuItem $menuItem, array $components): void
    {
        $menuItem->components()->delete();
        $menuItem->components()->createMany(array_map(
            fn (array $component, int $index): array => [...$component, 'sort_order' => $index],
            $components,
            array_keys($components),
        ));
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
