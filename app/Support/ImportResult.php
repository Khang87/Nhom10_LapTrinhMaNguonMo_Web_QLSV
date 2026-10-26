<?php

namespace App\Support;

final class ImportResult
{
    /**
     * @param  list<array{row: int, reason: string}>  $skipped
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public array $skipped = [],
    ) {}

    public function total(): int
    {
        return $this->created + $this->updated + count($this->skipped);
    }

    public function skip(int $row, string $reason): void
    {
        $this->skipped[] = ['row' => $row, 'reason' => $reason];
    }

    public function summary(): string
    {
        return sprintf(
            'Nhập %d dòng: thêm mới %d, cập nhật %d, bỏ qua %d.',
            $this->total(),
            $this->created,
            $this->updated,
            count($this->skipped),
        );
    }

    /**
     * @return array{created: int, updated: int, skipped: list<array{row: int, reason: string}>, total: int, summary: string}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'total' => $this->total(),
            'summary' => $this->summary(),
        ];
    }
}
