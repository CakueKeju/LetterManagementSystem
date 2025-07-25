<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\Division;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use App\Models\NomorUrutLock;
use PhpOffice\PhpWord\TemplateProcessor;
use setasign\Fpdi\Fpdi;

class SuratController extends Controller
{
    // Show the upload form
    public function showUploadForm()
    {
        $divisions = Division::all();
        $jenisSurat = JenisSurat::where('divisi_id', Auth::user()->divisi_id)->active()->get();
        return view('surat.upload', compact('divisions', 'jenisSurat'));
    }

    // Handle file upload and text extraction
    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx',
        ]);

        $file = $request->file('file');
        // Pastikan folder private/letters ada
        $lettersDir = storage_path('app/private/letters');
        if (!is_dir($lettersDir)) {
            if (!mkdir($lettersDir, 0777, true) && !is_dir($lettersDir)) {
                \Log::error('Gagal membuat folder: ' . $lettersDir);
                return back()->withErrors(['file_path' => 'Gagal membuat folder penyimpanan surat. Hubungi admin.']);
            }
        }
        $path = $file->store('private/letters');
        \Log::info('File uploaded to: ' . $path . ' | Exists: ' . (Storage::exists($path) ? 'yes' : 'no'));
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // Extract text based on file type
        $extracted = '';
        $ocrError = null;
        $extractionMethod = '';
        
        $fileExtension = strtolower($file->getClientOriginalExtension());
        
        try {
            switch ($fileExtension) {
                case 'pdf':
                    $extractionMethod = 'PDF Parser';
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $extracted = $pdf->getText();
                    break;
                    
                case 'doc':
                case 'docx':
                    $extractionMethod = 'Word Parser';
                    $phpWord = IOFactory::load($file->getRealPath());
                    $extracted = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $extracted .= $element->getText() . "\n";
                            }
                        }
                    }
                    break;
                    
                default:
                    $ocrError = 'Format file tidak didukung untuk ekstraksi teks.';
            }
            
            // Log the extracted text for debugging
            if (!empty($extracted)) {
                \Log::info('Text Extraction Result for file: ' . $file->getClientOriginalName(), [
                    'method' => $extractionMethod,
                    'extracted_length' => strlen($extracted),
                    'extracted_preview' => substr($extracted, 0, 200)
                ]);
            }
            
        } catch (\Exception $e) {
            $ocrError = 'Error ekstraksi teks (' . $extractionMethod . '): ' . $e->getMessage();
            \Log::error('Text Extraction Error: ' . $e->getMessage());
        }

        // Extract and pre-fill form data from file/extracted text
        $prefilledData = $this->extractFormData($file, $extracted);

        // Ambil nomor urut berikutnya (skip locked)
        $divisiId = Auth::user()->divisi_id;
        $jenisSuratId = $request->input('jenis_surat_id') ?? null;
        $nextNomorUrut = null;
        if ($divisiId && $jenisSuratId) {
            $nextNomorUrut = $this->getNextNomorUrut($divisiId, $jenisSuratId);
            if ($nextNomorUrut) {
                // Lock nomor urut untuk user ini selama 10 menit
                NomorUrutLock::updateOrCreate([
                    'divisi_id' => $divisiId,
                    'jenis_surat_id' => $jenisSuratId,
                    'nomor_urut' => $nextNomorUrut,
                ], [
                    'user_id' => Auth::id(),
                    'locked_until' => now()->addMinutes(10),
                ]);
            }
        }
        $prefilledData['nomor_urut'] = $nextNomorUrut;

        // Check for duplicate nomor_urut if we have divisi_id, jenis_surat_id, and nomor_urut
        if ($prefilledData['nomor_urut'] && $prefilledData['divisi_id'] && $prefilledData['jenis_surat_id']) {
            $duplicateCheck = $this->checkDuplicate($prefilledData['nomor_urut'], $prefilledData['divisi_id'], $prefilledData['jenis_surat_id']);
            if ($duplicateCheck['is_duplicate']) {
                // Jangan hapus file, biarkan user tetap bisa preview/konfirmasi
                return view('surat.duplicate_warning', [
                    'extracted_nomor_urut' => $prefilledData['nomor_urut'],
                    'extracted_divisi_id' => $prefilledData['divisi_id'],
                    'extracted_jenis_surat_id' => $prefilledData['jenis_surat_id'],
                    'available_numbers' => $duplicateCheck['available_numbers'],
                    'divisions' => Division::all(),
                    'jenisSurat' => JenisSurat::active()->get(),
                    'extracted_text' => $extracted,
                    'ocr_error' => $ocrError,
                    'extraction_method' => $extractionMethod,
                    'file_path' => $path,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                ]);
            }
        }

        // Show the form for user to fill/check, with file info and extracted text
        return view('surat.confirm', [
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'nomor_surat' => $prefilledData['nomor_surat'] ?? '',
            'extracted_text' => $extracted,
            'input' => $prefilledData,
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::where('divisi_id', Auth::user()->divisi_id)->active()->get(),
            'ocr_error' => $ocrError,
            'extraction_method' => $extractionMethod,
        ]);
    }

    // Show confirmation form for user to verify/correct code
    public function showConfirmForm(Request $request)
    {
        $jenisSurat = JenisSurat::where('divisi_id', Auth::user()->divisi_id)->active()->get();
        $divisiId = Auth::user()->divisi_id;
        $jenisSuratId = $request->input('jenis_surat_id');
        $nomorUrut = $request->input('nomor_urut');
        if ($divisiId && $jenisSuratId && $nomorUrut) {
            \App\Models\NomorUrutLock::updateOrCreate([
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'nomor_urut' => $nomorUrut,
            ], [
                'user_id' => \Auth::id(),
                'locked_until' => now()->addMinutes(10),
            ]);
        }
        return view('surat.confirm', [
            'file_path' => $request->input('file_path'),
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('mime_type'),
            'nomor_surat' => $request->input('nomor_surat'),
            'extracted_text' => $request->input('extracted_text'),
            'input' => $request->all(),
            'divisions' => Division::all(),
            'jenisSurat' => $jenisSurat,
        ]);
    }

    // Store the confirmed/corrected data
    public function store(Request $request)
    {
        // Pastikan nomor urut selalu integer (misal input '001' jadi 1)
        $request->merge([
            'nomor_urut' => (int) ltrim($request->input('nomor_urut'), '0')
        ]);

        $request->validate([
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'perihal' => 'required|string', // was 'deskripsi'
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date',
            'file_path' => 'required',
            'file_size' => 'required|integer',
            'mime_type' => 'required|string',
        ]);

        // Hapus lock nomor urut user ini (jika ada)
        NomorUrutLock::where('divisi_id', $request->divisi_id)
            ->where('jenis_surat_id', $request->jenis_surat_id)
            ->where('nomor_urut', $request->nomor_urut)
            ->where('user_id', Auth::id())
            ->delete();

        // Cek duplikasi nomor urut
        if (Surat::where('nomor_urut', $request->nomor_urut)
            ->where('divisi_id', $request->divisi_id)
            ->where('jenis_surat_id', $request->jenis_surat_id)
            ->exists()) {
            // Ambil data untuk form konfirmasi
            $divisions = \App\Models\Division::all();
            $jenisSurat = \App\Models\JenisSurat::active()->get();
            $input = $request->all();
            $input['nomor_urut'] = $request->nomor_urut;
            $input['divisi_id'] = $request->divisi_id;
            $input['jenis_surat_id'] = $request->jenis_surat_id;
            $input['perihal'] = $request->perihal; // was 'deskripsi'
            $input['tanggal_surat'] = $request->tanggal_surat;
            $input['tanggal_diterima'] = $request->tanggal_diterima;
            $input['is_private'] = $request->has('is_private');
            $file_path = $request->file_path;
            $file_size = $request->file_size;
            $mime_type = $request->mime_type;
            $nomor_surat = $request->nomor_surat;
            $extracted_text = $request->extracted_text ?? '';
            $extraction_method = $request->extraction_method ?? '';
            // Tampilkan warning di halaman konfirmasi
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada untuk jenis surat ini di divisi ini. Silakan pilih nomor lain.'])->withInput();
        }

        // Generate kode_surat
        $division = Division::find($request->divisi_id);
        $jenisSurat = JenisSurat::find($request->jenis_surat_id);
        $nomorSurat = sprintf('%s/%s/%s/INTENS/%s', $request->nomor_urut, $division->kode_divisi, $jenisSurat->kode_jenis, date('Y'));

        $filePath = $request->input('file_path');
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
            $request->nomor_urut,
            Division::find($request->divisi_id)->kode_divisi,
            JenisSurat::find($request->jenis_surat_id)->kode_jenis,
            date('m', strtotime($request->tanggal_surat)),
            date('Y', strtotime($request->tanggal_surat))
        );
        $finalPdfPath = 'private/letters/final_' . uniqid() . '.pdf';
        $outputPdfPath = storage_path('app/' . $finalPdfPath);
        $pythonScript = base_path('python/fill_nomor_surat.py');
        $cmd = escapeshellcmd("python3 $pythonScript '$filePath' '$outputPdfPath' '$nomorSurat'");
        $output = [];
        $return_var = 0;
        try {
            exec($cmd, $output, $return_var);
        } catch (\Throwable $e) {
            // If exec is not allowed, fallback (commented out)
            // return back()->withErrors(['file_path' => 'Server tidak mengizinkan eksekusi script Python. Hubungi admin.'])->withInput();
            return back()->withErrors(['file_path' => 'Server tidak mengizinkan eksekusi script Python. Hubungi admin.'])->withInput();
        }
        if ($return_var !== 0 || !file_exists($outputPdfPath)) {
            return back()->withErrors(['file_path' => 'Gagal generate file PDF akhir.'])->withInput();
        }
        $mimeType = 'application/pdf';
        $fileSize = filesize($outputPdfPath);

        // Create the surat
        $surat = Surat::create([
            'nomor_urut' => $request->input('nomor_urut'),
            'nomor_surat' => $nomorSurat, // was 'kode_surat'
            'divisi_id' => $request->input('divisi_id'),
            'jenis_surat_id' => $request->input('jenis_surat_id'),
            'perihal' => $request->input('perihal'), // was 'deskripsi'
            'tanggal_surat' => $request->input('tanggal_surat'),
            'tanggal_diterima' => $request->input('tanggal_diterima'),
            'file_path' => $finalPdfPath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'is_private' => $request->input('is_private', false),
            'uploaded_by' => Auth::id(),
        ]);

        // Handle private access if surat is private
        if ($request->input('is_private') && $request->has('selected_users')) {
            $selectedUsers = $request->input('selected_users', []);
            foreach ($selectedUsers as $userId) {
                \App\Models\SuratAccess::grantAccess($surat->id, $userId);
            }
        }

        return redirect()->route('home')->with('success', 'Surat berhasil diupload!');
    }

    // Get users for access selection
    public function getUsersForAccess(Request $request)
    {
        $search = $request->get('search', '');
        // Only non-admin users, no limit
        $users = \App\Models\User::where('id', '!=', Auth::id())
                    ->where('is_admin', false)
                    ->where(function($query) use ($search) {
                        $query->where('full_name', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy('full_name')
                    ->get(['id', 'full_name', 'username', 'email', 'divisi_id']);

        return response()->json($users);
    }

    // List all letters with filters
    public function index(Request $request)
    {
        $query = Surat::query();

        // Filter based on user access
        $user = Auth::user();
        
        // Show public surat from same division OR private surat that user has access to
        $query->where(function($q) use ($user) {
            // Public surat from same division
            $q->where(function($subQ) use ($user) {
                $subQ->where('is_private', false)
                     ->where('divisi_id', $user->divisi_id);
            });
            
            // OR private surat that user uploaded
            $q->orWhere('uploaded_by', $user->id);
            
            // OR private surat that user has access to
            $q->orWhereExists(function($existsQuery) use ($user) {
                $existsQuery->select(\DB::raw(1))
                           ->from('surat_access')
                           ->whereColumn('surat_access.surat_id', 'surat.id')
                           ->where('surat_access.user_id', $user->id);
            });
        });

        // Additional filtering
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

    // Extract form data from file or OCR text
    private function extractFormData($file, $extractedText)
    {
        $data = [
            'nomor_urut' => null,
            'divisi_id' => null,
            'jenis_surat_id' => null,
            'perihal' => '', // was 'deskripsi'
            'tanggal_surat' => date('Y-m-d'),
            'tanggal_diterima' => date('Y-m-d'), // Default to today
            'is_private' => false,
        ];

        if ($extractedText) {
            \Log::info('Extracted text for surat:', ['text' => $extractedText]);
        }
        // Try to extract nomor_urut and other data from extracted text
        if ($extractedText) {
            // Normalize text: remove double slashes, extra spaces
            $normalizedText = preg_replace('/\/\/+/', '/', $extractedText);
            $normalizedText = preg_replace('/\s+/', ' ', $normalizedText);

            // Improved pattern: allow for optional double slashes and extra spaces
            if (preg_match('/Nomor:\s*(\d+)\/([^\/\n]+)\/([^\/\n]+)\/INTENS\/?\/?(\d{4})/i', $normalizedText, $matches)) {
                $data['nomor_urut'] = trim($matches[1]);
                $divisiId = $this->findDivisiByKode(trim($matches[2]));
                $jenisId = $this->findJenisSuratByKode(trim($matches[3]));
                $data['divisi_id'] = $divisiId ?: null;
                $data['jenis_surat_id'] = $jenisId ?: null;
                $data['tanggal_surat'] = $this->extractDateFromText($normalizedText);
            }
            // Alternative pattern: "Nomor: 123/ABC/DEF/INTENS/2023" (without "Nomor:")
            elseif (preg_match('/(\d+)\/([^\/\n]+)\/([^\/\n]+)\/INTENS\/?\/?(\d{4})/i', $normalizedText, $matches)) {
                $data['nomor_urut'] = trim($matches[1]);
                $divisiId = $this->findDivisiByKode(trim($matches[2]));
                $jenisId = $this->findJenisSuratByKode(trim($matches[3]));
                $data['divisi_id'] = $divisiId ?: null;
                $data['jenis_surat_id'] = $jenisId ?: null;
                $data['tanggal_surat'] = $this->extractDateFromText($normalizedText);
            }
            // Simple number pattern if no structured format found
            if (!$data['nomor_urut'] && preg_match('/(\d+)/', $normalizedText, $matches)) {
                $data['nomor_urut'] = $matches[1];
            }
        }

        // Try to extract nomor_urut from filename if not found in text
        if (!$data['nomor_urut']) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            if (preg_match('/(\d+)/', $filename, $matches)) {
                $data['nomor_urut'] = $matches[1];
            }
        }

        // Try to extract dates from text if not already found
        if ($data['tanggal_surat'] === date('Y-m-d')) {
            $data['tanggal_surat'] = $this->extractDateFromText($extractedText);
        }

        // Try to match divisi and jenis_surat from text if not already found
        if (!$data['divisi_id'] && $extractedText) {
            $divisiId = $this->findDivisiFromText($extractedText);
            $data['divisi_id'] = $divisiId ?: null;
        }

        if (!$data['jenis_surat_id'] && $extractedText) {
            $jenisId = $this->findJenisSuratFromText($extractedText);
            $data['jenis_surat_id'] = $jenisId ?: null;
        }

        return $data;
    }

    // Helper method to find divisi by kode
    private function findDivisiByKode($kode)
    {
        $division = Division::where('kode_divisi', $kode)->first();
        return $division ? $division->id : null;
    }

    // Helper method to find jenis surat by kode
    private function findJenisSuratByKode($kode)
    {
        $jenisSurat = JenisSurat::where('kode_jenis', $kode)->first();
        return $jenisSurat ? $jenisSurat->id : null;
    }

    // Helper method to extract date from text
    private function extractDateFromText($text)
    {
        // Look for various date patterns
        $patterns = [
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/', // DD/MM/YYYY or DD-MM-YYYY
            '/(\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/', // YYYY/MM/DD or YYYY-MM-DD
            '/(\d{1,2}\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})/i', // DD Month YYYY
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $dateStr = $matches[1];
                
                // Try different date formats
                $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d'];
                foreach ($formats as $format) {
                    $date = \DateTime::createFromFormat($format, $dateStr);
                    if ($date) {
                        return $date->format('Y-m-d');
                    }
                }
                
                // Handle Indonesian month names
                if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $dateStr, $monthMatches)) {
                    $monthMap = [
                        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
                    ];
                    $month = $monthMap[strtolower($monthMatches[2])];
                    return sprintf('%04d-%02d-%02d', $monthMatches[3], $month, $monthMatches[1]);
                }
            }
        }
        
        return date('Y-m-d'); // Default to today if no date found
    }

    // Helper method to find divisi from text
    private function findDivisiFromText($text)
    {
        $divisions = Division::all();
        foreach ($divisions as $division) {
            // Match as whole word only
            if (preg_match('/\\b' . preg_quote($division->nama_divisi, '/') . '\\b/i', $text) ||
                preg_match('/\\b' . preg_quote($division->kode_divisi, '/') . '\\b/i', $text)) {
                return $division->id;
            }
        }
        return null;
    }

    // Helper method to find jenis surat from text
    private function findJenisSuratFromText($text)
    {
        $jenisSurat = JenisSurat::active()->get();
        foreach ($jenisSurat as $jenis) {
            // Match as whole word only
            if (preg_match('/\\b' . preg_quote($jenis->nama_jenis, '/') . '\\b/i', $text)) {
                \Log::info('Jenis surat match by nama_jenis', ['match' => $jenis->nama_jenis]);
                return $jenis->id;
            }
            if (preg_match('/\\b' . preg_quote($jenis->kode_jenis, '/') . '\\b/i', $text)) {
                \Log::info('Jenis surat match by kode_jenis', ['match' => $jenis->kode_jenis]);
                return $jenis->id;
            }
        }
        \Log::info('No jenis surat match found in text.');
        return null;
    }

    // Helper: Check for duplicate nomor_urut and get available numbers (per divisi & jenis surat)
    private function checkDuplicate($nomorUrut, $divisiId, $jenisSuratId)
    {
        $isDuplicate = Surat::where('nomor_urut', $nomorUrut)
                           ->where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
                           ->exists();

        $existingNumbers = Surat::where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
                               ->pluck('nomor_urut')
                               ->sort()
                               ->values();

        $availableNumbers = [];
        $nextNumber = 1;
        foreach ($existingNumbers as $existingNumber) {
            if ($existingNumber > $nextNumber) {
                for ($i = $nextNumber; $i < $existingNumber && count($availableNumbers) < 5; $i++) {
                    $availableNumbers[] = $i;
                }
            }
            $nextNumber = $existingNumber + 1;
        }
        while (count($availableNumbers) < 5) {
            $availableNumbers[] = $nextNumber++;
        }
        return [
            'is_duplicate' => $isDuplicate,
            'available_numbers' => $availableNumbers
        ];
    }

    // Helper: Get next available nomor urut (skip locked by others)
    private function getNextNomorUrut($divisiId, $jenisSuratId)
    {
        $usedNumbers = Surat::where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
            ->pluck('nomor_urut')
            ->toArray();
        $lockedNumbers = NomorUrutLock::where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
            ->where(function($q) {
                $q->whereNull('locked_until')->orWhere('locked_until', '>', now());
            })
            ->pluck('nomor_urut')
            ->toArray();
        $allUsed = array_unique(array_merge($usedNumbers, $lockedNumbers));
        for ($i = 1; $i <= 999; $i++) {
            if (!in_array($i, $allUsed)) {
                return $i;
            }
        }
        return null;
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file_path' => 'required',
            'nomor_urut' => 'required|integer',
            'divisi_id' => 'required|exists:divisions,id',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'tanggal_surat' => 'required|date',
        ]);
        $filePath = $request->input('file_path');
        $storagePath = storage_path('app/' . $filePath);
        \Log::info('Preview file path: ' . $storagePath . ' | Exists: ' . (file_exists($storagePath) ? 'yes' : 'no'));
        if (!file_exists($storagePath)) {
            return response('File tidak ditemukan: ' . $storagePath, 404);
        }
        $nomorSurat = sprintf('%03d/%s/%s/INTENS/%02d/%04d',
            $request->nomor_urut,
            Division::find($request->divisi_id)->kode_divisi,
            JenisSurat::find($request->jenis_surat_id)->kode_jenis,
            date('m', strtotime($request->tanggal_surat)),
            date('Y', strtotime($request->tanggal_surat))
        );
        $outputPdfPath = storage_path('app/private/letters/preview_' . uniqid() . '.pdf');
        $pythonScript = base_path('python/fill_nomor_surat.py');
        $cmd = escapeshellcmd("python3 $pythonScript '$storagePath' '$outputPdfPath' '$nomorSurat'");
        $output = [];
        $return_var = 0;
        try {
            exec($cmd . ' 2>&1', $output, $return_var);
        } catch (\Throwable $e) {
            return response('Server tidak mengizinkan eksekusi script Python. Hubungi admin. Error: ' . $e->getMessage(), 500);
        }
        if ($return_var !== 0 || !file_exists($outputPdfPath)) {
            return response('Gagal generate preview PDF. CMD: ' . $cmd . '\nOutput: ' . implode("\n", $output), 500);
        }
        return response()->file($outputPdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_surat.pdf"',
        ]);
    }
} 