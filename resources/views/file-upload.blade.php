<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload to Google Drive</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 min-h-screen ">

    @if (Session::has('success'))
        <div class="bg-green-400 text-green-700">
            {{ Session::get('success') }}
        </div>
    @endif

    <div class="flex items-center justify-center">

        <div class="max-w-4xl w-full bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-semibold text-gray-700 mb-6 text-center">Upload File to Google Drive</h2>


            <div class="space-y-6">
                <form action="{{ route('upload-file') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label for="file" class="block text-sm font-medium text-gray-700">Choose a file</label>
                        <div class="flex items-center justify-center w-full">
                            <label for="file"
                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">

                                    <p class="mb-2 text-sm text-gray-500">
                                        <span class="font-semibold">Click to upload</span>
                                    </p>
                                    <p class="text-xs text-gray-500">Any file up to 10MB</p>
                                </div>
                                <input id="file" name="file" type="file" />
                            </label>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full cursor-pointer flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Upload File
                        </button>
                    </div>
                </form>

                {{-- Table --}}
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-700 mb-4">Uploaded Files</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        File Name
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Size
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Type
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Uploaded At
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">

                                @foreach ($files as $file)
                                    <tr>
                                        <td class="px-4 py-2"> {{ $file->filename }} </td>
                                        <td class="px-4 py-2"> {{ number_format($file->file_size / 1024, 2) }} KB </td>
                                        <td class="px-4 py-2"> {{ $file->extension }}</td>
                                        <td class="px-4 py-2"> {{ $file->last_modified }}</td>
                                        <td class="px-4 py-2 flex items-center">
                                            <a href="https://drive.google.com/file/d/{{ $file->drive_file_id }}/view"
                                                target="_blank" class="bg-sky-500 text-white px-2 py-1 rounded">View</a>
                                            <a href="{{ route('drive.download', $file->id) }}"
                                                class="bg-green-500 text-white px-2 py-1 mx-1 rounded">Download</a>
                                            <form action="{{ route('drive.delete', $file->id) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this file?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="rounded bg-red-500 px-2 py-1 text-white">
                                                    Delete
                                                </button>
                                                </form>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>


                </div>
            </div>
        </div>
</body>

</html>
