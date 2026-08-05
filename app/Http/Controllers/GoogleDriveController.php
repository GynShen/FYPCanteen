<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yaza\LaravelGoogleDriveStorage\Gdrive;

class GoogleDriveController extends Controller
{

    public function __construct(public GoogleDriveService $googleDriveService)
    {
        # Getting access token and setting this to config access token
        config(['filesystems.disks.google.accessToken' => $this->googleDriveService->getAccessToken()]);
    }

    public function index()
    {
        $files = DriveFile::all();
        return view('file-upload', compact('files'));
    }

    /**
     * Function: uploadFileToDrive
     * Description: to upload file into Google Drive
     */
    public function uploadFileToDrive(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);
        
        $file = $request->file('file');

        $fileName = $file->getClientOriginalName();

        Gdrive::put($fileName, $file);

        $contents = Gdrive::all('/');

        $uploadedContent = $contents->firstWhere('path', $fileName);

        if ($uploadedContent) {

            $fileMeta = $uploadedContent->extraMetaData() ?? [];

            # Setting the file visibility to public
            $this->googleDriveService->makeFileToPublic($fileMeta['id']);

            DriveFile::updateOrCreate(
                ['drive_file_id' => $fileMeta['id']],
                [
                    'name'          => $fileMeta['name'],
                    'filename'      => $fileMeta['filename'],
                    'extension'     => $fileMeta['extension'],
                    'path'          => $uploadedContent->path(),
                    'mime_type'     => $uploadedContent->mimeType(),
                    'file_size'     => $uploadedContent->fileSize(),
                    'visibility'    => $uploadedContent->visibility(),
                    'last_modified' => Carbon::createFromTimestamp($uploadedContent->lastModified()),
                ]

            );
        }

        return back()->with('success', 'File uploaded and saved into the database successfully!');
    }

    /**
     * Function: downloadFile
     */
    public function downloadFile($id)
    {
        $driveFile = DriveFile::findOrFail($id);

        $data = Gdrive::get($driveFile->path);

        return response($data->file, 200)
            ->header('Content-Type', $data->ext)
            ->header('Content-disposition', 'attachment; filename="' . $data->filename . '"');
    }

    /**
     * Function: deleteFile
     */
    public function deleteFile($id)
    {
        $driveFile = DriveFile::findOrFail($id);

        Gdrive::delete($driveFile->path);

        $driveFile->delete();

        return back()->with('success', 'File deleted successfully!');
    }
}
