<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BengkelPic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BengkelPicController extends Controller
{
    public function avatar(BengkelPic $bengkel_pic): Response
    {
        abort_unless(
            $bengkel_pic->avatar_path && Storage::disk('public')->exists($bengkel_pic->avatar_path),
            404
        );

        return response()->file(
            Storage::disk('public')->path($bengkel_pic->avatar_path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($bengkel_pic->avatar_path) ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.basename($bengkel_pic->avatar_path).'"',
            ]
        );
    }

    public function index(): View
    {
        $pics = BengkelPic::query()->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.bengkel-pics.index', compact('pics'));
    }

    public function create(): View
    {
        return view('admin.bengkel-pics.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store('bengkel-pics', 'public');
        }

        BengkelPic::create($validated);

        return redirect()
            ->route('admin.bengkel-pics.index', $this->indexQuery($request))
            ->with('status', 'PIC berhasil ditambahkan.');
    }

    public function edit(BengkelPic $bengkel_pic): View
    {
        return view('admin.bengkel-pics.edit', compact('bengkel_pic'));
    }

    public function update(Request $request, BengkelPic $bengkel_pic): RedirectResponse
    {
        $validated = $this->validateData($request, $bengkel_pic);

        if ($request->hasFile('avatar')) {
            if ($bengkel_pic->avatar_path) {
                Storage::disk('public')->delete($bengkel_pic->avatar_path);
            }

            $validated['avatar_path'] = $request->file('avatar')->store('bengkel-pics', 'public');
        }

        $bengkel_pic->update($validated);

        return redirect()
            ->route('admin.bengkel-pics.index', $this->indexQuery($request))
            ->with('status', 'PIC berhasil diperbarui.');
    }

    public function destroy(Request $request, BengkelPic $bengkel_pic): RedirectResponse
    {
        if ($bengkel_pic->avatar_path) {
            Storage::disk('public')->delete($bengkel_pic->avatar_path);
        }

        $bengkel_pic->delete();

        return redirect()
            ->route('admin.bengkel-pics.index', $this->indexQuery($request))
            ->with('status', 'PIC berhasil dihapus.');
    }

    private function validateData(Request $request, ?BengkelPic $pic = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:bengkel_pics,name'.($pic ? ','.$pic->id : ''),
            ],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'avatar_position_x' => ['nullable', 'integer', 'between:0,100'],
            'avatar_position_y' => ['nullable', 'integer', 'between:0,100'],
        ]);

        $validated['avatar_position_x'] = (int) ($validated['avatar_position_x'] ?? $pic?->avatar_position_x ?? 50);
        $validated['avatar_position_y'] = (int) ($validated['avatar_position_y'] ?? $pic?->avatar_position_y ?? 50);

        return $validated;
    }

    /**
     * @return array<string, scalar>
     */
    private function indexQuery(Request $request): array
    {
        return collect($request->only('page'))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }
}
