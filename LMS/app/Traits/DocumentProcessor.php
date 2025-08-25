<?php

namespace App\Traits;

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

trait DocumentProcessor
{
    // ================================= PROSES PDF =================================
    
    /*
     * DISABLED: Automatic PDF filling functionality
     * Reason: Lacks flexibility and doesn't work reliably
     * Date: August 25, 2025
     */
    
    // isi PDF dengan nomor surat, deteksi dan ganti placeholder text
    private function fillPdfWithNomorSurat($pdfPath, $nomorSurat)
    {
        /*
        try {
            \Log::info('Mulai proses isi PDF:', [
                'pdf_path' => $pdfPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($pdfPath)
            ]);
            
            // parse PDF buat deteksi text dan koordinat
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            
            \Log::info('PDF berhasil di-parse, jumlah halaman: ' . count($pdf->getPages()));
            
            // pattern placeholder yang lebih fleksibel
            $placeholderPatterns = [
                // match persis format yang ada di PDF
                '/Nomor:\s*…\/…\/…\.\/…\.\/…\.\/…\./i',
                '/Nomor:\s*…\/…\/…\/…\/…\/…/i',
                '/Nomor:\s*\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\./i',
                
                // pattern format standar
                '/(?:Nomor\s*:?\s*)?(\d{3}\/)?([A-Z]+\/)?([A-Z]+\/)?(?:INTENS\/)?(\d{2}\/)?(\d{4})?/i',
                '/(?:Nomor\s*:?\s*)?(\d{1,3}\/)?([A-Z]+\/)?([A-Z]+\/)?(?:INTENS\/)?(\d{1,2}\/)?(\d{4})?/i',
                
                // pattern placeholder dengan titik
                '/(?:Nomor\s*:?\s*)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{3,5}\/)?(?:INTENS\/)?(\.{2,5}\/)?(\.{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(\.{1,5}\/)?(\.{1,5}\/)?(\.{1,5}\/)?(?:INTENS\/)?(\.{1,5}\/)?(\.{1,5})?/i',
                
                // Placeholder patterns with underscores
                '/(?:Nomor\s*:?\s*)?(_{3,5}\/)?(_{3,5}\/)?(_{3,5}\/)?(?:INTENS\/)?(_{2,5}\/)?(_{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(_{1,5}\/)?(_{1,5}\/)?(_{1,5}\/)?(?:INTENS\/)?(_{1,5}\/)?(_{1,5})?/i',
                
                // Placeholder patterns with dashes
                '/(?:Nomor\s*:?\s*)?(-{3,5}\/)?(-{3,5}\/)?(-{3,5}\/)?(?:INTENS\/)?(-{2,5}\/)?(-{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(-{1,5}\/)?(-{1,5}\/)?(-{1,5}\/)?(?:INTENS\/)?(-{1,5}\/)?(-{1,5})?/i',
                
                // Simple placeholder patterns
                '/(?:Nomor\s*:?\s*)?(\.{3,5})/i',
                '/(?:Nomor\s*:?\s*)?(_{3,5})/i',
                '/(?:Nomor\s*:?\s*)?(-{3,5})/i',
                
                // Generic placeholder patterns
                '/(?:Nomor\s*:?\s*)?(\*{3,5}\/)?(\*{3,5}\/)?(\*{3,5}\/)?(?:INTENS\/)?(\*{2,5}\/)?(\*{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(X{3,5}\/)?(X{3,5}\/)?(X{3,5}\/)?(?:INTENS\/)?(X{2,5}\/)?(X{4,5})?/i',
                
                // Common formal document patterns
                '/(?:Nomor\s*:?\s*)?(___+\/)?(___+\/)?(___+\/)?(?:INTENS\/)?(___+\/)?(___+)?/i',
                '/(?:Nomor\s*:?\s*)?(\.\.\.+\/)?(\.\.\.+\/)?(\.\.\.+\/)?(?:INTENS\/)?(\.\.\.+\/)?(\.\.\.+)?/i',
                '/(?:Nomor\s*:?\s*)?(---+\/)?(---+\/)?(---+\/)?(?:INTENS\/)?(---+\/)?(---+)?/i',
                
                // Simple text placeholders
                '/(?:Nomor\s*:?\s*)?\[.*?\]/i',
                '/(?:Nomor\s*:?\s*)?\{.*?\}/i',
                '/(?:Nomor\s*:?\s*)?\(.*?\)/i',
                
                // Common Indonesian formal document patterns
                '/(?:Nomor\s*:?\s*)?(\.{2,5})/i',
                '/(?:Nomor\s*:?\s*)?(_{2,5})/i',
                '/(?:Nomor\s*:?\s*)?(-{2,5})/i',
                
                // More specific patterns for formal documents
                '/(?:Nomor\s*:?\s*)?(\d{1,3}\/)?([A-Z]{2,5}\/)?([A-Z]{2,5}\/)?(?:INTENS\/)?(\d{1,2}\/)?(\d{4})?/i',
                '/(?:Nomor\s*:?\s*)?(\d{1,3}\/)?([A-Z]{2,5}\/)?([A-Z]{2,5}\/)?(\d{1,2}\/)?(\d{4})?/i',
                
                // Patterns without INTENS
                '/(?:Nomor\s*:?\s*)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{3,5}\/)?(\.{2,5}\/)?(\.{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(_{3,5}\/)?(_{3,5}\/)?(_{3,5}\/)?(_{2,5}\/)?(_{4,5})?/i',
                '/(?:Nomor\s*:?\s*)?(-{3,5}\/)?(-{3,5}\/)?(-{3,5}\/)?(-{2,5}\/)?(-{4,5})?/i',
                
                // Generic patterns for any text with dots/underscores/dashes
                '/…\/…\/…\.\/…\.\/…\.\/…\./i',
                '/…\/…\/…\/…\/…\/…/i',
                '/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\.\/\.\.\./i',
            ];
            
            $foundPlaceholder = false;
            $replacementData = [];
            $allText = '';
            
            foreach ($pdf->getPages() as $pageIndex => $page) {
                $text = $page->getText();
                $allText .= $text . ' ';
                \Log::info('Page ' . $pageIndex . ' text length: ' . strlen($text));
                \Log::info('Page ' . $pageIndex . ' text preview: ' . substr($text, 0, 500));
                
                // Try to find placeholder in this page
                foreach ($placeholderPatterns as $pattern) {
                    if (preg_match($pattern, $text, $matches)) {
                        $foundPlaceholder = true;
                        $matchedText = $matches[0];
                        
                        \Log::info('Placeholder ditemukan:', [
                            'pattern' => $pattern,
                            'matched_text' => $matchedText,
                            'nomor_surat' => $nomorSurat,
                            'page' => $pageIndex,
                            'matches' => $matches,
                            'full_text_sample' => substr($text, 0, 1000)
                        ]);
                        
                        // Try to find the position of the placeholder
                        $position = $this->findTextPosition($page, $matchedText);
                        
                        $replacementData[] = [
                            'page' => $pageIndex,
                            'pattern' => $pattern,
                            'matched_text' => $matchedText,
                            'replacement' => $nomorSurat,
                            'position' => $position
                        ];
                        
                        break 2; // Hanya replace yang pertama ditemukan
                    }
                }
            }
            
            // Buat PDF baru dengan replacement
            $result = $this->createFilledPdf($pdfPath, $replacementData);
            \Log::info('PDF fill result: ' . ($result ? $result : 'null'));
            
            if (!$foundPlaceholder) {
                \Log::warning('Placeholder text tidak ditemukan di PDF: ' . $pdfPath);
                \Log::info('Text yang ada di PDF (first 1000 chars):', ['text' => substr($allText, 0, 1000)]);
                
                // Fallback: try to find any text that looks like a placeholder
                foreach ($pdf->getPages() as $pageIndex => $page) {
                    $text = $page->getText();
                    $lines = explode("\n", $text);
                    
                    foreach ($lines as $lineIndex => $line) {
                        $line = trim($line);
                        
                        // Look for lines that contain "Nomor:" with placeholders
                        if (stripos($line, 'Nomor:') !== false) {
                            \Log::info('Found Nomor line: ' . $line);
                            
                            // Look for placeholder patterns in this line
                            if (preg_match('/(\.{2,}|_{2,}|-{2,}|…)/', $line)) {
                                \Log::info('Found placeholder in Nomor line: ' . $line);
                                
                                $foundPlaceholder = true;
                                $matchedText = $line;
                                
                                // Create a simple position for this line
                                $position = [
                                    'x' => 80, // Left side
                                    'y' => 180, // Below title
                                    'width' => strlen($line) * 6,
                                    'height' => 15,
                                    'font_size' => 12,
                                    'font_family' => 'Times',
                                    'font_style' => 'B',
                                    'original_text' => $line
                                ];
                                
                                $replacementData[] = [
                                    'page' => $pageIndex,
                                    'pattern' => 'fallback_nomor',
                                    'matched_text' => $matchedText,
                                    'replacement' => $nomorSurat,
                                    'position' => $position
                                ];
                                
                                break 2;
                            }
                        }
                        
                        // Also look for lines that contain dots, underscores, or dashes that might be placeholders
                        if (preg_match('/(\.{2,}|_{2,}|-{2,}|…)/', $line)) {
                            \Log::info('Found potential placeholder line: ' . $line);
                            
                            $foundPlaceholder = true;
                            $matchedText = $line;
                            
                            // Create a simple position for this line
                            $position = [
                                'x' => 80, // Left side
                                'y' => 180, // Below title
                                'width' => strlen($line) * 6,
                                'height' => 15,
                                'font_size' => 12,
                                'font_family' => 'Times',
                                'font_style' => 'B',
                                'original_text' => $line
                            ];
                            
                            $replacementData[] = [
                                'page' => $pageIndex,
                                'pattern' => 'fallback',
                                'matched_text' => $matchedText,
                                'replacement' => $nomorSurat,
                                'position' => $position
                            ];
                            
                            break 2;
                        }
                    }
                }
                
                // If still no placeholder found, create a default replacement
                if (!$foundPlaceholder) {
                    \Log::info('No placeholder found, creating default replacement');
                    $foundPlaceholder = true;
                    
                    $position = [
                        'x' => 80,
                        'y' => 180,
                        'width' => strlen($nomorSurat) * 6,
                        'height' => 15,
                        'font_size' => 12,
                        'font_family' => 'Times',
                        'font_style' => 'B',
                        'original_text' => 'placeholder'
                    ];
                    
                    $replacementData[] = [
                        'page' => 0,
                        'pattern' => 'default',
                        'matched_text' => 'placeholder',
                        'replacement' => $nomorSurat,
                        'position' => $position
                    ];
                }
                
                // Return file yang sudah di-copy meskipun tidak ada placeholder
                $result = $this->createFilledPdf($pdfPath, $replacementData);
                return $result;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Error filling PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
        */
        
        // Disabled: Return null to indicate PDF filling is not available
        \Log::info('PDF automatic filling is disabled', [
            'pdf_path' => $pdfPath,
            'nomor_surat' => $nomorSurat
        ]);
        return null;
    }
    
    /*
     * DISABLED: Text position finding for PDF filling
     */
    /**
     * Find the position of text in PDF page
     */
    private function findTextPosition($page, $searchText)
    {
        /*
        try {
            \Log::info('Finding text position for: ' . $searchText);
            
            // Get page dimensions - Smalot\PdfParser\Page doesn't have getWidth/getHeight
            // We'll use default dimensions or try to extract from page content
            $defaultWidth = 595; // A4 width in points
            $defaultHeight = 842; // A4 height in points
            
            // Try to get dimensions from page details if available
            $width = $defaultWidth;
            $height = $defaultHeight;
            
            try {
                // Try to get page details
                $pageDetails = $page->getDetails();
                \Log::info('Page details:', $pageDetails);
                
                if (isset($pageDetails['MediaBox'])) {
                    $mediaBox = $pageDetails['MediaBox'];
                    if (is_array($mediaBox) && count($mediaBox) >= 4) {
                        $width = $mediaBox[2] - $mediaBox[0]; // right - left
                        $height = $mediaBox[3] - $mediaBox[1]; // top - bottom
                        \Log::info('Page dimensions from MediaBox: ' . $width . ' x ' . $height);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not get page dimensions, using defaults: ' . $e->getMessage());
            }
            
            \Log::info('Using page dimensions: ' . $width . ' x ' . $height);
            
            // Try to extract more detailed position information
            try {
                $content = $page->getText();
                $lines = explode("\n", $content);
                
                foreach ($lines as $lineIndex => $line) {
                    $line = trim($line);
                    
                    // Look for lines containing "Nomor:" or the search text
                    if (stripos($line, 'Nomor:') !== false || stripos($line, $searchText) !== false) {
                        \Log::info('Found relevant line: ' . $line);
                        
                        // Calculate position based on line content and position
                        $y = $height - (($lineIndex + 1) * 15); // Better line height estimation
                        
                        // Try to estimate X position based on line content
                        $x = 50; // Default left margin
                        
                        // If line starts with "Nomor" or contains "Nomor:", position it properly
                        if (preg_match('/^(Nomor|No\.?|Number)/i', $line)) {
                            $x = 80; // Slightly indented for formal documents
                        }
                        
                        // If line contains the placeholder, try to position it where the placeholder was
                        $placeholderPos = stripos($line, $searchText);
                        if ($placeholderPos !== false) {
                            // Estimate X position based on placeholder position in line
                            $x = 80 + ($placeholderPos * 3); // Rough character width estimation
                        }
                        
                        // If line contains "Nomor:" but not the placeholder, position after "Nomor:"
                        if (stripos($line, 'Nomor:') !== false && stripos($line, $searchText) === false) {
                            $nomorPos = stripos($line, 'Nomor:');
                            $x = 80 + ($nomorPos + 6) * 3; // Position after "Nomor: "
                        }
                        
                        // Ensure position is within reasonable bounds
                        $x = max(50, min($x, $width - 200));
                        $y = max(50, min($y, $height - 50));
                        
                        $position = [
                            'x' => $x,
                            'y' => $y,
                            'width' => strlen($searchText) * 5, // Better width estimation
                            'height' => 12,
                            'font_size' => 10,
                            'font_family' => 'Times', // Use Times for formal documents
                            'font_style' => '',
                            'original_text' => $searchText // Store original text for white block sizing
                        ];
                        
                        \Log::info('Found text position: x=' . $position['x'] . ', y=' . $position['y'] . ', font=' . $position['font_family'] . ', original_text=' . $searchText);
                        return $position;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not extract detailed position: ' . $e->getMessage());
            }
            
            // Default position for formal documents (left-aligned below title)
            $defaultPosition = [
                'x' => 80,
                'y' => 180, // Below title "BERITA ACARA SERAH TERIMA"
                'width' => strlen($searchText) * 6,
                'height' => 15,
                'font_size' => 12,
                'font_family' => 'Times',
                'font_style' => 'B',
                'original_text' => $searchText
            ];
            
            \Log::info('Using default position: x=' . $defaultPosition['x'] . ', y=' . $defaultPosition['y']);
            return $defaultPosition;
            
        } catch (\Exception $e) {
            \Log::warning('Error finding text position: ' . $e->getMessage());
            return [
                'x' => 80,
                'y' => 180,
                'width' => strlen($searchText) * 6,
                'height' => 15,
                'font_size' => 12,
                'font_family' => 'Times',
                'font_style' => 'B',
                'original_text' => $searchText
            ];
        }
        */
        
        // Disabled: Return default position for compatibility
        return [
            'x' => 80,
            'y' => 180,
            'width' => strlen($searchText) * 6,
            'height' => 15,
            'font_size' => 12,
            'font_family' => 'Times',
            'font_style' => 'B',
            'original_text' => $searchText
        ];
    }
    
    /*
     * DISABLED: PDF creation and filling functionality
     */
    /**
     * Create filled PDF with nomor surat replacement
     */
    private function createFilledPdf($originalPath, $replacementData)
    {
        /*
        try {
            \Log::info('Creating filled PDF:', [
                'original_path' => $originalPath,
                'replacement_count' => count($replacementData)
            ]);
            
            // Create temp file for results
            $tempPath = storage_path('app/filled_pdf_' . uniqid() . '.pdf');
            
            if (empty($replacementData)) {
                // Jika tidak ada replacement data, copy file asli
                if (copy($originalPath, $tempPath)) {
                    \Log::info('PDF copied to temp location (no replacements): ' . $tempPath);
                    return $tempPath;
                } else {
                    \Log::error('Failed to copy PDF to temp location');
                    return null;
                }
            }
            
            // Implementasi pengisian PDF menggunakan FPDI
            try {
                // Import FPDI
                $pdf = new \setasign\Fpdi\Fpdi();
                
                // Set document properties
                $pdf->SetCreator('LMS System');
                $pdf->SetAuthor('LMS System');
                $pdf->SetTitle('Filled Document');
                
                // Get page count from original PDF
                $pageCount = $pdf->setSourceFile($originalPath);
                \Log::info('Original PDF has ' . $pageCount . ' pages');
                
                // Process each page
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    try {
                        // Import page
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        
                        // Ensure size is valid
                        if (!isset($size['width']) || !isset($size['height'])) {
                            \Log::warning('Invalid page size for page ' . $pageNo . ', using defaults');
                            $size = ['width' => 595, 'height' => 842, 'orientation' => 'P'];
                        }
                        
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                        
                        // Apply replacements for this page (only on first page for now)
                        if ($pageNo == 1) {
                            foreach ($replacementData as $replacement) {
                                \Log::info('Applying replacement on page ' . $pageNo . ': ' . $replacement['replacement']);
                                
                                // Get position and font information
                                $position = $replacement['position'] ?? [];
                                $x = $position['x'] ?? 80;
                                $y = $position['y'] ?? 80;
                                $fontFamily = $position['font_family'] ?? 'Times';
                                $fontSize = $position['font_size'] ?? 10;
                                $fontStyle = $position['font_style'] ?? '';
                                
                                // For formal documents, position nomor surat below the title, left-aligned
                                // Based on the template, it should be around 80-100 x, 150-200 y
                                $x = 65;
                                $y = 57;
                                
                                // Ensure position is within page bounds
                                $x = max(10, min($x, $size['width'] - 100));
                                $y = max(10, min($y, $size['height'] - 20));
                                
                                // Calculate text dimensions for white block
                                $textToWrite = 'Nomor : ' . $replacement['replacement'];
                                $originalTextLength = strlen($position['original_text'] ?? $replacement['matched_text']);
                                $newTextLength = strlen($textToWrite);
                                
                                // Use the longer of original or new text for white block width
                                $textWidth = max($originalTextLength, $newTextLength) * 4; // Reduced character width for smaller white block
                                $textHeight = 8; // Reduced line height for smaller white block
                                
                                // Draw white rectangle to cover original text
                                $pdf->SetFillColor(255, 255, 255); // White
                                $pdf->Rect($x - 2, $y - 1, $textWidth + 4, $textHeight + 2, 'F'); // Smaller white background
                                
                                // Set font properties to match original document - larger size
                                $pdf->SetFont($fontFamily, 'B', 12); // Bold, larger font size
                                $pdf->SetTextColor(0, 0, 0); // Black text
                                
                                // Add text overlay with "Nomor : " prefix
                                $pdf->SetXY($x, $y);
                                $pdf->Write(0, $textToWrite);
                                
                                // Add underline below the text
                                $underlineY = $y + 2; // Position line just below the text
                                $underlineWidth = 81; // Fixed width for underline
                                $pdf->SetDrawColor(0, 0, 0); // Black line
                                $pdf->SetLineWidth(0.5); // Line thickness
                                $pdf->Line($x, $underlineY, $x + $underlineWidth, $underlineY); // Draw underline
                                
                                \Log::info('Text added at position: x=' . $x . ', y=' . $y . ', font=' . $fontFamily . '-B-12, text: ' . $textToWrite . ' with underline');
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error processing page ' . $pageNo . ': ' . $e->getMessage());
                        // Continue with next page
                        continue;
                    }
                }
                
                // Save the filled PDF
                $pdf->Output($tempPath, 'F');
                
                if (file_exists($tempPath)) {
                    \Log::info('Filled PDF created successfully: ' . $tempPath);
                    // Set proper permissions
                    chmod($tempPath, 0644);
                    return $tempPath;
                } else {
                    \Log::error('Failed to create filled PDF');
                    return null;
                }
                
            } catch (\Exception $e) {
                \Log::error('Error with FPDI: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Fallback: create simple PDF with text overlay
                try {
                    \Log::info('Trying fallback approach: create simple PDF with text');
                    $pdf = new \setasign\Fpdi\Fpdi();
                    $pdf->SetCreator('LMS System');
                    $pdf->SetAuthor('LMS System');
                    $pdf->SetTitle('Filled Document');
                    
                    // Add a page
                    $pdf->AddPage();
                    
                    // Add text with better formatting
                    $pdf->SetFont('Times', 'B', 12); // Use Times font, bold, larger size
                    $pdf->SetTextColor(0, 0, 0);
                    
                    foreach ($replacementData as $replacement) {
                        $textToWrite = 'Nomor : ' . $replacement['replacement'];
                        $x = 80;
                        $y = 180;
                        
                        // Draw white rectangle to cover area
                        $textWidth = strlen($textToWrite) * 4; // Reduced character width
                        $textHeight = 8; // Reduced line height
                        $pdf->SetFillColor(255, 255, 255); // White
                        $pdf->Rect($x - 2, $y - 1, $textWidth + 4, $textHeight + 2, 'F'); // Smaller white background
                        
                        // Add text
                        $pdf->SetXY($x, $y);
                        $pdf->Write(0, $textToWrite);
                        
                        // Add underline below the text
                        $underlineY = $y + 2; // Position line just below the text
                        $underlineWidth = 80; // Fixed width for underline
                        $pdf->SetDrawColor(0, 0, 0); // Black line
                        $pdf->SetLineWidth(0.5); // Line thickness
                        $pdf->Line($x, $underlineY, $x + $underlineWidth, $underlineY); // Draw underline
                        
                        break; // Only use first replacement
                    }
                    
                    $pdf->Output($tempPath, 'F');
                    
                    if (file_exists($tempPath)) {
                        \Log::info('Fallback PDF created successfully: ' . $tempPath);
                        // Set proper permissions
                        chmod($tempPath, 0644);
                        return $tempPath;
                    }
                } catch (\Exception $fallbackError) {
                    \Log::error('Fallback also failed: ' . $fallbackError->getMessage());
                }
                
                // Final fallback: copy original file
                if (copy($originalPath, $tempPath)) {
                    \Log::info('Final fallback: PDF copied to temp location: ' . $tempPath);
                    return $tempPath;
                } else {
                    \Log::error('Final fallback failed: Could not copy PDF');
                    return null;
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error creating filled PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
        */
        
        // Disabled: Return null to indicate PDF creation is not available
        \Log::info('PDF creation and filling is disabled', [
            'original_path' => $originalPath,
            'replacement_count' => count($replacementData)
        ]);
        return null;
    }

    /*
     * DISABLED: Word to PDF conversion functionality
     */
    /**
     * Fill Word document with nomor surat and convert to PDF
     */
    private function fillWordWithNomorSuratAndConvertToPdf($wordPath, $nomorSurat)
    {
        try {
            \Log::info('Starting Word to PDF process:', [
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($wordPath)
            ]);
            
            // First, fill the Word document
            $filledWordPath = $this->fillWordWithNomorSuratSimple($wordPath, $nomorSurat);
            
            if (!$filledWordPath || !file_exists($filledWordPath)) {
                \Log::error('Failed to create filled Word document');
                return null;
            }
            
            // Now convert the filled Word document to PDF
            $pdfPath = $this->convertWordToPdf($filledWordPath);
            
            if ($pdfPath && file_exists($pdfPath)) {
                // PDF conversion successful, clean up Word file
                if (file_exists($filledWordPath)) {
                    unlink($filledWordPath);
                }
                return $pdfPath;
            } else {
                // PDF conversion failed, return the filled DOCX file
                \Log::warning('PDF conversion failed, returning filled DOCX file instead');
                return $filledWordPath;
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in Word to PDF process: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Convert Word document to PDF preserving ALL formatting, images, logos
     * Uses LibreOffice headless mode for perfect conversion
     */
    private function convertWordToPdf($wordPath)
    {
        try {
            \Log::info('Converting filled Word document to PDF using LibreOffice:', [
                'word_path' => $wordPath,
                'file_exists' => file_exists($wordPath),
                'file_size' => file_exists($wordPath) ? filesize($wordPath) : 0
            ]);

            if (!file_exists($wordPath)) {
                \Log::error('Word file does not exist for PDF conversion: ' . $wordPath);
                return null;
            }

            // Method 1: Try LibreOffice headless conversion
            $pdfPath = $this->convertWithLibreOffice($wordPath);
            if ($pdfPath && file_exists($pdfPath)) {
                return $pdfPath;
            }

            // Method 2: Try Pandoc conversion
            $pdfPath = $this->convertWithPandoc($wordPath);
            if ($pdfPath && file_exists($pdfPath)) {
                return $pdfPath;
            }

            // Method 3: Try unoconv (LibreOffice python wrapper)
            $pdfPath = $this->convertWithUnoconv($wordPath);
            if ($pdfPath && file_exists($pdfPath)) {
                return $pdfPath;
            }

            \Log::warning('All external conversion methods failed, document will remain as DOCX');
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Error in convertWordToPdf: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'word_path' => $wordPath
            ]);
            return null;
        }
    }

    /**
     * Convert DOCX to PDF using LibreOffice headless mode
     */
    private function convertWithLibreOffice($wordPath)
    {
        try {
            \Log::info('Attempting LibreOffice conversion');
            
            $outputDir = dirname($wordPath);
            $pdfPath = $outputDir . '/' . pathinfo($wordPath, PATHINFO_FILENAME) . '.pdf';
            
            // Common LibreOffice installation paths
            $libreOfficePaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                '/usr/bin/libreoffice',
                '/Applications/LibreOffice.app/Contents/MacOS/soffice',
                'soffice', // If in PATH
                'libreoffice' // If in PATH
            ];
            
            $libreOfficeCmd = null;
            foreach ($libreOfficePaths as $path) {
                if (file_exists($path)) {
                    $libreOfficeCmd = '"' . $path . '"';
                    \Log::info('Found LibreOffice at: ' . $path);
                    break;
                } elseif ($path === 'soffice' || $path === 'libreoffice') {
                    // Test if command is available in PATH
                    $testCmd = $path . ' --version';
                    $testOutput = [];
                    $testReturn = 0;
                    exec($testCmd, $testOutput, $testReturn);
                    if ($testReturn === 0) {
                        $libreOfficeCmd = $path;
                        \Log::info('Found LibreOffice in PATH: ' . $path);
                        break;
                    }
                }
            }
            
            if (!$libreOfficeCmd) {
                \Log::warning('LibreOffice not found. Please install LibreOffice from https://www.libreoffice.org/');
                return null;
            }
            
            // LibreOffice command for headless conversion
            $cmd = $libreOfficeCmd . ' --headless --convert-to pdf --outdir "' . $outputDir . '" "' . $wordPath . '"';
            
            \Log::info('Executing LibreOffice command: ' . $cmd);
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            \Log::info('LibreOffice conversion result:', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
                'expected_pdf' => $pdfPath
            ]);
            
            if ($returnCode === 0 && file_exists($pdfPath)) {
                \Log::info('LibreOffice conversion successful: ' . $pdfPath);
                return $pdfPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('LibreOffice conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert DOCX to PDF using Pandoc
     */
    private function convertWithPandoc($wordPath)
    {
        try {
            \Log::info('Attempting Pandoc conversion');
            
            $pdfPath = str_replace('.docx', '.pdf', $wordPath);
            
            // Try pandoc command
            $cmd = 'pandoc "' . $wordPath . '" -o "' . $pdfPath . '"';
            
            \Log::info('Executing Pandoc command: ' . $cmd);
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            \Log::info('Pandoc conversion result:', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            
            if ($returnCode === 0 && file_exists($pdfPath)) {
                \Log::info('Pandoc conversion successful: ' . $pdfPath);
                return $pdfPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Pandoc conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert DOCX to PDF using unoconv
     */
    private function convertWithUnoconv($wordPath)
    {
        try {
            \Log::info('Attempting unoconv conversion');
            
            $pdfPath = str_replace('.docx', '.pdf', $wordPath);
            
            // Try unoconv command
            $cmd = 'unoconv -f pdf "' . $wordPath . '"';
            
            \Log::info('Executing unoconv command: ' . $cmd);
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            \Log::info('unoconv conversion result:', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            
            if ($returnCode === 0 && file_exists($pdfPath)) {
                \Log::info('unoconv conversion successful: ' . $pdfPath);
                return $pdfPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('unoconv conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fill Word document with nomor surat by editing the DOCX ZIP structure directly
     * This preserves the original document formatting perfectly
     */
    private function fillWordWithNomorSuratSimple($wordPath, $nomorSurat)
    {
        try {
            \Log::info('Starting direct DOCX ZIP editing:', [
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($wordPath),
                'file_size' => file_exists($wordPath) ? filesize($wordPath) : 0
            ]);

            if (!file_exists($wordPath)) {
                \Log::error('Word file does not exist: ' . $wordPath);
                return null;
            }

            $tempPath = storage_path('app/filled_word_' . uniqid() . '.docx');
            
            if (!copy($wordPath, $tempPath)) {
                \Log::error('Failed to copy Word file to temp location');
                return null;
            }

            \Log::info('Word file copied to temp location: ' . $tempPath);

            // Open DOCX as ZIP file
            $zip = new \ZipArchive();
            if ($zip->open($tempPath) !== TRUE) {
                \Log::error('Failed to open DOCX as ZIP file');
                return null;
            }

            $foundPlaceholder = false;
            
            // The main document content is in word/document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                \Log::error('Could not extract document.xml from DOCX');
                $zip->close();
                return null;
            }

            // ============================================================================
            // WARNING: TEMPORARY DEBUG LOGGING - REMOVE AFTER TESTING
            // Set to false to disable all debug logging
            $enableDebugLogging = true;
            // ============================================================================
            
            if ($enableDebugLogging) {
                \Log::warning('=== TEMPORARY DEBUG LOGGING ENABLED - REMOVE AFTER TESTING ===');
            }

            if ($enableDebugLogging) {
                \Log::warning('=== DEBUG: XML CONTENT ANALYSIS ===');
                \Log::info('Original document.xml size: ' . strlen($documentXml));
                
                // Debug: Show a sample of the XML content first
                $sampleXml = substr($documentXml, 0, 3000);
                \Log::warning('DEBUG - Document XML sample (first 3000 chars):', ['xml_sample' => $sampleXml]);
                
                // Look for any text containing "nomor" to understand the structure
                if (preg_match_all('/<w:t[^>]*>([^<]*(?:nomor|no\.|No\.|NOMOR).*?)<\/w:t>/i', $documentXml, $debugMatches)) {
                    \Log::warning('DEBUG - Found nomor text elements in XML:', [
                        'count' => count($debugMatches[1]),
                        'matches' => $debugMatches[1]
                    ]);
                }
                
                // Look for any underscore patterns
                if (preg_match_all('/<w:t[^>]*>([^<]*[_]{3,}[^<]*)<\/w:t>/i', $documentXml, $underscoreMatches)) {
                    \Log::warning('DEBUG - Found underscore patterns in XML:', [
                        'count' => count($underscoreMatches[1]),
                        'matches' => $underscoreMatches[1]
                    ]);
                }
                
                // Look for any dot patterns
                if (preg_match_all('/<w:t[^>]*>([^<]*[\.]{3,}[^<]*)<\/w:t>/i', $documentXml, $dotMatches)) {
                    \Log::warning('DEBUG - Found dot patterns in XML:', [
                        'count' => count($dotMatches[1]),
                        'matches' => $dotMatches[1]
                    ]);
                }
                
                \Log::warning('=== END DEBUG: XML CONTENT ANALYSIS ===');
            }
            
            \Log::info('Starting direct DOCX ZIP editing:', [
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat,
                'file_exists' => file_exists($wordPath),
                'file_size' => file_exists($wordPath) ? filesize($wordPath) : 0
            ]);

            if (!file_exists($wordPath)) {
                \Log::error('Word file does not exist: ' . $wordPath);
                return null;
            }

            $tempPath = storage_path('app/filled_word_' . uniqid() . '.docx');
            
            if (!copy($wordPath, $tempPath)) {
                \Log::error('Failed to copy Word file to temp location');
                return null;
            }

            \Log::info('Word file copied to temp location: ' . $tempPath);

            // Open DOCX as ZIP file
            $zip = new \ZipArchive();
            if ($zip->open($tempPath) !== TRUE) {
                \Log::error('Failed to open DOCX as ZIP file');
                return null;
            }

            $foundPlaceholder = false;
            
            // The main document content is in word/document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                \Log::error('Could not extract document.xml from DOCX');
                $zip->close();
                return null;
            }

            if ($enableDebugLogging) {
                \Log::warning('=== DEBUG: XML CONTENT ANALYSIS ===');
                \Log::info('Original document.xml size: ' . strlen($documentXml));
                
                // Debug: Show a sample of the XML content first
                $sampleXml = substr($documentXml, 0, 3000);
                \Log::warning('DEBUG - Document XML sample (first 3000 chars):', ['xml_sample' => $sampleXml]);
                
                // Look for any text containing "nomor" to understand the structure
                if (preg_match_all('/<w:t[^>]*>([^<]*(?:nomor|no\.|No\.|NOMOR).*?)<\/w:t>/i', $documentXml, $debugMatches)) {
                    \Log::warning('DEBUG - Found nomor text elements in XML:', [
                        'count' => count($debugMatches[1]),
                        'matches' => $debugMatches[1]
                    ]);
                }
                
                // Look for any underscore patterns
                if (preg_match_all('/<w:t[^>]*>([^<]*[_]{3,}[^<]*)<\/w:t>/i', $documentXml, $underscoreMatches)) {
                    \Log::warning('DEBUG - Found underscore patterns in XML:', [
                        'count' => count($underscoreMatches[1]),
                        'matches' => $underscoreMatches[1]
                    ]);
                }
                
                // Look for any dot patterns
                if (preg_match_all('/<w:t[^>]*>([^<]*[\.]{3,}[^<]*)<\/w:t>/i', $documentXml, $dotMatches)) {
                    \Log::warning('DEBUG - Found dot patterns in XML:', [
                        'count' => count($dotMatches[1]),
                        'matches' => $dotMatches[1]
                    ]);
                }
                
                \Log::warning('=== END DEBUG: XML CONTENT ANALYSIS ===');
            }
            
            // Define patterns to find and replace - target the actual placeholder specifically
            $patterns = [
                // Pattern 1: Find "Nomor:" element followed by placeholder element containing â€¦ characters
                '/(<w:t[^>]*>[^<]*\b(?:nomor)\s*:\s*<\/w:t>\s*)(<w:t[^>]*>)([^<]*â€¦[^<]*?)(<\/w:t>)/i',
                
                // Pattern 2: Find "Nomor:" and â€¦ placeholder in same element 
                '/<w:t([^>]*)>([^<]*\b(?:nomor)\s*:?\s*)([^<]*â€¦[^<]*)<\/w:t>/i',
                
                // Pattern 3: Find standalone elements containing â€¦ (the actual placeholder)
                '/<w:t[^>]*>([^<]*â€¦[^<]*)<\/w:t>/i',
                
                // Pattern 4: Find elements with dots and slashes (but not just spaces)
                '/<w:t[^>]*>([^<]*[\.\-_\/]{2,}[^<]*[^\s])<\/w:t>/i',
                
                // Pattern 5: Find "Nomor:" and replace everything after it in same element (fallback)
                '/<w:t([^>]*)>([^<]*\b(?:nomor)\s*:?\s*)([^<]*)<\/w:t>/i',
            ];

            $originalXml = $documentXml;
            $patternUsed = null;
            
            // First, let's see if we can find "Nomor" and get context around it
            if ($enableDebugLogging) {
                // Look specifically for the placeholder element
                if (preg_match_all('/<w:t[^>]*>([^<]*â€¦[^<]*)<\/w:t>/i', $documentXml, $placeholderMatches)) {
                    \Log::warning('DEBUG - Found placeholder elements with dots:', [
                        'matches' => array_map(function($match) { return $match[0]; }, $placeholderMatches[1])
                    ]);
                }
                
                if (preg_match_all('/<w:t[^>]*>([^<]*(?:nomor)[^<]*)<\/w:t>/i', $documentXml, $nomorMatches, PREG_OFFSET_CAPTURE)) {
                    \Log::warning('DEBUG - Found Nomor contexts:', [
                        'matches' => array_map(function($match) { return $match[0]; }, $nomorMatches[1])
                    ]);
                    
                    // For each nomor match, show what comes after it in more detail
                    foreach ($nomorMatches[0] as $index => $fullMatch) {
                        $position = $fullMatch[1] + strlen($fullMatch[0]);
                        $nextContent = substr($documentXml, $position, 800);
                        
                        // Extract the next few w:t elements
                        if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $nextContent, $nextElements)) {
                            \Log::warning("DEBUG - Elements after Nomor match {$index}:", [
                                'nomor_element' => $fullMatch[0],
                                'next_5_elements' => array_slice($nextElements[1], 0, 5)
                            ]);
                        }
                    }
                }
            }
            
            foreach ($patterns as $patternIndex => $pattern) {
                $matches = [];
                if (preg_match_all($pattern, $documentXml, $matches, PREG_OFFSET_CAPTURE)) {
                    if ($enableDebugLogging) {
                        \Log::warning("DEBUG - Pattern {$patternIndex} found " . count($matches[0]) . " matches:", [
                            'pattern' => $pattern,
                            'first_3_matches' => array_slice($matches[0], 0, 3) // Show first 3 matches
                        ]);
                    }
                    
                    // Apply replacement - keep "Nomor:" and only replace placeholder content
                    if ($patternIndex == 0) {
                        // Pattern 1: "Nomor:" element + placeholder element - keep Nomor:, replace placeholder
                        $replacement = '$1$2' . $nomorSurat . '$4';
                        $documentXml = preg_replace($pattern, $replacement, $documentXml, 1);
                        $patternUsed = $patternIndex;
                        $foundPlaceholder = true;
                        
                        if ($enableDebugLogging) {
                            \Log::warning('DEBUG - Applied multi-element replacement (keep Nomor, replace placeholder):', [
                                'pattern_index' => $patternIndex,
                                'replacement_template' => $replacement
                            ]);
                        }
                    } elseif ($patternIndex == 1) {
                        // Pattern 2: "Nomor:" and placeholder in same element - keep Nomor:, replace placeholder
                        $replacement = '<w:t$1>$2' . $nomorSurat . '</w:t>';
                        $documentXml = preg_replace($pattern, $replacement, $documentXml, 1);
                        $patternUsed = $patternIndex;
                        $foundPlaceholder = true;
                        
                        if ($enableDebugLogging) {
                            \Log::warning('DEBUG - Applied same-element replacement (keep Nomor):', [
                                'pattern_index' => $patternIndex,
                                'replacement_template' => $replacement
                            ]);
                        }
                    } elseif ($patternIndex == 2 || $patternIndex == 3) {
                        // Pattern 3, 4: Standalone placeholder elements - replace with just the number (no "Nomor:")
                        $replacement = '<w:t>' . $nomorSurat . '</w:t>';
                        $documentXml = preg_replace($pattern, $replacement, $documentXml, 1);
                        $patternUsed = $patternIndex;
                        $foundPlaceholder = true;
                        
                        if ($enableDebugLogging) {
                            \Log::warning('DEBUG - Applied standalone placeholder replacement (number only):', [
                                'pattern_index' => $patternIndex,
                                'replacement' => $replacement
                            ]);
                        }
                    } else {
                        // Pattern 5: Fallback - replace with complete "Nomor: [number]"
                        $replacement = '<w:t>Nomor: ' . $nomorSurat . '</w:t>';
                        $documentXml = preg_replace($pattern, $replacement, $documentXml, 1);
                        $patternUsed = $patternIndex;
                        $foundPlaceholder = true;
                        
                        if ($enableDebugLogging) {
                            \Log::warning('DEBUG - Applied fallback replacement (complete):', [
                                'pattern_index' => $patternIndex,
                                'replacement' => $replacement
                            ]);
                        }
                    }
                    
                    break; // Stop after first successful replacement
                }
            }

            if (!$foundPlaceholder) {
                if ($enableDebugLogging) {
                    \Log::warning('=== DEBUG: NO PATTERN MATCHED - DETAILED ANALYSIS ===');
                    
                    // Enhanced debugging - show more XML structure
                    \Log::warning('DEBUG - Full document XML sample for debugging (first 5000 chars):', [
                        'xml_sample' => substr($documentXml, 0, 5000),
                        'xml_length' => strlen($documentXml)
                    ]);
                    
                    // Try to find ANY text that might contain placeholders
                    if (preg_match_all('/<w:t[^>]*>([^<]*[_\.\-]{2,}[^<]*)<\/w:t>/i', $documentXml, $placeholderMatches)) {
                        \Log::warning('DEBUG - Found ANY placeholder-like text elements:', [
                            'count' => count($placeholderMatches[1]),
                            'matches' => $placeholderMatches[1]
                        ]);
                    }
                    
                    // Look for "nomor" in any case or form
                    if (preg_match_all('/<w:t[^>]*>([^<]*(?:nomor|number|no\.|nr\.|num).*?)<\/w:t>/i', $documentXml, $nomorMatches)) {
                        \Log::warning('DEBUG - Found ANY nomor-like text elements:', [
                            'count' => count($nomorMatches[1]),
                            'matches' => $nomorMatches[1]
                        ]);
                    }
                    
                    // Show all w:t elements for analysis
                    if (preg_match_all('/<w:t[^>]*>([^<]+)<\/w:t>/i', $documentXml, $allTextMatches)) {
                        $textElements = array_slice($allTextMatches[1], 0, 20); // First 20 text elements
                        \Log::warning('DEBUG - First 20 text elements in document:', [
                            'count_total' => count($allTextMatches[1]),
                            'first_20' => $textElements
                        ]);
                    }
                    
                    \Log::warning('=== END DEBUG: NO PATTERN MATCHED ANALYSIS ===');
                }
                
                \Log::warning('No "Nomor" pattern found in document.xml - check debug logs above');
            } else {
                if ($enableDebugLogging) {
                    \Log::warning('DEBUG - Successfully replaced nomor placeholder:', [
                        'pattern_used' => $patternUsed,
                        'original_length' => strlen($originalXml),
                        'modified_length' => strlen($documentXml),
                        'length_difference' => strlen($documentXml) - strlen($originalXml)
                    ]);
                    
                    // Show sample of modified XML
                    $modifiedSample = substr($documentXml, 0, 3000);
                    \Log::warning('DEBUG - Modified XML sample (first 3000 chars):', ['xml_sample' => $modifiedSample]);
                }
            }

            // Update the document.xml in the ZIP
            $zip->deleteName('word/document.xml');
            $zip->addFromString('word/document.xml', $documentXml);
            $zip->close();

            \Log::info('DOCX file processed successfully:', [
                'temp_path' => $tempPath,
                'placeholder_found' => $foundPlaceholder ? 'yes' : 'no',
                'original_xml_size' => strlen($originalXml),
                'modified_xml_size' => strlen($documentXml),
                'output_file_size' => file_exists($tempPath) ? filesize($tempPath) : 0
            ]);

            return $tempPath;
            
        } catch (\Exception $e) {
            \Log::error('Error in fillWordWithNomorSuratSimple: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'word_path' => $wordPath,
                'nomor_surat' => $nomorSurat
            ]);
            return null;
        }
    }

    /**
     * Recursively search for "Nomor :" field and fill it with flexible approach
     * This method finds "Nomor :" with any spacing and replaces everything after it
     * Also handles underscore placeholders like "Nomor : ____________"
     */
    private function searchAndFillNomorInElementFlexible($element, $nomorSurat)
    {
        $found = false;
        
        // Handle TextRun elements (most common)
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $textElement) {
                if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $textElement->getText();
                    
                    // Enhanced patterns to find "Nomor" with various formats including underscores
                    $patterns = [
                        // Pattern 1: "Nomor :" followed by underscores, dots, dashes
                        '/\b(nomor|no\.?)\s*:?\s*[_\.\-\s]+/i',
                        // Pattern 2: Just "Nomor :" with any following content
                        '/\b(nomor|no\.?)\s*:?\s*.*/i',
                        // Pattern 3: Lines with mostly underscores after "Nomor"
                        '/\b(nomor|no\.?)\s*:?\s*_{3,}/i',
                        // Pattern 4: Lines with dots after "Nomor"
                        '/\b(nomor|no\.?)\s*:?\s*\.{3,}/i',
                        // Pattern 5: Lines with dashes after "Nomor"
                        '/\b(nomor|no\.?)\s*:?\s*-{3,}/i'
                    ];
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $text)) {
                            \Log::info('Found Nomor field in TextRun with pattern:', [
                                'pattern' => $pattern,
                                'original_text' => $text,
                                'nomor_surat' => $nomorSurat
                            ]);
                            
                            // Replace the matched pattern with "Nomor : [nomor_surat]"
                            $newText = preg_replace($pattern, '$1 : ' . $nomorSurat, $text);
                            $textElement->setText($newText);
                            
                            \Log::info('Filled Nomor field:', ['new_text' => $newText]);
                            $found = true;
                            break; // Stop after first match
                        }
                    }
                }
            }
        }
        // Handle direct Text elements
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text = $element->getText();
            
            // Same enhanced patterns for direct text elements
            $patterns = [
                '/\b(nomor|no\.?)\s*:?\s*[_\.\-\s]+/i',
                '/\b(nomor|no\.?)\s*:?\s*.*/i',
                '/\b(nomor|no\.?)\s*:?\s*_{3,}/i',
                '/\b(nomor|no\.?)\s*:?\s*\.{3,}/i',
                '/\b(nomor|no\.?)\s*:?\s*-{3,}/i'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    \Log::info('Found Nomor field in Text element with pattern:', [
                        'pattern' => $pattern,
                        'original_text' => $text,
                        'nomor_surat' => $nomorSurat
                    ]);
                    
                    $newText = preg_replace($pattern, '$1 : ' . $nomorSurat, $text);
                    $element->setText($newText);
                    
                    \Log::info('Filled Nomor field:', ['new_text' => $newText]);
                    $found = true;
                    break; // Stop after first match
                }
            }
        }
        // Handle Table elements
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        if ($this->searchAndFillNomorInElementFlexible($cellElement, $nomorSurat)) {
                            $found = true;
                        }
                    }
                }
            }
        }
        // Handle Container elements (like sections)
        elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $subElement) {
                if ($this->searchAndFillNomorInElementFlexible($subElement, $nomorSurat)) {
                    $found = true;
                }
            }
        }
        
        return $found;
    }

    /**
     * Extract text from Word element (recursive)
     */
    private function extractTextFromElement($element)
    {
        $text = '';
        
        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text = $element->getText();
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $textElement) {
                if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $textElement->getText();
                }
            }
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractTextFromElement($cellElement) . ' ';
                    }
                }
            }
        } elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $subElement) {
                $text .= $this->extractTextFromElement($subElement) . ' ';
            }
        }
        
        return $text;
    }
}
