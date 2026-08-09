<?php
use PHPUnit\Framework\TestCase;
use App\Models\Certificate;

class CertificateTest extends TestCase {
    private $pdoMock;
    private $stmtMock;

    protected function setUp(): void {
        // Mock PDO
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        
        // Inject into global scope for the Model
        $GLOBALS['conn'] = $this->pdoMock;
    }

    public function testGetCountReturnsInteger() {
        $model = new Certificate();
        $count = $model->count();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
