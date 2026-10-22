<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequestDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Streams an uploaded document from private storage to an authorised viewer. */
class DocumentController extends Controller
{
    public function show(ServiceRequestDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document->serviceRequest);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
        ], request()->boolean('download') ? 'attachment' : 'inline');
    }
}
