<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use App\Models\UploadChecklist;

class UploadChecklistController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Resolve index/redirect route name (adminconsole when used from console).
     */
    protected function indexRouteName(Request $request = null): string
    {
        $request = $request ?: request();
        if ($request->is('adminconsole/*') || $request->routeIs('adminconsole.*')) {
            return 'adminconsole.upload_checklists.index';
        }

        return 'upload_checklists.index';
    }

    /**
     * List upload checklists.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = UploadChecklist::query();
        $totalData = $query->count();
        $lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));

        return view('Admin.uploadchecklist.index', compact(['lists', 'totalData']));
    }

    /**
     * Create a checklist.
     */
    public function store(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'name' => 'required|max:255',
                'checklists' => 'required|file',
            ]);

            $obj = new UploadChecklist;
            $obj->name = $request->input('name');

            if ($request->hasfile('checklists')) {
                $checklists = $this->uploadFile($request->file('checklists'), Config::get('constants.checklists'));
            } else {
                $checklists = null;
            }

            $obj->file = $checklists;
            $saved = $obj->save();

            if (! $saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            }

            return redirect()->route($this->indexRouteName($request))
                ->with('success', 'Record Added Successfully');
        }

        return redirect()->route($this->indexRouteName($request));
    }

    /**
     * Show edit form.
     */
    public function edit(Request $request, $id)
    {
        $fetchedData = UploadChecklist::find($id);
        if (! $fetchedData) {
            return redirect()->route($this->indexRouteName($request))
                ->with('error', 'Record not found');
        }

        return view('Admin.uploadchecklist.edit', compact('fetchedData'));
    }

    /**
     * Update checklist name and optionally replace file.
     */
    public function update(Request $request, $id)
    {
        $obj = UploadChecklist::find($id);
        if (! $obj) {
            return redirect()->route($this->indexRouteName($request))
                ->with('error', 'Record not found');
        }

        $this->validate($request, [
            'name' => 'required|max:255',
            'checklists' => 'nullable|file',
        ]);

        $obj->name = $request->input('name');
        $oldFile = $obj->file;

        if ($request->hasfile('checklists')) {
            $newFile = $this->uploadFile($request->file('checklists'), Config::get('constants.checklists'));
            if ($newFile) {
                $obj->file = $newFile;
            }
        }

        $saved = $obj->save();

        if (! $saved) {
            return redirect()->back()->with('error', Config::get('constants.server_error'))->withInput();
        }

        // After successful save, remove replaced file from disk (never block on missing old file)
        if ($request->hasfile('checklists') && ! empty($oldFile) && $oldFile !== $obj->file) {
            $oldPath = public_path('checklists/' . $oldFile);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            } else {
                Log::info('Upload checklist old file already missing on replace', [
                    'id' => $obj->id,
                    'file' => $oldFile,
                ]);
            }
        }

        return redirect()->route($this->indexRouteName($request))
            ->with('success', 'Record Updated Successfully');
    }
}
