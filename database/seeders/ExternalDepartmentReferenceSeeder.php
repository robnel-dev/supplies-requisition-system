<?php

namespace Database\Seeders;

use App\Models\ExternalDepartmentReference;
use Illuminate\Database\Seeder;
use SplFileObject;

class ExternalDepartmentReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/external_department_references.csv');

        if (! file_exists($path)) {
            $this->command?->warn("External department reference CSV not found: {$path}");

            return;
        }

        $rows = $this->readCsv($path);

        foreach (array_chunk($rows, 100) as $chunk) {
            ExternalDepartmentReference::upsert(
                $chunk,
                ['external_id'],
                [
                    'company_code',
                    'department_code',
                    'name',
                    'cost_center',
                    'branch',
                    'area',
                    'remarks',
                    'active',
                    'updated_at',
                ]
            );
        }

        $this->command?->info('External department references imported: '.count($rows).' records.');
    }

    private function readCsv(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $headers = null;
        $rows = [];
        $now = now();

        foreach ($file as $line) {
            if ($line === [null] || $line === false) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(
                    fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
                    $line
                );

                continue;
            }

            $line = array_pad($line, count($headers), null);
            $record = array_combine($headers, array_slice($line, 0, count($headers)));

            if (! $record || blank($record['department_code'] ?? null)) {
                continue;
            }

            $rows[] = [
                'external_id' => filled($record['id'] ?? null) ? trim((string) $record['id']) : null,
                'company_code' => trim((string) ($record['company_code'] ?? '')),
                'department_code' => trim((string) ($record['department_code'] ?? '')),
                'name' => trim((string) ($record['name'] ?? '')),
                'cost_center' => trim((string) ($record['cost_center'] ?? '')),
                'branch' => trim((string) ($record['branch'] ?? '')),
                'area' => filled($record['area'] ?? null) ? trim((string) $record['area']) : null,
                'remarks' => filled($record['remarks'] ?? null) ? trim((string) $record['remarks']) : null,
                'active' => filter_var($record['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}
