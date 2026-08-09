<?php
/**
 * LearnerModelTest - Unit tests for LearnerResult model
 */

use PHPUnit\Framework\TestCase;
use App\Models\LearnerResult;

require_once __DIR__ . '/../../vendor/autoload.php';

class LearnerModelTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Create in-memory SQLite for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test table
        $this->pdo->exec("CREATE TABLE itgk_learner_result (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            s_no INTEGER,
            receiving_date DATE,
            itgk_code TEXT,
            learner_code TEXT,
            learner_name TEXT,
            father_name TEXT,
            total_marks INTEGER,
            marks_obtained INTEGER,
            percentage REAL,
            result TEXT,
            certificate_no TEXT,
            course_name TEXT,
            exam_name TEXT,
            exam_date DATE,
            status TEXT DEFAULT 'Not Received',
            remark TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP
        )");
        
        // Insert test data
        $this->pdo->exec("INSERT INTO itgk_learner_result (learner_name, course_name, exam_name, result, status) VALUES('Test Learner', 'RS-CIT', 'Theory', 'PASS', 'Available')");
    }

    public function testGetAnalyticsReturnsArray(): void
    {
        $learner = new LearnerResult();
        $analytics = $learner->getAnalytics();
        
        $this->assertIsArray($analytics);
    }

    public function testCountByResultReturnsInteger(): void
    {
        $learner = new LearnerResult();
        $count = $learner->countByResult('PASS');
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testSearchReturnsResults(): void
    {
        $learner = new LearnerResult();
        $result = $learner->search('Test');
        
        $this->assertIsArray($result);
    }
}