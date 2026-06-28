<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Services\DataTransfer\ExportService;
use App\Services\DataTransfer\ImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataExportController extends Controller
{
    public function export(Request $request, ExportService $service): BinaryFileResponse
    {
        $type = (string) $request->query('type', 'products');
        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        abort_unless(in_array($type, ExportService::TYPES, true), 404);

        $data = $service->build($type);
        $writer = $format === 'csv' ? Excel::CSV : Excel::XLSX;
        $filename = $type.'_'.now()->format('Ymd_His').'.'.$format;

        return ExcelFacade::download(new ArrayExport($data['headings'], $data['rows']), $filename, $writer);
    }

    /** Downloadable CSV template (heading row only) for an import type. */
    public function template(Request $request, ImportService $service): BinaryFileResponse
    {
        $type = (string) $request->query('type', 'products');

        abort_unless(in_array($type, ImportService::TYPES, true), 404);

        return ExcelFacade::download(
            new ArrayExport($service->template($type), []),
            $type.'_template.csv',
            Excel::CSV,
        );
    }
}
