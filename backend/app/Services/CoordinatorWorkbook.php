<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CoordinatorWorkbook
{
    public const ALIASES = [
        'student_id' => ['student id', 'student number', 'student no', 'id number', 'school id', 'id no'],
        'first_name' => ['first name', 'given name'],
        'middle_name' => ['middle name'],
        'last_name' => ['last name', 'surname', 'family name'],
        'academic_term' => ['academic term', 'term', 'semester'],
        'name' => ['student name', 'full name', 'name of student', 'name'],
        'email' => ['student email', 'email', 'email address', 'school email', 'institutional email'],
        'program' => ['program', 'academic program', 'course', 'degree program'],
        'year_level' => ['year level', 'year'],
        'student_address' => ['student address', 'residential address', 'home address'],
        'host_code' => ['hte id', 'host id', 'hte code', 'host code'],
        'host_name' => ['hte name', 'host name', 'company name', 'host establishment', 'establishment name'],
        'host_address' => ['hte address', 'host address', 'company address', 'address', 'general location address'],
        'city' => ['city', 'location', 'municipality', 'hte location'],
        'latitude' => ['latitude', 'lat', 'hte latitude'],
        'longitude' => ['longitude', 'lng', 'lon', 'hte longitude'],
        'enrolled_on' => ['enrolled on', 'enrollment date'],
        'starts_on' => ['starts on', 'start date', 'deployment date', 'placement deployment date'],
        'ends_on' => ['ends on', 'end date', 'completion date'],
        'opportunity_id' => ['opportunity database id', 'internmatch opportunity id'],
        'source_opportunity' => ['opportunity id'],
        'opportunity_title' => ['opportunity title', 'internship opportunity training area', 'internship position', 'training area'],
        'tasks' => ['tasks', 'internship duties tasks', 'duties', 'job description'],
        'capacity' => ['capacity', 'available slots', 'opportunity capacity'],
        'host_capacity' => ['internship capacity', 'host capacity', 'hte capacity'],
        'reference_number' => ['reference number', 'moa reference', 'moa number', 'agreement number', 'agreement reference'],
        'effective_on' => ['effective date', 'effective on', 'moa effective date', 'agreement start date'],
        'expires_on' => ['expiry date', 'expires on', 'moa valid until', 'expiration date', 'agreement end date'],
        'notes' => ['notes', 'remarks', 'notes limitations'],
        'source_status' => ['moa status', 'agreement status', 'opportunity status', 'posting status'],
        'placement_status' => ['placement status', 'internship status'],
    ];

    public static function key(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)));
    }

    public static function read(string $path): array
    {
        try {
            $zip = new \ZipArchive;
            if ($zip->open($path) === true) {
                $size = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $size += $zip->statIndex($i)['size'];
                }
                $zip->close();
                if ($size > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Expanded workbook exceeds 50 MB.');
                }
            }
            $type = IOFactory::identify($path, ['Xlsx', 'Xls']);
            $reader = IOFactory::createReader($type);
            $info = $reader->listWorksheetInfo($path);
            if (count($info) > 25 || max(array_column($info, 'totalRows')) > 10000 || array_sum(array_column($info, 'totalRows')) > 25000 || max(array_column($info, 'totalColumns')) > 100) {
                throw new \RuntimeException('Limit: 25 sheets, 10,000 worksheet rows and 100 columns.');
            }
            // Styles retain date and zero-padded ID formats. Never calculate formulas or load charts.
            $reader->setReadEmptyCells(false);
            $book = $reader->load($path);
            $sheets = [];
            $nonemptyRows = 0;
            foreach ($book->getWorksheetIterator() as $sheet) {
                $matrix = [];
                $columns = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_fill(0, $columns, '');
                    $cells = $row->getCellIterator();
                    // Iterating absent cells creates them in memory; formatted templates have thousands of blanks.
                    $cells->setIterateOnlyExistingCells(true);
                    foreach ($cells as $cell) {
                        $value = $cell->getValue();
                        if ($cell->isFormula()) {
                            $value = '[Formula: enter a confirmed value]';
                        } elseif ($value !== null && Date::isDateTime($cell) && is_numeric($value)) {
                            $value = Date::excelToDateTimeObject($value)->format('Y-m-d');
                        } elseif ($value !== null) {
                            $value = $cell->getFormattedValue();
                        }
                        $values[Coordinate::columnIndexFromString($cell->getColumn()) - 1] = $value === null ? '' : trim((string) $value);
                    }
                    if (array_filter($values, fn ($value) => $value !== '')) {
                        if (++$nonemptyRows > 10000) {
                            throw new \RuntimeException('Limit: 10,000 nonempty rows.');
                        }
                        $matrix[$row->getRowIndex()] = $values;
                    }
                }
                $best = 0;
                $headerRow = 1;
                $mapping = [];
                foreach (array_slice($matrix, 0, 25, true) as $number => $values) {
                    $map = self::detect($values);
                    if (count($map) > $best) {
                        $best = count($map);
                        $headerRow = $number;
                        $mapping = $map;
                    }
                }
                if (! $matrix) {
                    continue;
                }
                if (! $best) {
                    $headerRow = array_key_first($matrix);
                }
                $rows = [];
                foreach ($matrix as $number => $values) {
                    if ($number <= $headerRow || ! array_filter($values, fn ($v) => $v !== '')) {
                        continue;
                    }
                    if (in_array('COLOR KEY', $values, true)) {
                        continue;
                    }
                    $rows[] = ['source_row' => $number, 'cells' => $values];
                }
                $sheets[] = ['name' => $sheet->getTitle(), 'header_row' => $headerRow, 'headers' => $matrix[$headerRow], 'mapping' => $mapping, 'rows' => $rows];
            }
            $book->disconnectWorksheets();
            unset($book, $sheet, $row, $cells, $cell);
            gc_collect_cycles();
            if (! $sheets) {
                throw new \RuntimeException('No recognizable student or host columns found. Add a header row such as Student ID, Student Name, HTE Name.');
            }

            return $sheets;
        } catch (\Throwable $error) {
            if (isset($book)) {
                $book->disconnectWorksheets();
            }
            throw ValidationException::withMessages(['file' => 'Cannot read this Excel file. Use an unencrypted .xlsx or .xls with at most 10,000 rows and 100 columns. '.$error->getMessage()]);
        }
    }

    public static function detect(array $headers): array
    {
        $mapping = [];
        foreach ($headers as $index => $header) {
            foreach (self::ALIASES as $field => $aliases) {
                if (in_array(self::key((string) $header), $aliases, true) && ! isset($mapping[$field])) {
                    $mapping[$field] = $index;
                }
            }
        }

        return $mapping;
    }

    public static function rows(array $sheets, int $selected, array $mapping): array
    {
        $normalize = function ($sheet, $map) {
            return array_map(function ($row) use ($sheet, $map) {
                $data = ['source' => $sheet['name'].':'.$row['source_row']];
                foreach ($map as $field => $index) {
                    if ($index !== null && $index !== '') {
                        $data[$field] = $row['cells'][(int) $index] ?? '';
                    }
                }

                return $data;
            }, $sheet['rows']);
        };
        $rows = $normalize($sheets[$selected], $mapping);
        // Supplement only unique reference rows, joined by identifiers, never by student name.
        $students = [];
        $hosts = [];
        foreach ($sheets as $index => $sheet) {
            if ($index === $selected) {
                continue;
            }
            foreach ($normalize($sheet, $sheet['mapping']) as $ref) {
                if (! empty($ref['student_id']) && empty($ref['host_code']) && (isset($ref['program']) || isset($ref['name']))) {
                    $students[$ref['student_id']][] = $ref;
                }
                if (! empty($ref['host_code']) && ! empty($ref['host_name']) && empty($ref['student_id'])) {
                    $hosts[$ref['host_code']][] = $ref;
                }
            }
        }
        foreach ($rows as &$row) {
            foreach ([$students[$row['student_id'] ?? ''] ?? [], $hosts[$row['host_code'] ?? ''] ?? []] as $matches) {
                if (count($matches) === 1) {
                    foreach ($matches[0] as $key => $value) {
                        if ($key !== 'source' && empty($row[$key])) {
                            $row[$key] = $value;
                        }
                    }
                } elseif (count($matches) > 1) {
                    $row['reference_warning'] = 'Multiple reference rows share this ID; enter confirmed values manually.';
                }
            }
            $row['kind'] = isset($mapping['student_id']) || isset($mapping['name']) ? 'student' : 'host';
        }

        return $rows;
    }
}
