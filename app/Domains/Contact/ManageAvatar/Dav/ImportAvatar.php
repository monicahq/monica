<?php

namespace App\Domains\Contact\ManageAvatar\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCard;

#[Order(50)]
class ImportAvatar extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Import Contact avatar from temp storage.
     * Photos are already extracted in storage/app/tmp/{sessionKey}/photos/ during upload.
     * We just need to copy the photo from temp to permanent storage.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        // Get session_key and contact_index from context
        $sessionKey = $this->context->data['session_key'] ?? null;
        $contactIndex = $this->context->data['contact_index'] ?? null;

        if (! $sessionKey || $contactIndex === null) {
            return $contact;
        }

        try {
            // Check if temp photo exists for this contact
            $tempPhotoPath = "tmp/{$sessionKey}/photos/{$contactIndex}";
            
            // Try to find the photo file (could be .jpg, .png, etc.)
            $photoFile = null;
            $extension = null;
            
            foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
                if (Storage::exists("{$tempPhotoPath}.{$ext}")) {
                    $photoFile = "{$tempPhotoPath}.{$ext}";
                    $extension = $ext;
                    break;
                }
            }

            if (! $photoFile) {
                // No photo for this contact
                return $contact;
            }

            // Read photo data from temp storage
            $photoData = Storage::get($photoFile);
            
            if (! $photoData || strlen($photoData) === 0) {
                return $contact;
            }

            // Detect mime type from file extension
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';

            // Normalize extension
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            // Store in permanent location
            $file = $this->storeLocally($photoData, $mimeType, $extension, $contact);

            if ($file) {
                // Delete old avatar if exists
                if ($contact->file) {
                    $oldFile = $contact->file;
                    $contact->file_id = null;
                    $contact->save();
                    $oldFile->delete();
                }
                
                // Associate file with contact
                $contact->file_id = $file->id;
                $contact->save();
            }
        } catch (\Exception $e) {
            // Silently fail on photo import errors - don't block contact import
            \Log::warning('Failed to import avatar for contact '.$contact->id.': '.$e->getMessage());
        }

        return $contact->refresh();
    }

    /**
     * Store photo locally in Laravel storage (fallback when Uploadcare is not configured).
     */
    private function storeLocally(string $photoData, string $mimeType, string $extension, Contact $contact): ?File
    {
        try {
            // Generate unique filename
            $uuid = (string) Str::uuid();
            $filename = "avatars/{$contact->vault_id}/{$uuid}.{$extension}";
            
            // Store in public disk so it's accessible via URL
            Storage::disk('public')->put($filename, $photoData);
            
            // Generate URLs
            $url = asset('storage/'.$filename);
            
            // Create File model
            $file = File::create([
                'vault_id' => $this->vault()->id,
                'uuid' => $uuid,
                'name' => "avatar.{$extension}",
                'original_url' => $url,
                'cdn_url' => $url,
                'mime_type' => $mimeType,
                'size' => strlen($photoData),
                'type' => File::TYPE_AVATAR,
            ]);

            return $file;
        } catch (\Exception $e) {
            \Log::warning('Failed to store photo locally: '.$e->getMessage());
            
            return null;
        }
    }

    /**
     * Upload photo data to Uploadcare.
     */
    private function uploadToUploadcare(string $photoData, string $mimeType, string $extension): ?File
    {
        try {
            $publicKey = config('services.uploadcare.public_key');
            
            // Create a temporary file
            $tempPath = sys_get_temp_dir().'/'.Str::uuid().'.'.$extension;
            file_put_contents($tempPath, $photoData);

            // Upload to Uploadcare
            $response = Http::attach(
                'file',
                file_get_contents($tempPath),
                'avatar.'.$extension
            )->post('https://upload.uploadcare.com/base/', [
                'UPLOADCARE_PUB_KEY' => $publicKey,
                'UPLOADCARE_STORE' => '1',
            ]);

            // Clean up temp file
            @unlink($tempPath);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $uuid = $data['file'] ?? null;

            if (! $uuid) {
                return null;
            }

            // Wait a moment for Uploadcare to process
            sleep(1);

            // Get file info from Uploadcare
            $infoResponse = Http::get("https://upload.uploadcare.com/info/?pub_key={$publicKey}&file_id={$uuid}");
            
            if (! $infoResponse->successful()) {
                return null;
            }

            $fileInfo = $infoResponse->json();
            
            // Create File model
            $file = File::create([
                'vault_id' => $this->vault()->id,
                'uuid' => $uuid,
                'name' => 'avatar.'.$extension,
                'original_url' => $fileInfo['original_file_url'] ?? "https://ucarecdn.com/{$uuid}/",
                'cdn_url' => "https://ucarecdn.com/{$uuid}/",
                'mime_type' => $mimeType,
                'size' => $fileInfo['size'] ?? strlen($photoData),
                'type' => File::TYPE_AVATAR,
            ]);

            return $file;
        } catch (\Exception $e) {
            \Log::warning('Failed to upload to Uploadcare: '.$e->getMessage());
            
            return null;
        }
    }
}
