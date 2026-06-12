<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;
use Throwable;

class PdfMergeService
{
    /**
     * @param  array<int, string|null>  $pdfOutputs
     * @param  array<string, mixed>  $context
     */
    public function merge(array $pdfOutputs, string $title = '', array $context = []): string
    {
        $pdfOutputs = array_values(array_filter(
            $pdfOutputs,
            static fn ($pdfOutput): bool => is_string($pdfOutput) && trim($pdfOutput) !== ''
        ));

        if ($pdfOutputs === []) {
            return '';
        }

        $temporaryFiles = [];
        $mergedFile = null;

        try {
            foreach ($pdfOutputs as $pdfOutput) {
                $temporaryFile = tempnam(sys_get_temp_dir(), 'woms-pdf-');

                if ($temporaryFile === false) {
                    continue;
                }

                file_put_contents($temporaryFile, $pdfOutput);
                $temporaryFiles[] = $temporaryFile;
            }

            $mergedFile = tempnam(sys_get_temp_dir(), 'woms-merged-');

            if ($mergedFile !== false) {
                $externalMerge = $this->mergeWithSystemTool($temporaryFiles, $mergedFile, $context);

                if ($externalMerge !== null) {
                    return $externalMerge;
                }
            }

            $compatibleFiles = array_values(array_filter(
                $temporaryFiles,
                function (string $temporaryFile) use ($context): bool {
                    try {
                        $validator = new Fpdi;
                        $pageCount = $validator->setSourceFile($temporaryFile);

                        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                            $validator->importPage($pageNumber);
                        }

                        return true;
                    } catch (Throwable $exception) {
                        Log::warning('Skipping incompatible PDF during merge.', $context + [
                            'error' => $exception->getMessage(),
                        ]);

                        return false;
                    }
                }
            ));

            if ($compatibleFiles === []) {
                return $pdfOutputs[0];
            }

            if (count($compatibleFiles) === 1) {
                return file_get_contents($compatibleFiles[0]) ?: $pdfOutputs[0];
            }

            $pdf = new Fpdi;

            if ($title !== '') {
                $pdf->SetTitle($title, true);
            }

            foreach ($compatibleFiles as $temporaryFile) {
                $pageCount = $pdf->setSourceFile($temporaryFile);

                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $templateId = $pdf->importPage($pageNumber);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';

                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            return $pdf->Output('S');
        } catch (Throwable $exception) {
            Log::warning('PDF merge failed. Returning the first available document.', $context + [
                'pdf_count' => count($pdfOutputs),
                'error' => $exception->getMessage(),
            ]);

            return $pdfOutputs[0];
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }

            if (is_string($mergedFile) && is_file($mergedFile)) {
                @unlink($mergedFile);
            }
        }
    }

    /**
     * @param  list<string>  $inputFiles
     * @param  array<string, mixed>  $context
     */
    private function mergeWithSystemTool(array $inputFiles, string $outputFile, array $context): ?string
    {
        $commands = [
            array_merge(['qpdf', '--empty', '--pages'], $inputFiles, ['--', $outputFile]),
            array_merge(['pdfunite'], $inputFiles, [$outputFile]),
            array_merge(['gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite', '-sOutputFile='.$outputFile], $inputFiles),
            array_merge(['gswin64c', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite', '-sOutputFile='.$outputFile], $inputFiles),
        ];

        foreach ($commands as $command) {
            try {
                $process = new Process($command, null, null, null, 30);
                $process->run();

                if ($process->isSuccessful() && is_file($outputFile) && filesize($outputFile) > 0) {
                    return file_get_contents($outputFile) ?: null;
                }
            } catch (Throwable $exception) {
                Log::debug('PDF merge system tool failed.', $context + [
                    'tool' => $command[0] ?? 'unknown',
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return null;
    }
}
