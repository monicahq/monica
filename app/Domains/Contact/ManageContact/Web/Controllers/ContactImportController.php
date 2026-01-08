<?php
/**
 * vCard Import Feature - Import Controller
 *
 * @author Clément ABRAHAM <https://github.com/cabraham2>
 * @version 1.0.0
 * @created 2026-01-08
 * @updated 2026-01-08
 * @license MIT
 *
 * Controller managing the vCard import workflow:
 * - Upload and parse vCard files
 * - Preview contacts with selection
 * - Import selected contacts into vault
 */
namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContact\Services\ParseVCardFile;
use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactImportViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ContactImportController extends Controller
{
    /**
     * Show the import page.
     */
    public function index(Request $request, string $vaultId): Response
    {
        $vault = Vault::findOrFail($vaultId);
        Gate::authorize('vault-editor', $vault);

        return Inertia::render('Vault/Contact/Import/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactImportViewHelper::data($vault),
        ]);
    }

    /**
     * Upload and parse vCard file.
     */
    public function upload(Request $request, string $vaultId): JsonResponse
    {
        $vault = Vault::findOrFail($vaultId);
        Gate::authorize('vault-editor', $vault);

        $request->validate([
            'file' => 'required|file|mimes:vcf,vcard|max:51200', // 50MB max
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());

            // Parse the vCard file
            $parsedContacts = app(ParseVCardFile::class)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vaultId,
                'file_content' => $content,
            ]);

            // Reconnect to database in case of long parsing time
            DB::reconnect();

            // Store the file temporarily in storage instead of session (files can be too large)
            $sessionKey = 'vcard_import_'.Auth::id().'_'.now()->timestamp;
            Storage::put('tmp/'.$sessionKey.'.vcf', $content);
            
            // Extract and save photos as separate files
            $this->extractAndSavePhotos($parsedContacts, $sessionKey);
            
            // Store only the key in session
            session(['vcard_import_session_key' => $sessionKey]);

            return response()->json([
                'data' => [
                    'contacts' => ContactImportViewHelper::formatParsedContacts($parsedContacts, $vaultId, $sessionKey),
                    'session_key' => $sessionKey,
                    'total' => $parsedContacts->count(),
                    'errors' => $parsedContacts->where('error', true)->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to parse vCard file',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get detailed contact data including photo (on-demand loading).
     */
    public function contactDetail(Request $request, string $vaultId): JsonResponse
    {
        $vault = Vault::findOrFail($vaultId);
        Gate::authorize('vault-editor', $vault);

        $request->validate([
            'session_key' => 'required|string',
            'index' => 'required|integer',
        ]);

        try {
            $sessionKey = $request->input('session_key');
            $index = $request->input('index');

            // Load the vCard file from storage
            if (! Storage::exists('tmp/'.$sessionKey.'.vcf')) {
                return response()->json([
                    'error' => 'Session expired',
                    'message' => 'The upload session has expired. Please upload the file again.',
                ], 404);
            }

            $content = Storage::get('tmp/'.$sessionKey.'.vcf');

            // Parse the vCard file
            $parsedContacts = app(ParseVCardFile::class)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vaultId,
                'file_content' => $content,
            ]);

            // Find the specific contact by index
            $contact = $parsedContacts->firstWhere('index', $index);

            if (! $contact) {
                return response()->json([
                    'error' => 'Contact not found',
                    'message' => 'The requested contact was not found.',
                ], 404);
            }

            // Return the full contact data including photo
            return response()->json([
                'data' => $contact,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load contact details',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Extract photos from parsed contacts and save them as temporary files.
     */
    private function extractAndSavePhotos($parsedContacts, string $sessionKey): void
    {
        $photosDir = 'tmp/'.$sessionKey.'/photos';
        
        foreach ($parsedContacts as $contact) {
            if (!empty($contact['photo']) && !empty($contact['photo']['value'])) {
                $index = $contact['index'];
                $photoData = $contact['photo']['value'];
                $photoType = $contact['photo']['type'] ?? 'jpeg';
                
                // Decode base64 if needed
                if (($contact['photo']['encoding'] ?? '') === 'base64') {
                    $photoData = base64_decode($photoData);
                }
                
                // Save the photo
                $extension = strtolower($photoType);
                if ($extension === 'jpg') {
                    $extension = 'jpeg';
                }
                Storage::put($photosDir.'/'.$index.'.'.$extension, $photoData);
            }
        }
    }

    /**
     * Serve a photo from temporary storage.
     */
    public function getPhoto(Request $request, string $vaultId, string $sessionKey, int $index)
    {
        $vault = Vault::findOrFail($vaultId);
        Gate::authorize('vault-editor', $vault);

        // Check if session belongs to current user
        if (!str_starts_with($sessionKey, 'vcard_import_'.Auth::id().'_')) {
            abort(403, 'Unauthorized');
        }

        $photosDir = 'tmp/'.$sessionKey.'/photos';
        
        // Try different extensions
        $extensions = ['jpeg', 'jpg', 'png', 'gif'];
        foreach ($extensions as $ext) {
            $path = $photosDir.'/'.$index.'.'.$ext;
            if (Storage::exists($path)) {
                $mimeType = 'image/'.$ext;
                return response(Storage::get($path), 200, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        abort(404, 'Photo not found');
    }

    /**
     * Import selected contacts in chunks.
     */
    public function import(Request $request, string $vaultId): JsonResponse
    {
        $vault = Vault::findOrFail($vaultId);
        Gate::authorize('vault-editor', $vault);

        $request->validate([
            'session_key' => 'required|string',
            'selected_indices' => 'required|array',
            'selected_indices.*' => 'integer',
            'chunk_size' => 'integer|min:1|max:100',
            'offset' => 'integer|min:0',
        ]);

        $sessionKey = $request->input('session_key');
        $filePath = 'tmp/'.$sessionKey.'.vcf';

        if (! Storage::exists($filePath)) {
            return response()->json([
                'error' => 'Import session expired or file not found',
            ], 422);
        }

        $content = Storage::get($filePath);
        $selectedIndices = $request->input('selected_indices');
        $chunkSize = $request->input('chunk_size', 50); // Default 50 contacts per chunk
        $offset = $request->input('offset', 0);

        // Get the chunk of indices to process
        $chunk = array_slice($selectedIndices, $offset, $chunkSize);
        
        if (empty($chunk)) {
            return response()->json([
                'data' => [
                    'imported' => [],
                    'errors' => [],
                    'success_count' => 0,
                    'error_count' => 0,
                    'completed' => true,
                    'progress' => 100,
                ],
            ], 200);
        }

        try {
            // Parse again to get individual vCards
            $parsedContacts = app(ParseVCardFile::class)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vaultId,
                'file_content' => $content,
            ]);

            $imported = [];
            $errors = [];

            DB::transaction(function () use ($parsedContacts, $chunk, $vaultId, $sessionKey, &$imported, &$errors) {
                foreach ($chunk as $index) {
                    $contactData = $parsedContacts->get($index);

                    if (! $contactData || $contactData['error']) {
                        $errors[] = [
                            'index' => $index,
                            'message' => $contactData['error_message'] ?? 'Invalid contact data',
                        ];
                        continue;
                    }

                    try {
                        // Import using the existing ImportVCard service
                        $result = app(ImportVCard::class)->execute([
                            'account_id' => Auth::user()->account_id,
                            'author_id' => Auth::id(),
                            'vault_id' => $vaultId,
                            'entry' => $contactData['raw'],
                            'behaviour' => ImportVCard::BEHAVIOUR_ADD,
                            'session_key' => $sessionKey,  // Pass session key for photo import
                            'contact_index' => $index,     // Pass contact index for photo import
                        ]);

                        if (isset($result['error'])) {
                            $errors[] = [
                                'index' => $index,
                                'error_type' => $result['error'] ?? 'unknown',
                                'message' => $result['reason'] ?? 'Import failed',
                                'contact_name' => $contactData['name'] ?? 'Unknown',
                            ];
                        } else {
                            $imported[] = [
                                'index' => $index,
                                'id' => $result['id'] ?? null,
                            ];
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'index' => $index,
                            'error_type' => 'exception',
                            'message' => $e->getMessage(),
                            'contact_name' => $contactData['name'] ?? 'Unknown',
                        ];
                    }
                }
            });

            // Calculate progress
            $totalSelected = count($selectedIndices);
            $processed = $offset + count($chunk);
            $progress = round(($processed / $totalSelected) * 100);
            $completed = $processed >= $totalSelected;

            // Clean up only if completed
            if ($completed) {
                Storage::delete($filePath);
                Storage::deleteDirectory('tmp/'.$sessionKey.'/photos');
                Storage::deleteDirectory('tmp/'.$sessionKey);
                $this->cleanupOldTempFiles();
                session()->forget('vcard_import_session_key');
            }

            return response()->json([
                'data' => [
                    'imported' => $imported,
                    'errors' => $errors,
                    'success_count' => count($imported),
                    'error_count' => count($errors),
                    'completed' => $completed,
                    'progress' => $progress,
                    'processed' => $processed,
                    'total' => $totalSelected,
                    'next_offset' => $completed ? null : $processed,
                    'redirect' => $completed ? route('contact.index', ['vault' => $vaultId]) : null,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Import failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean up old temporary vCard import files.
     */
    private function cleanupOldTempFiles(): void
    {
        try {
            $directories = Storage::directories('tmp');
            $now = now();

            foreach ($directories as $directory) {
                // Check if it's a vcard import directory
                if (str_starts_with(basename($directory), 'vcard_import_')) {
                    // Check if directory is older than 24 hours
                    $lastModified = Storage::lastModified($directory);
                    if ($lastModified && $now->diffInHours(\Carbon\Carbon::createFromTimestamp($lastModified)) > 24) {
                        Storage::deleteDirectory($directory);
                    }
                }
            }

            // Also clean up orphaned .vcf files
            $files = Storage::files('tmp');
            foreach ($files as $file) {
                if (str_ends_with($file, '.vcf') && str_contains($file, 'vcard_import_')) {
                    $lastModified = Storage::lastModified($file);
                    if ($lastModified && $now->diffInHours(\Carbon\Carbon::createFromTimestamp($lastModified)) > 24) {
                        Storage::delete($file);
                    }
                }
            }
        } catch (\Exception $e) {
            // Fail silently - cleanup is not critical
            \Log::warning('Failed to cleanup old temp files: '.$e->getMessage());
        }
    }

    /**
     * Cancel import and clean up session.
     */
    public function cancel(Request $request, string $vaultId): JsonResponse
    {
        $sessionKey = session('vcard_import_session_key');
        
        if ($sessionKey) {
            // Delete temporary file
            $filePath = 'tmp/'.$sessionKey.'.vcf';
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
            session()->forget('vcard_import_session_key');
        }

        return response()->json([
            'data' => [
                'success' => true,
            ],
        ], 200);
    }
}
