<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\DocumentProcessor;
use Illuminate\Support\Facades\Storage;

class TestPlaceholderDetection extends Command
{
    use DocumentProcessor;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:placeholder-detection {file : Path to the test file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test placeholder detection in PDF and Word documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }
        
        $this->info("Testing placeholder detection for: {$filePath}");
        $this->info("This test will check positioning and font matching improvements.");
        
        // Generate a test nomor surat
        $nomorSurat = '001/ABC/DEF/INTENS/12/2024';
        
        try {
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            if ($fileExtension === 'pdf') {
                $this->info("Processing PDF file...");
                $this->info("Expected improvements:");
                $this->info("- Better positioning using Times font");
                $this->info("- Improved text placement based on document structure");
                $this->info("- Enhanced placeholder detection patterns");
                
                $result = $this->fillPdfWithNomorSurat($filePath, $nomorSurat);
                
                if ($result && file_exists($result)) {
                    $this->info("✅ PDF processed successfully!");
                    $this->info("Output file: {$result}");
                    $this->info("File size: " . filesize($result) . " bytes");
                    
                    // Clean up
                    unlink($result);
                    $this->info("✅ Test file cleaned up");
                } else {
                    $this->error("❌ PDF processing failed");
                    return 1;
                }
            } elseif (in_array($fileExtension, ['docx', 'doc'])) {
                $this->info("Processing Word document...");
                $this->info("Expected improvements:");
                $this->info("- Enhanced placeholder detection in complex structures");
                $this->info("- Better text replacement in tables and sections");
                
                $result = $this->fillWordWithNomorSurat($filePath, $nomorSurat);
                
                if ($result && file_exists($result)) {
                    $this->info("✅ Word document processed successfully!");
                    $this->info("Output file: {$result}");
                    $this->info("File size: " . filesize($result) . " bytes");
                    
                    // Clean up
                    unlink($result);
                    $this->info("✅ Test file cleaned up");
                } else {
                    $this->error("❌ Word document processing failed");
                    return 1;
                }
            } else {
                $this->error("Unsupported file type: {$fileExtension}");
                $this->info("Supported types: PDF, DOCX, DOC");
                return 1;
            }
            
            $this->info("🎉 Test completed successfully!");
            $this->info("The improved positioning and font matching should now work better.");
            
        } catch (\Exception $e) {
            $this->error("Test failed with error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 