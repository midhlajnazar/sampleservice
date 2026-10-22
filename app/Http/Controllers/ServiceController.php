<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $services = Service::active()
            ->when($request->string('category')->value(), fn ($q, $category) => $q->where('category', $category))
            ->get();

        return Inertia::render('services/Index', [
            'services' => ServiceResource::collection($services)->resolve(),
            // Not the active() scope: its ORDER BY columns aren't selected, which MySQL rejects with DISTINCT.
            'categories' => Service::where('is_active', true)->distinct()->orderBy('category')->pluck('category'),
            'filters' => ['category' => $request->query('category')],
        ]);
    }

    public function show(Service $service): Response
    {
        abort_unless($service->is_active, 404);

        return Inertia::render('services/Show', [
            'service' => ServiceResource::make($service)->withSchema()->resolve(),
        ]);
    }
}
