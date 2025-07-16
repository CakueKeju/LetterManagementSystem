<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Division;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use thiagoalessio\TesseractOCR\TesseractOCR;

class SuratController extends Controller
{
    // Show the upload form
    public function showUploadForm()
    {
        $divisions = Division::all();
        $jenisSurat = JenisSurat::active()->get();
        return view('surat.upload', compact('divisions', 'jenisSurat'));
    }

    // Handle file upload and OCR extraction
    public function handleUpload(Request $request)
    {
        $request->validate([
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'deskripsi' => 'required|string',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png',
        ]);

        // Enforce unique nomor_urut per divisi
        if (Surat::where('nomor_urut', $request->nomor_urut)->where('divisi_id', $request->divisi_id)->exists()) {
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada di divisi ini.'])->withInput();
        }

        $file = $request->file('file');
        $path = $file->store('letters');
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // Generate kode_surat
        $division = Division::find($request->divisi_id);
        $jenisSurat = JenisSurat::find($request->jenis_surat_id);
        $kodeSurat = sprintf('%03d/%s/%s', $request->nomor_urut, $division->kode_divisi, $jenisSurat->kode_jenis);

        // OCR extraction (for images, not PDFs)
        $extracted = '';
        if (in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
            $ocr = new TesseractOCR($file->getRealPath());
            $extracted = $ocr->run();
        }

        return view('surat.confirm', [
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'kode_surat' => $kodeSurat,
            'extracted_text' => $extracted,
            'input' => $request->all(),
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::active()->get(),
        ]);
    }

    // Show confirmation form for user to verify/correct code
    public function showConfirmForm(Request $request)
    {
        return view('surat.confirm', [
            'file_path' => $request->input('file_path'),
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('mime_type'),
            'kode_surat' => $request->input('kode_surat'),
            'extracted_text' => $request->input('extracted_text'),
            'input' => $request->all(),
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::active()->get(),
        ]);
    }

    // Store the confirmed/corrected data
    public function store(Request $request)
    {
        $request->validate([
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'deskripsi' => 'required|string',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'file_path' => 'required',
            'file_size' => 'required|integer',
            'mime_type' => 'required|string',
            'kode_surat' => 'required|string',
        ]);

        // Enforce unique nomor_urut per divisi
        if (Surat::where('nomor_urut', $request->nomor_urut)->where('divisi_id', $request->divisi_id)->exists()) {
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada di divisi ini.'])->withInput();
        }

        Surat::create([
            'nomor_urut' => $request->input('nomor_urut'),
            'kode_surat' => $request->input('kode_surat'),
            'divisi_id' => $request->input('divisi_id'),
            'jenis_surat_id' => $request->input('jenis_surat_id'),
            'deskripsi' => $request->input('deskripsi'),
            'tanggal_surat' => $request->input('tanggal_surat'),
            'tanggal_diterima' => $request->input('tanggal_diterima'),
            'file_path' => $request->input('file_path'),
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('mime_type'),
            'is_private' => $request->input('is_private', false),
            'uploaded_by' => Auth::id(),
        ]);

        return redirect()->route('surat.upload')->with('success', 'Surat uploaded successfully!');
    }

    // List all letters with filters
    public function index(Request $request)
    {
        $query = Surat::query();

        // Filtering
        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }
        if ($request->filled('jenis_surat_id')) {
            $query->where('jenis_surat_id', $request->jenis_surat_id);
        }
        if ($request->filled('tanggal_surat')) {
            $query->where('tanggal_surat', $request->tanggal_surat);
        }
        if ($request->filled('is_private')) {
            $query->where('is_private', $request->is_private);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $letters = $query->paginate(15);

        return view('surat.index', [
            'letters' => $letters,
            'filters' => $request->only(['divisi_id', 'jenis_surat_id', 'tanggal_surat', 'is_private', 'sort']),
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::active()->get(),
        ]);
    }
} 