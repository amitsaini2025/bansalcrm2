<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ClientAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Client record merging
 *
 * Methods moved from ClientsController:
 * - merge_records
 */
class ClientMergeController extends Controller
{
    use ClientAuthorization;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Merge two client records into one (related rows re-pointed, source soft-deleted).
     */
    public function merge_records(Request $request)
    {
        $response = [
            'status' => false,
            'message' => 'Please try again',
        ];

        $mergeFrom = $request->input('merge_from');
        $mergeInto = $request->input('merge_into');

        if ($mergeFrom === null || $mergeFrom === '' || $mergeInto === null || $mergeInto === '') {
            $response['message'] = 'Please select two client records to merge.';
            echo json_encode($response);

            return;
        }

        if (! is_numeric($mergeFrom) || ! is_numeric($mergeInto)) {
            $response['message'] = 'Invalid client records.';
            echo json_encode($response);

            return;
        }

        $mergeFrom = (int) $mergeFrom;
        $mergeInto = (int) $mergeInto;

        if ($mergeFrom === $mergeInto) {
            $response['message'] = 'Cannot merge a record into itself.';
            echo json_encode($response);

            return;
        }

        $fromClient = Admin::find($mergeFrom);
        $intoClient = Admin::find($mergeInto);

        if (! $fromClient || ! $intoClient) {
            $response['message'] = 'Client record not found.';
            echo json_encode($response);

            return;
        }

        if ((int) ($fromClient->is_deleted ?? 0) === 1 || (int) ($intoClient->is_deleted ?? 0) === 1) {
            $response['message'] = 'Cannot merge archived or deleted client records.';
            echo json_encode($response);

            return;
        }

        // Same allocation/grants rules as client edit (blocks IDOR on restricted staff).
        if (! $this->canEditClient($fromClient) || ! $this->canEditClient($intoClient)) {
            $response['message'] = 'Unauthorized';
            echo json_encode($response);

            return;
        }

        $relatedTables = [
            'activities_logs',
            'notes',
            'applications',
            'documents',
            'quotations',
            'invoices',
            'emails',
            'tasks',
            'checkin_logs',
        ];

        try {
            DB::transaction(function () use ($mergeFrom, $mergeInto, $relatedTables) {
                $now = now();

                // Reassign children first; soft-delete source only after reassign succeeds.
                foreach ($relatedTables as $table) {
                    if (! $this->mergeTableExists($table)) {
                        continue;
                    }

                    $query = DB::table($table)->where('client_id', $mergeFrom);
                    $columns = $this->mergeTableColumns($table);
                    $payload = ['client_id' => $mergeInto];
                    if (in_array('updated_at', $columns, true)) {
                        $payload['updated_at'] = $now;
                    }
                    $query->update($payload);
                }

                DB::table('admins')->where('id', $mergeFrom)->update([
                    'is_deleted' => 1,
                    'updated_at' => $now,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Client merge failed', [
                'merge_from' => $mergeFrom,
                'merge_into' => $mergeInto,
                'error' => $e->getMessage(),
            ]);
            $response['message'] = 'Merge failed. Please try again.';
            echo json_encode($response);

            return;
        }

        $response['status'] = true;
        $response['message'] = 'You have successfully merged records from ' . $mergeFrom . ' to ' . $mergeInto . ' .';
        echo json_encode($response);
    }

    /**
     * Skip tables removed from schema (e.g. tasks) without failing the whole merge.
     */
    private function mergeTableExists(string $table): bool
    {
        static $cache = [];

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = DB::getSchemaBuilder()->hasTable($table);
        }

        return $cache[$table];
    }

    /**
     * @return list<string>
     */
    private function mergeTableColumns(string $table): array
    {
        static $cache = [];

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = DB::getSchemaBuilder()->getColumnListing($table);
        }

        return $cache[$table];
    }
}
