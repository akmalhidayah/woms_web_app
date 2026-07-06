<?php

namespace Tests\Unit;

use App\Support\SignatureImageStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SignatureImageStorageTest extends TestCase
{
    public function test_blank_signature_png_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        SignatureImageStorage::storeDataUri(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAA8CAYAAACtrX6oAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAApUlEQVR4nO3RAQkAIBDAQLV/5zeFCOMuwWB7ZhZd53cAbxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3CcwXEGxxkcZ3DcBc3jA3WChFE9AAAAAElFTkSuQmCC',
            'signatures',
            'blank'
        );
    }

    public function test_signature_png_with_visible_strokes_is_stored(): void
    {
        Storage::fake('public');

        $path = SignatureImageStorage::storeDataUri(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAA8CAYAAACtrX6oAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAB1ElEQVR4nO3bwW3DMAyF4brIADlmh+w/infw0Rs4JwKB0SYSRYrk8/vPSSHpg2QHdpfjOH4Ybr/RA2C+ERg8AoNHYPAIDB6BwSMweAQGj8DgERg8AoNH4ALdH0/1A4Ob5UCYbSOwEoETZgErpTqiLSdWsfvjeVivQRpgmdgVkT1gpSXDA///Jrdv6zJ7LDNrRR1Zh3Dgb5NERJ4BK4UB9xxJKMgzYaUQYM31pjpyy5w95jgd+NNE921d0I7sKFhpKvA33JbPnT+btWhYaRpwK+7od6LLAitNAR6BqoKcDVZyB7b4jZv5yI64M+7JDdh652VDzg4ruQB7HqvRR3YVWMkceAZAFHLW6+ynTIFnLvzMI7sirGQGHLGrvJErw0omwJmvix43dNq/G9EwcJZHfRbISLCSGjh61/6V9siudmfckwo4I67Ug4wMK3UDZ8Z9z+IVmEzz0dYFXAVX0iJnnIu2ZuBquNIV3xx5b/ityuyL0jK+fVuX7PPQpn7xvdKCyFjPu7nSHLSpdnDVhZFxI+/Yc93A1Rem+vh76wK+2uIgFP7iO/Mtzf8mMZ8IDB6BwSMweAQGj8DgERg8AoNHYPAIDB6BwSMweAQGj8DgERg8AoP3Ak1UWTxLpAzmAAAAAElFTkSuQmCC',
            'signatures',
            'valid'
        );

        Storage::disk('public')->assertExists($path);
    }
}
