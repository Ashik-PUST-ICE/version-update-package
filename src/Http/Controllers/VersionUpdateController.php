<?php

namespace Ashik\VersionUpdater\Http\Controllers;

use Ashik\VersionUpdater\VersionManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VersionUpdateController extends Controller
{
    public function __construct(private readonly VersionManager $manager) {}

    public function index()
    {
        return view('ashik-version-updater::index', [
            'title' => config('version-updater.name'),
            'manager' => $this->manager,
            'uploaded' => $this->manager->uploaded(),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate(['update_file' => ['required', 'file', 'mimes:zip']]);
        $this->manager->upload($request->file('update_file'));
        return back()->with('success', 'Ashik update package uploaded successfully.');
    }

    public function apply()
    {
        try {
            $this->manager->apply();
            return back()->with('success', 'Ashik update applied successfully.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', $exception->getMessage());
        }
    }

    public function delete()
    {
        $this->manager->deleteUpload();
        return back()->with('success', 'Ashik update package deleted.');
    }
}
