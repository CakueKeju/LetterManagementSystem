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

class SuratController extends Controller
{
    // Show the upload form
    public function showUploadForm()
    {
        $divisions = Division::all();
        $jenisSurat = JenisSurat::active()->get();
        return view('surat.upload', compact('divisions', 'jenisSurat'));
    }

    // Handle file upload and text extraction
    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        $file = $request->file('file');
        $path = $file->store('letters');
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
                    
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $extractionMethod = 'OCR (Tesseract)';
                    // Check if Tesseract is available
                    $tesseractPath = exec('which tesseract');
                    if (empty($tesseractPath)) {
                        $ocrError = 'Tesseract OCR tidak ditemukan. Pastikan Tesseract terinstall di sistem.';
                    } else {
                        $ocr = new TesseractOCR($file->getRealPath());
                        $ocr->executable($tesseractPath);
                        $extracted = $ocr->run();
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

        // Check for duplicate nomor_urut if we have divisi_id and nomor_urut
        if ($prefilledData['nomor_urut'] && $prefilledData['divisi_id']) {
            $duplicateCheck = $this->checkDuplicate($prefilledData['nomor_urut'], $prefilledData['divisi_id']);
            if ($duplicateCheck['is_duplicate']) {
                // Delete the uploaded file since it's a duplicate
                Storage::delete($path);

                return view('surat.duplicate_warning', [
                    'extracted_nomor_urut' => $prefilledData['nomor_urut'],
                    'extracted_divisi_id' => $prefilledData['divisi_id'],
                    'available_numbers' => $duplicateCheck['available_numbers'],
                    'divisions' => Division::all(),
                    'jenisSurat' => JenisSurat::active()->get(),
                    'extracted_text' => $extracted,
                    'ocr_error' => $ocrError,
                    'extraction_method' => $extractionMethod,
                ]);
            }
        }

        // Show the form for user to fill/check, with file info and extracted text
        return view('surat.confirm', [
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'kode_surat' => $prefilledData['kode_surat'] ?? '',
            'extracted_text' => $extracted,
            'input' => $prefilledData,
            'divisions' => Division::all(),
            'jenisSurat' => JenisSurat::active()->get(),
            'ocr_error' => $ocrError,
            'extraction_method' => $extractionMethod,
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
            // 'kode_surat' => 'required|string', // Remove this validation, will be generated
        ]);

        // Enforce unique nomor_urut per divisi
        if (Surat::where('nomor_urut', $request->nomor_urut)->where('divisi_id', $request->divisi_id)->exists()) {
            return back()->withErrors(['nomor_urut' => 'Nomor urut sudah ada di divisi ini.'])->withInput();
        }

        // Generate kode_surat
        $division = Division::find($request->divisi_id);
        $jenisSurat = JenisSurat::find($request->jenis_surat_id);
        $kodeSurat = sprintf('%s/%s/%s/INTENS/%s', $request->nomor_urut, $division->kode_divisi, $jenisSurat->kode_jenis, date('Y'));

        // Create the surat
        $surat = Surat::create([
            'nomor_urut' => $request->input('nomor_urut'),
            'kode_surat' => $kodeSurat,
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

        // Handle private access if surat is private
        if ($request->input('is_private') && $request->has('selected_users')) {
            $selectedUsers = $request->input('selected_users', []);
            foreach ($selectedUsers as $userId) {
                \App\Models\SuratAccess::grantAccess($surat->id, $userId);
            }
        }

        return redirect()->route('surat.upload')->with('success', 'Surat uploaded successfully!');
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
            'deskripsi' => '', // Always empty as requested
            'tanggal_surat' => date('Y-m-d'),
            'tanggal_diterima' => date('Y-m-d'), // Default to today
            'is_private' => false,
        ];

        // Try to extract nomor_urut and other data from extracted text
        if ($extractedText) {
            // Look for "Nomor:" pattern like "Nomor: 123/OPS/BAST/INTENS/2022"
            if (preg_match('/Nomor:\s*(\d+)\/([^\/\n]+)\/([^\/\n]+)\/INTENS\/(\d{4})/i', $extractedText, $matches)) {
                $data['nomor_urut'] = trim($matches[1]);
                $data['divisi_id'] = $this->findDivisiByKode(trim($matches[2]));
                $data['jenis_surat_id'] = $this->findJenisSuratByKode(trim($matches[3]));
                $data['tanggal_surat'] = $this->extractDateFromText($extractedText);
            }
            // Alternative pattern: "Nomor: 123/ABC/DEF/INTENS/2023" (without "Nomor:")
            elseif (preg_match('/(\d+)\/([^\/\n]+)\/([^\/\n]+)\/INTENS\/(\d{4})/i', $extractedText, $matches)) {
                $data['nomor_urut'] = trim($matches[1]);
                $data['divisi_id'] = $this->findDivisiByKode(trim($matches[2]));
                $data['jenis_surat_id'] = $this->findJenisSuratByKode(trim($matches[3]));
                $data['tanggal_surat'] = $this->extractDateFromText($extractedText);
            }
            // Simple number pattern if no structured format found
            elseif (preg_match('/(\d+)/', $extractedText, $matches)) {
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
            $data['divisi_id'] = $this->findDivisiFromText($extractedText);
        }

        if (!$data['jenis_surat_id'] && $extractedText) {
            $data['jenis_surat_id'] = $this->findJenisSuratFromText($extractedText);
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
            if (stripos($text, $division->nama_divisi) !== false ||
                stripos($text, $division->kode_divisi) !== false) {
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
            if (stripos($text, $jenis->nama_jenis) !== false ||
                stripos($text, $jenis->kode_jenis) !== false) {
                return $jenis->id;
            }
        }
        return null;
    }

    // Check for duplicate nomor_urut and get available numbers
    private function checkDuplicate($nomorUrut, $divisiId)
    {
        $isDuplicate = Surat::where('nomor_urut', $nomorUrut)
                           ->where('divisi_id', $divisiId)
                           ->exists();

        // Get available numbers for this division (next 5 available numbers)
        $existingNumbers = Surat::where('divisi_id', $divisiId)
                               ->pluck('nomor_urut')
                               ->sort()
                               ->values();

        $availableNumbers = [];
        $nextNumber = 1;

        foreach ($existingNumbers as $existingNumber) {
            if ($existingNumber > $nextNumber) {
                // Found a gap, add available numbers
                for ($i = $nextNumber; $i < $existingNumber && count($availableNumbers) < 5; $i++) {
                    $availableNumbers[] = $i;
                }
            }
            $nextNumber = $existingNumber + 1;
        }

        // Add next numbers if we don't have 5 yet
        while (count($availableNumbers) < 5) {
            $availableNumbers[] = $nextNumber++;
        }

        return [
            'is_duplicate' => $isDuplicate,
            'available_numbers' => $availableNumbers
        ];
    }
} 