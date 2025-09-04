<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class UploadController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Upload::query();
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        $uploads = $query->latest()->get();
        return response()->json($uploads);
    }

    public function show(Upload $upload)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $upload->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($upload);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = in_array($extension, ['pdf']) ? 'pdf' : 'image';

        $path = $file->store('uploads');

        $extractedText = null;
        try {
            if ($fileType === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile(Storage::path($path));
                $extractedText = trim($pdf->getText());
            } else {
                // Image OCR - requires tesseract OCR binary installed in system path
                $imagePath = Storage::path($path);
                $extractedText = trim((new TesseractOCR($imagePath))->run());
            }
        } catch (\Throwable $e) {
            $extractedText = null; // fail gracefully
        }

        $upload = Upload::create([
            'user_id' => $user->id,
            'original_filename' => $originalName,
            'file_path' => $path,
            'file_type' => $fileType,
            'extracted_text' => $extractedText,
        ]);

        return response()->json($upload, 201);
    }

    public function destroy(Upload $upload)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $upload->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        Storage::delete($upload->file_path);
        $upload->delete();
        return response()->json(null, 204);
    }
}

