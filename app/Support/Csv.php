<?php

namespace App\Support;

use Illuminate\Http\Response;

class Csv
{
    /**
     * Tab-separated UTF-8 (BOM) so Excel opens Vietnamese text and keeps scores as numbers.
     */
    public static function download(string $filename, array $header, iterable $rows): Response
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $header, "\t");
        foreach ($rows as $row) {
            fputcsv($handle, $row, "\t");
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
