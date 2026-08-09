<?php
/**
 * CertificateModelTest - Unit tests for Certificate model
 */

use PHPUnit\Framework\TestCase;
use App\Models\Certificate;

require_once __DIR__ . '/../../vendor/autoload.php';

class CertificateModelTest extends TestCase
{
    private $pdo;
    private $dbMock;

    protected function setUp(): void
    {
        // Create in-memory SQLite for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test table
        $this->pdo->exec("CREATE TABLE itgk_certificate (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_name TEXT,
            receiving_date DATE,
            exam_name TEXT,
            exam_date DATE,
            itgk_code TEXT,
            district TEXT,
            absent INTEGER,
            fail INTEGER,
            pass INTEGER,
            ufm INTEGER,
            grand_total INTEGER,
            packet_no TEXT,
            cert_no_from TEXT,
            cert_no_to TEXT,
            current_location TEXT,
            status TEXT,
            remark TEXT,
            receiver_name TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP
        )");
    }

    public function testGetAnalyticsReturnsArray(): void
    {
        $cert = new Certificate();
        $analytics = $cert->getAnalytics();
        
        $this->assertIsArray($analytics);
    }

    public function testGetAllReturnsEmptyArrayWhenNoData(): void
    {
        $cert = new Certificate();
        $result = $cert->getAll(10);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSearchReturnsEmptyArrayWhenNoMatch(): void
    {
        $cert = new Certificate();
        $result = $cert->search('nonexistent');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}