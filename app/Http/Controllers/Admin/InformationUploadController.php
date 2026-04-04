<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminInformationFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class InformationUploadController extends Controller
{
    public function index(): View
    {
        $files = AdminInformationFile::with('uploader')
            ->whereIn('type', AdminInformationFile::allowedTypes())
            ->latest('id')
            ->get();

        $roleOptions = [
            User::ROLE_USER => User::roleLabels()[User::ROLE_USER],
            User::ROLE_PKM => User::roleLabels()[User::ROLE_PKM],
            User::ROLE_APPROVER => User::roleLabels()[User::ROLE_APPROVER],
        ];

        $caraKerja = [];
        foreach ($roleOptions as $roleKey => $label) {
            $caraKerja[$roleKey] = $files
                ->where('type', AdminInformationFile::TYPE_CARA_KERJA)
                ->where('role', $roleKey)
                ->values();
        }

        return view('admin.information-upload.index', [
            'roleOptions' => $roleOptions,
            'caraKerja' => $caraKerja,
            'flowchartFiles' => $files
                ->where('type', AdminInformationFile::TYPE_FLOWCHART_APLIKASI)
                ->values(),
            'kontrakFiles' => $files
                ->where('type', AdminInformationFile::TYPE_KONTRAK_PKM)
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(AdminInformationFile::allowedTypes())],
            'role' => [
                Rule::requiredIf(fn () => $request->input('type') === AdminInformationFile::TYPE_CARA_KERJA),
                'nullable',
                Rule::in([User::ROLE_USER, User::ROLE_PKM, User::ROLE_APPROVER]),
            ],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,svg,doc,docx,xls,xlsx,ppt,pptx', 'max:10240'],
        ]);

        $type = $validated['type'];
        $role = $type === AdminInformationFile::TYPE_CARA_KERJA ? ($validated['role'] ?? null) : null;
        $defaults = AdminInformationFile::defaults()[$type];

        foreach ($request->file('files', []) as $uploadedFile) {
            $path = $uploadedFile->store(
                'admin-information/'.$type.'/'.($role ?: 'global'),
                'public'
            );

            AdminInformationFile::create([
                'type' => $type,
                'role' => $role,
                'title' => $defaults['title'],
                'description' => $defaults['description'],
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $uploadedFile->getClientMimeType(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('admin.information-upload.index')
            ->with('success', 'File informasi berhasil diunggah.');
    }

    public function preview(AdminInformationFile $informationUpload): StreamedResponse
    {
        abort_unless($informationUpload->file_path && Storage::disk('public')->exists($informationUpload->file_path), 404);

        return Storage::disk('public')->response(
            $informationUpload->file_path,
            $informationUpload->original_name ?? basename($informationUpload->file_path),
            ['Content-Disposition' => 'inline; filename="'.$this->safeFilename($informationUpload->original_name).'";']
        );
    }

    public function download(AdminInformationFile $informationUpload): StreamedResponse
    {
        abort_unless($informationUpload->file_path && Storage::disk('public')->exists($informationUpload->file_path), 404);

        return Storage::disk('public')->download(
            $informationUpload->file_path,
            $informationUpload->original_name ?? basename($informationUpload->file_path)
        );
    }

    public function destroy(AdminInformationFile $informationUpload): RedirectResponse
    {
        if ($informationUpload->file_path) {
            Storage::disk('public')->delete($informationUpload->file_path);
        }

        $informationUpload->delete();

        return redirect()
            ->route('admin.information-upload.index')
            ->with('success', 'File informasi berhasil dihapus.');
    }

    private function safeFilename(?string $filename): string
    {
        $filename = trim((string) $filename);

        return $filename !== '' ? str_replace('"', '', $filename) : 'document';
    }
}
