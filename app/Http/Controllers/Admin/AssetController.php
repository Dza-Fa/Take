<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAssetRequest;
use App\Models\Asset;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'condition' => ['nullable', Rule::in(['baik', 'perbaikan', 'rusak'])],
            'search' => ['nullable', 'string', 'max:255'],
        ], [
            'category_id.integer' => 'Kategori yang dipilih tidak valid.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'condition.in' => 'Kondisi aset yang dipilih tidak valid.',
            'search.string' => 'Pencarian harus berupa teks.',
            'search.max' => 'Pencarian maksimal 255 karakter.',
        ]);

        $assetsQuery = Asset::query()->with('category');

        if (filled($filters['category_id'] ?? null)) {
            $assetsQuery->where('category_id', $filters['category_id']);
        }

        if (filled($filters['condition'] ?? null)) {
            $assetsQuery->where('condition', $filters['condition']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = $filters['search'];

            $assetsQuery->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $assets = $assetsQuery
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.assets.index', compact('assets', 'categories', 'filters'));
    }

    public function create(): View
    {
        return view('admin.assets.form', [
            'asset' => new Asset,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(SaveAssetRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $image = $request->file('image');
        unset($attributes['image']);

        $attributes['code'] = filled($attributes['code'] ?? null)
            ? Str::upper($attributes['code'])
            : $this->generateAssetCode();

        if ($image !== null) {
            $attributes['image'] = $this->storeImage($image);
        }

        Asset::create($attributes);

        return redirect()->route('admin.assets.index')
            ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function edit(Asset $asset): View
    {
        return view('admin.assets.form', [
            'asset' => $asset,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(SaveAssetRequest $request, Asset $asset): RedirectResponse
    {
        $attributes = $request->validated();
        $image = $request->file('image');
        unset($attributes['image']);

        $attributes['code'] = filled($attributes['code'] ?? null)
            ? Str::upper($attributes['code'])
            : $asset->code;

        $oldImage = $asset->image;

        if ($image !== null) {
            $attributes['image'] = $this->storeImage($image);
        }

        $asset->update($attributes);

        if ($image !== null && $oldImage !== null) {
            $this->deleteImage($oldImage);
        }

        return redirect()->route('admin.assets.index')
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $image = $asset->image;
        $asset->delete();

        if ($image !== null) {
            $this->deleteImage($image);
        }

        return redirect()->route('admin.assets.index')
            ->with('success', 'Aset berhasil dihapus.');
    }

    private function generateAssetCode(): string
    {
        $existingCodes = Asset::query()
            ->where('code', 'like', 'TAKE-AST-%')
            ->pluck('code')
            ->all();
        $nextSequence = 1;

        foreach ($existingCodes as $existingCode) {
            if (preg_match('/^TAKE-AST-(\d+)$/', $existingCode, $matches) === 1) {
                $nextSequence = max($nextSequence, (int) $matches[1] + 1);
            }
        }

        do {
            $code = sprintf('TAKE-AST-%03d', $nextSequence);
            $nextSequence++;
        } while (in_array($code, $existingCodes, true));

        return $code;
    }

    private function storeImage(UploadedFile $image): string
    {
        $path = $image->storePublicly('assets', 'public');

        if ($path === false) {
            throw new RuntimeException('Foto aset gagal diunggah. Silakan coba kembali.');
        }

        return $path;
    }

    private function deleteImage(string $path): void
    {
        if (! Storage::disk('public')->delete($path)) {
            throw new RuntimeException('Foto aset gagal dihapus dari penyimpanan.');
        }
    }
}
