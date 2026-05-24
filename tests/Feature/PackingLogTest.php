<?php

namespace Tests\Feature;

use App\Models\PackingLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PackingLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if dashboard view loads correctly.
     */
    public function test_dashboard_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    /**
     * Test if logs API returns list.
     */
    public function test_logs_api_lists_logs(): void
    {
        PackingLog::create([
            'order_id' => 'TEST123456',
            'file_name' => 'test.webm',
            'file_path' => 'videos/test.webm',
            'file_size' => 1024,
            'duration_seconds' => 15,
            'staff_name' => 'John Doe'
        ]);

        $response = $this->getJson('/api/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data', 'current_page', 'total', 'per_page'
        ]);
        $this->assertEquals(1, $response->json('total'));
    }

    /**
     * Test if video upload is successful.
     */
    public function test_video_upload_endpoint(): void
    {
        Storage::fake('public');

        $video = UploadedFile::fake()->create('video.webm', 1000); // 1MB mock file

        $response = $this->postJson('/api/logs/upload', [
            'order_id' => 'RESI9876',
            'video' => $video,
            'duration_seconds' => 45,
            'staff_name' => 'Alice Smith'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        // Assert record exists in database
        $this->assertDatabaseHas('packing_logs', [
            'order_id' => 'RESI9876',
            'duration_seconds' => 45,
            'staff_name' => 'Alice Smith'
        ]);

        $log = PackingLog::first();
        // Assert file stored in storage
        Storage::disk('public')->assertExists($log->file_path);
    }

    /**
     * Test log deletion removes record and file.
     */
    public function test_log_deletion(): void
    {
        Storage::fake('public');

        // Create log and mock file
        $fileName = 'test_delete_123.webm';
        Storage::disk('public')->put('videos/' . $fileName, 'dummy content');
        
        $log = PackingLog::create([
            'order_id' => 'RESI_DEL',
            'file_name' => $fileName,
            'file_path' => 'videos/' . $fileName,
            'file_size' => 200,
            'duration_seconds' => 10,
            'staff_name' => 'Test Operator'
        ]);

        Storage::disk('public')->assertExists($log->file_path);

        $response = $this->deleteJson('/api/logs/' . $log->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseMissing('packing_logs', [
            'id' => $log->id
        ]);

        Storage::disk('public')->assertMissing($log->file_path);
    }

    /**
     * Test storage retention cleanup older than specified days.
     */
    public function test_retention_cleanup(): void
    {
        Storage::fake('public');

        // Create file 1 (expired, older than 30 days)
        $expiredFileName = 'expired_123.webm';
        Storage::disk('public')->put('videos/' . $expiredFileName, 'expired content');
        $expiredLog = PackingLog::create([
            'order_id' => 'EXPIRED',
            'file_name' => $expiredFileName,
            'file_path' => 'videos/' . $expiredFileName,
            'file_size' => 500,
            'duration_seconds' => 30,
            'staff_name' => 'Old Operator'
        ]);
        // Manually adjust timestamps to 31 days ago
        $expiredLog->created_at = now()->subDays(31);
        $expiredLog->save();

        // Create file 2 (recent, within 30 days)
        $recentFileName = 'recent_123.webm';
        Storage::disk('public')->put('videos/' . $recentFileName, 'recent content');
        $recentLog = PackingLog::create([
            'order_id' => 'RECENT',
            'file_name' => $recentFileName,
            'file_path' => 'videos/' . $recentFileName,
            'file_size' => 500,
            'duration_seconds' => 30,
            'staff_name' => 'Recent Operator'
        ]);

        // Run cleanup
        $response = $this->postJson('/api/logs/cleanup', [
            'days' => 30
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_count' => 1
        ]);

        // Expired log should be deleted from DB and storage
        $this->assertDatabaseMissing('packing_logs', [
            'id' => $expiredLog->id
        ]);
        Storage::disk('public')->assertMissing($expiredLog->file_path);

        // Recent log should be untouched
        $this->assertDatabaseHas('packing_logs', [
            'id' => $recentLog->id
        ]);
        Storage::disk('public')->assertExists($recentLog->file_path);
    }
}
