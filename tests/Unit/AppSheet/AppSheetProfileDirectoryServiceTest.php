<?php

namespace Tests\Unit\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Services\AppSheet\AppSheetProfileDirectoryService;
use App\Services\AppSheet\GoogleSheetsReader;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AppSheetProfileDirectoryServiceTest extends TestCase
{
    public function test_requester_is_matched_case_insensitively_with_trimmed_profile_metadata(): void
    {
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('requesterProfiles')->once()->andReturn([[
            'NAMA' => ' Hadi   Purnomo ',
            'REGU' => ' A ',
            'SHIFT' => ' 1 ',
            'JABATAN' => ' Teknisi Senior ',
            'IMG' => ' Data_Images/Hadi.png ',
        ]]);
        $directory = new AppSheetProfileDirectoryService($reader);

        self::assertSame([
            'name' => 'hadi purnomo',
            'position' => 'Teknisi Senior',
            'regu' => 'A',
            'shift' => '1',
            'image_path' => 'Data_Images/Hadi.png',
            'initials' => 'HP',
        ], $directory->resolve('  hadi purnomo  '));
        self::assertSame('HP', $directory->resolve('HADI PURNOMO')['initials']);
    }

    public function test_missing_profile_and_profile_sheet_failure_return_safe_initial_fallback(): void
    {
        Log::spy();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('requesterProfiles')->once()->andThrow(
            new GoogleSheetsException('Sensitive sheet failure'),
        );
        $directory = new AppSheetProfileDirectoryService($reader);

        self::assertSame([
            'name' => 'Unknown Requester',
            'position' => '',
            'regu' => '',
            'shift' => '',
            'image_path' => '',
            'initials' => 'UR',
        ], $directory->resolve('Unknown Requester'));
        Log::shouldHaveReceived('notice')->once()->with(
            'AppSheet requester profile directory unavailable.',
            ['requires_reconnect' => false],
        );
    }
}
