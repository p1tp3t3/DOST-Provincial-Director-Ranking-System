<?php

namespace App\Helpers;

class CSVToDFHelper
{
    /**
     * Create a new class instance.
     */
    public static function get_df(string $filename): array
    {
        // 1. Securely locate file within Laravel storage disk
        $path = "app/private/data/{$filename}";
        

        $file = fopen(storage_path($path), 'r');
        $header = fgetcsv($file);
        $data = [];

        // 2. Process rows safely
        while (($row = fgetcsv($file)) !== false) {
            // Prevent array_combine crash if columns count mismatches header count
            if (count($header) === count($row)) {
                $data[] = array_combine($header, $row);
            }
        }
        
        fclose($file);
        return $data;
    }
}
