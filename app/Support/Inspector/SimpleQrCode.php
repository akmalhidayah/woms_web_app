<?php

namespace App\Support\Inspector;

use InvalidArgumentException;

/**
 * Dependency-free QR encoder for short WOMS public URLs.
 *
 * It emits QR Model 2, byte mode, error-correction level L, versions 1-6.
 * Keeping the encoder local avoids sending internal URLs to a third-party service.
 */
final class SimpleQrCode
{
    /** @var array<int, array{data: int, total: int, blocks: int}> */
    private const VERSIONS = [
        1 => ['data' => 19, 'total' => 26, 'blocks' => 1],
        2 => ['data' => 34, 'total' => 44, 'blocks' => 1],
        3 => ['data' => 55, 'total' => 70, 'blocks' => 1],
        4 => ['data' => 80, 'total' => 100, 'blocks' => 1],
        5 => ['data' => 108, 'total' => 134, 'blocks' => 1],
        6 => ['data' => 136, 'total' => 172, 'blocks' => 2],
    ];

    /** @var array<int, array<int, int>> */
    private const ALIGNMENT = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
    ];

    /**
     * @return array<int, array<int, bool>>
     */
    public function matrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $version = $this->versionFor(count($bytes));
        $data = $this->dataCodewords($bytes, self::VERSIONS[$version]['data']);
        $codewords = $this->withErrorCorrection($data, $version);
        $size = 17 + 4 * $version;
        $modules = array_fill(0, $size, array_fill(0, $size, null));
        $function = array_fill(0, $size, array_fill(0, $size, false));

        $this->drawFinder($modules, $function, 3, 3);
        $this->drawFinder($modules, $function, $size - 4, 3);
        $this->drawFinder($modules, $function, 3, $size - 4);
        foreach (self::ALIGNMENT[$version] as $y) {
            foreach (self::ALIGNMENT[$version] as $x) {
                if ($function[$y][$x]) {
                    continue;
                }
                $this->drawAlignment($modules, $function, $x, $y);
            }
        }
        for ($i = 8; $i < $size - 8; $i++) {
            if (! $function[6][$i]) {
                $this->setFunction($modules, $function, $i, 6, $i % 2 === 0);
            }
            if (! $function[$i][6]) {
                $this->setFunction($modules, $function, 6, $i, $i % 2 === 0);
            }
        }
        $this->reserveFormat($modules, $function);
        $this->setFunction($modules, $function, 8, $size - 8, true);

        $bits = [];
        foreach ($codewords as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = (($byte >> $i) & 1) !== 0;
            }
        }
        $this->drawData($modules, $function, $bits, 0);
        $this->drawFormat($modules, 0);

        return array_map(fn (array $row): array => array_map(fn ($value): bool => (bool) $value, $row), $modules);
    }

    public function svg(string $text, int $scale = 8, int $border = 4): string
    {
        $matrix = $this->matrix($text);
        $moduleCount = count($matrix);
        $dimension = ($moduleCount + $border * 2) * $scale;
        $paths = [];
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $paths[] = 'M'.(($x + $border) * $scale).' '.(($y + $border) * $scale).'h'.$scale.'v'.$scale.'h-'.$scale.'z';
                }
            }
        }
        $description = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="'.$dimension.'" height="'.$dimension.'" viewBox="0 0 '.$dimension.' '.$dimension.'" role="img" aria-label="QR Code">'
            .'<title>QR Code WOMS</title><desc>'.$description.'</desc><rect width="100%" height="100%" fill="#fff"/>'
            .'<path d="'.implode('', $paths).'" fill="#000" shape-rendering="crispEdges"/></svg>';
    }

    private function versionFor(int $byteCount): int
    {
        foreach (self::VERSIONS as $version => $spec) {
            // Mode (4), character count (8), bytes, terminator (up to 4).
            if (4 + 8 + $byteCount * 8 <= $spec['data'] * 8) {
                return $version;
            }
        }

        throw new InvalidArgumentException('URL QR terlalu panjang. Gunakan base URL publik yang lebih pendek dari 120 karakter.');
    }

    /**
     * @param  array<int, int>  $bytes
     * @return array<int, int>
     */
    private function dataCodewords(array $bytes, int $capacity): array
    {
        $bits = [false, true, false, false]; // Byte mode 0100.
        $length = count($bytes);
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = (($length >> $i) & 1) !== 0;
        }
        foreach ($bytes as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = (($byte >> $i) & 1) !== 0;
            }
        }
        $remaining = $capacity * 8 - count($bits);
        array_push($bits, ...array_fill(0, min(4, max(0, $remaining)), false));
        while (count($bits) % 8 !== 0) {
            $bits[] = false;
        }

        $result = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $value = 0;
            foreach ($chunk as $bit) {
                $value = ($value << 1) | ($bit ? 1 : 0);
            }
            $result[] = $value;
        }
        $pads = [0xEC, 0x11];
        while (count($result) < $capacity) {
            $result[] = $pads[(count($result) - (int) ceil(count($bits) / 8)) % 2];
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private function withErrorCorrection(array $data, int $version): array
    {
        $spec = self::VERSIONS[$version];
        $blockCount = $spec['blocks'];
        $eccLength = intdiv($spec['total'] - $spec['data'], $blockCount);
        $shortLength = intdiv($spec['data'], $blockCount);
        $longBlocks = $spec['data'] % $blockCount;
        $blocks = [];
        $offset = 0;
        for ($i = 0; $i < $blockCount; $i++) {
            $length = $shortLength + ($i >= $blockCount - $longBlocks ? 1 : 0);
            $block = array_slice($data, $offset, $length);
            $offset += $length;
            $blocks[] = ['data' => $block, 'ecc' => $this->remainder($block, $eccLength)];
        }

        $result = [];
        $maxData = max(array_map(fn (array $block): int => count($block['data']), $blocks));
        for ($i = 0; $i < $maxData; $i++) {
            foreach ($blocks as $block) {
                if (isset($block['data'][$i])) {
                    $result[] = $block['data'][$i];
                }
            }
        }
        for ($i = 0; $i < $eccLength; $i++) {
            foreach ($blocks as $block) {
                $result[] = $block['ecc'][$i];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private function remainder(array $data, int $degree): array
    {
        $generator = [1];
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($generator) + 1, 0);
            foreach ($generator as $j => $coefficient) {
                $next[$j] ^= $coefficient;
                $next[$j + 1] ^= $this->multiply($coefficient, $root);
            }
            $generator = $next;
            $root = $this->multiply($root, 2);
        }

        $result = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $result[0];
            array_shift($result);
            $result[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $result[$i] ^= $this->multiply($generator[$i + 1], $factor);
            }
        }

        return $result;
    }

    private function multiply(int $x, int $y): int
    {
        $result = 0;
        while ($y > 0) {
            if ($y & 1) {
                $result ^= $x;
            }
            $x = ($x << 1) ^ (($x & 0x80) ? 0x11D : 0);
            $y >>= 1;
        }

        return $result;
    }

    private function drawFinder(array &$modules, array &$function, int $centerX, int $centerY): void
    {
        $size = count($modules);
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $centerX + $dx;
                $y = $centerY + $dy;
                if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
                    continue;
                }
                $distance = max(abs($dx), abs($dy));
                $this->setFunction($modules, $function, $x, $y, $distance !== 2 && $distance !== 4);
            }
        }
    }

    private function drawAlignment(array &$modules, array &$function, int $centerX, int $centerY): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->setFunction($modules, $function, $centerX + $dx, $centerY + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
    }

    private function reserveFormat(array &$modules, array &$function): void
    {
        $size = count($modules);
        for ($i = 0; $i <= 5; $i++) {
            $this->setFunction($modules, $function, 8, $i, false);
        }
        $this->setFunction($modules, $function, 8, 7, false);
        $this->setFunction($modules, $function, 8, 8, false);
        $this->setFunction($modules, $function, 7, 8, false);
        for ($i = 9; $i < 15; $i++) {
            $this->setFunction($modules, $function, 14 - $i, 8, false);
        }
        for ($i = 0; $i < 8; $i++) {
            $this->setFunction($modules, $function, $size - 1 - $i, 8, false);
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunction($modules, $function, 8, $size - 15 + $i, false);
        }
    }

    /**
     * @param  array<int, bool>  $bits
     */
    private function drawData(array &$modules, array $function, array $bits, int $mask): void
    {
        $size = count($modules);
        $bitIndex = 0;
        $upward = true;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right--;
            }
            for ($vertical = 0; $vertical < $size; $vertical++) {
                $y = $upward ? $size - 1 - $vertical : $vertical;
                for ($offset = 0; $offset < 2; $offset++) {
                    $x = $right - $offset;
                    if ($function[$y][$x]) {
                        continue;
                    }
                    $value = $bits[$bitIndex] ?? false;
                    $bitIndex++;
                    if ($this->mask($mask, $x, $y)) {
                        $value = ! $value;
                    }
                    $modules[$y][$x] = $value;
                }
            }
            $upward = ! $upward;
        }
    }

    private function drawFormat(array &$modules, int $mask): void
    {
        $data = (1 << 3) | $mask; // Error-correction level L = binary 01.
        $remainder = $data;
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) ? 0x537 : 0);
        }
        $bits = (($data << 10) | $remainder) ^ 0x5412;
        $size = count($modules);
        $bit = fn (int $i): bool => (($bits >> $i) & 1) !== 0;

        for ($i = 0; $i <= 5; $i++) {
            $modules[$i][8] = $bit($i);
        }
        $modules[7][8] = $bit(6);
        $modules[8][8] = $bit(7);
        $modules[8][7] = $bit(8);
        for ($i = 9; $i < 15; $i++) {
            $modules[8][14 - $i] = $bit($i);
        }
        for ($i = 0; $i < 8; $i++) {
            $modules[8][$size - 1 - $i] = $bit($i);
        }
        for ($i = 8; $i < 15; $i++) {
            $modules[$size - 15 + $i][8] = $bit($i);
        }
        $modules[$size - 8][8] = true;
    }

    private function mask(int $mask, int $x, int $y): bool
    {
        return match ($mask) {
            0 => ($x + $y) % 2 === 0,
            default => false,
        };
    }

    private function setFunction(array &$modules, array &$function, int $x, int $y, bool $dark): void
    {
        $modules[$y][$x] = $dark;
        $function[$y][$x] = true;
    }
}
