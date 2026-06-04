<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Download::latest()->get());
    }

    public function store(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json([
                'message' => 'File tidak terdeteksi oleh server. Pastikan ukuran file tidak melebihi batasan PHP (upload_max_filesize).',
                'errors' => ['file' => ['File is missing in request']]
            ], 422);
        }

        if (!$request->file('file')->isValid()) {
            return response()->json([
                'message' => 'File upload tidak valid: ' . $request->file('file')->getErrorMessage(),
                'errors' => ['file' => [$request->file('file')->getErrorMessage()]]
            ], 422);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:downloads,slug',
            'version' => 'nullable|string|max:50',
            'file' => 'required|file',
            'platform' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('downloads', 'public');
            $data['file_path'] = $path;
        }

        $download = \App\Models\Download::create(collect($data)->except('file')->all());

        return response()->json($download, 201);
    }

    public function update(Request $request, $id)
    {
        $download = \App\Models\Download::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:downloads,slug,' . $id,
            'version' => 'nullable|string|max:50',
            'file' => 'nullable|file',
            'platform' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            if ($download->file_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($download->file_path);
            }
            $path = $request->file('file')->store('downloads', 'public');
            $data['file_path'] = $path;
        }

        $download->update(collect($data)->except('file')->all());

        return response()->json($download);
    }

    public function destroy($id)
    {
        $download = \App\Models\Download::findOrFail($id);
        if ($download->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($download->file_path);
        }
        $download->delete();

        return response()->json(['message' => 'Download deleted']);
    }
}
