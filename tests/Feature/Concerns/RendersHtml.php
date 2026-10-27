<?php

namespace Tests\Feature\Concerns;

/**
 * Mẹo soi HTML cho assertion: form lọc và select vẫn liệt kê đủ mọi lớp nên assertSee cả trang
 * dễ cho điểm xanh giả. Chỉ soi vùng bảng thì kết luận "dòng này biến mất" mới đáng tin.
 */
trait RendersHtml
{
    /** Nội dung giữa <tbody> và </tbody>, rỗng khi trang không có bảng. */
    protected function rowsOnly(string $content): string
    {
        $start = strpos($content, '<tbody');
        $end = strpos($content, '</tbody>');

        return $start === false || $end === false ? '' : substr($content, $start, $end - $start);
    }
}
