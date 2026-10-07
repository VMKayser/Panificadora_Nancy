<?php

namespace Tests\Unit;

use App\Support\StorageSymlink;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class StorageSymlinkTest extends TestCase
{
    protected string $sandbox;
    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        if (DIRECTORY_SEPARATOR === '\\' || !function_exists('symlink')) {
            $this->markTestSkipped('El entorno no soporta enlaces simbólicos.');
        }

        $this->filesystem = new Filesystem();
        $this->sandbox = storage_path('framework/testing/storage-link');
        $this->filesystem->deleteDirectory($this->sandbox);
        $this->filesystem->makeDirectory($this->sandbox, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->sandbox);
        parent::tearDown();
    }

    public function test_it_creates_symlink_when_missing(): void
    {
        $target = $this->sandbox . '/target';
        $link = $this->sandbox . '/public-storage';

        StorageSymlink::ensure($this->filesystem, $link, $target);

        $this->assertTrue(is_link($link));
        $this->assertDirectoryExists($target);
        $this->assertSame(realpath($target), realpath(readlink($link)));
    }

    public function test_it_relinks_when_pointing_to_wrong_target(): void
    {
        $target = $this->sandbox . '/target';
        $link = $this->sandbox . '/public-storage';
        $otherTarget = $this->sandbox . '/other';

        $this->filesystem->makeDirectory($otherTarget, 0775, true);
        symlink($otherTarget, $link);

        StorageSymlink::ensure($this->filesystem, $link, $target);

        $this->assertTrue(is_link($link));
        $this->assertSame(realpath($target), realpath(readlink($link)));
    }
}
