<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PackingLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PackingLogController extends Controller
{
    /**
     * Display the main dashboard view with statistics.
     */
    public function dashboard()
    {
        $total_count = PackingLog::count();
        $total_size_bytes = PackingLog::sum('file_size') ?? 0;
        $total_size_mb = round($total_size_bytes / (1024 * 1024), 2);
        
        $avg_duration = round(PackingLog::avg('duration_seconds') ?? 0, 1);
        $recent_logs = PackingLog::orderBy('created_at', 'desc')->take(5)->get();

        return view('dashboard', compact('total_count', 'total_size_mb', 'avg_duration', 'recent_logs'));
    }

    /**
     * Get a list of packing logs (AJAX search & filter).
     */
    public function index(Request $request)
    {
        $query = PackingLog::query();

        if ($request->filled('q')) {
            $query->where('order_id', 'like', '%' . $request->q . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($logs);
    }

    /**
     * Store a newly created packing log and video file.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string',
                'video' => 'required|file|max:30720', // Max 30MB
                'duration_seconds' => 'nullable|integer',
                'staff_name' => 'nullable|string',
            ]);

            $file = $request->file('video');
            $originalExtension = $file->getClientOriginalExtension();
            // Default to webm if extension is not resolved
            $extension = $originalExtension ?: 'webm';
            
            $fileName = cleanFileName($request->order_id) . '_' . time() . '.' . $extension;
            
            // Store file in the 'public' disk under 'videos' directory
            $path = $file->storeAs('videos', $fileName, 'public');
            $fileSize = $file->getSize();

            $log = PackingLog::create([
                'order_id' => $request->order_id,
                'file_name' => $fileName,
                'file_path' => 'videos/' . $fileName,
                'file_size' => $fileSize,
                'duration_seconds' => $request->duration_seconds ?? 0,
                'staff_name' => $request->staff_name ?? 'Default Staff',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded and logged successfully.',
                'log' => $log
            ]);

        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to store video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a packing log and its video file.
     */
    public function destroy($id)
    {
        $log = PackingLog::find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log entry not found.'
            ], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($log->file_path)) {
            Storage::disk('public')->delete($log->file_path);
        }

        // Delete DB record
        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log and video file deleted successfully.'
        ]);
    }

    /**
     * Clean up records and files older than specified days.
     */
    public function cleanup(Request $request)
    {
        $days = $request->input('days', 30);
        $cutoffDate = now()->subDays($days);

        $expiredLogs = PackingLog::where('created_at', '<', $cutoffDate)->get();
        $deletedCount = 0;

        foreach ($expiredLogs as $log) {
            if (Storage::disk('public')->exists($log->file_path)) {
                Storage::disk('public')->delete($log->file_path);
            }
            $log->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully cleaned up {$deletedCount} records older than {$days} days.",
            'deleted_count' => $deletedCount
        ]);
    }
}

/**
 * Helper to clean file name from special characters
 */
function cleanFileName($string) {
    $string = str_replace(array(' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'), '_', $string);
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $string);
}
