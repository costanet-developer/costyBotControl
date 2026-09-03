<?php

namespace Tests\Unit;

use App\Services\SecureLocalFile;
use PHPUnit\Framework\TestCase;

class SecureLocalFileTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/costy-secure-file-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0700, true);
        file_put_contents($this->root.'/documento.jpg', 'imagen');
    }

    protected function tearDown(): void
    {
        @unlink($this->root.'/documento.jpg');
        @rmdir($this->root);

        parent::tearDown();
    }

    public function test_resuelve_un_archivo_dentro_de_la_raiz_permitida(): void
    {
        $resolved = (new SecureLocalFile)->resolve($this->root.'/documento.jpg', [$this->root]);

        $this->assertSame(realpath($this->root.'/documento.jpg'), $resolved);
    }

    public function test_rechaza_un_archivo_fuera_de_la_raiz_permitida(): void
    {
        $outside = tempnam(sys_get_temp_dir(), 'costy-outside-');

        try {
            $this->assertNull((new SecureLocalFile)->resolve($outside, [$this->root]));
        } finally {
            @unlink($outside);
        }
    }

    public function test_rechaza_rutas_inexistentes(): void
    {
        $this->assertNull((new SecureLocalFile)->resolve($this->root.'/no-existe.jpg', [$this->root]));
    }
}
