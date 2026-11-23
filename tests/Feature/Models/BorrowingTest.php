<?php

namespace Tests\Feature\Models;

use App\Models\Borrowing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BorrowingTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function approvedOrBorrowedStatuses(): array
    {
        return [
            'approved' => ['approved'],
            'borrowed' => ['borrowed'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nonOverdueCases(): array
    {
        return [
            'pending past the due date' => ['pending', '2026-11-22'],
            'returned past the due date' => ['returned', '2026-11-22'],
            'rejected past the due date' => ['rejected', '2026-11-22'],
            'borrowed due today' => ['borrowed', '2026-11-23'],
            'borrowed due in the future' => ['borrowed', '2026-11-24'],
        ];
    }

    #[DataProvider('approvedOrBorrowedStatuses')]
    public function test_approved_or_borrowed_request_is_overdue_after_its_return_date(string $status): void
    {
        $this->travelTo('2026-11-23 12:00:00');
        $borrowing = new Borrowing([
            'status' => $status,
            'return_date' => '2026-11-22',
        ]);

        $this->assertTrue($borrowing->isOverdue());
    }

    #[DataProvider('nonOverdueCases')]
    public function test_request_is_not_overdue_for_other_statuses_or_on_its_return_date(
        string $status,
        string $returnDate,
    ): void {
        $this->travelTo('2026-11-23 12:00:00');
        $borrowing = new Borrowing([
            'status' => $status,
            'return_date' => $returnDate,
        ]);

        $this->assertFalse($borrowing->isOverdue());
    }
}
