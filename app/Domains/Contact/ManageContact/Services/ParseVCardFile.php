<?php

/**
 * vCard Import Feature - Parse vCard File Service
 *
 * @author Clément ABRAHAM <https://github.com/cabraham2>
 * @version 1.0.0
 * @created 2026-01-08
 * @updated 2026-01-08
 * @license MIT
 *
 * Service to parse vCard files and extract contact information.
 * Supports vCard 3.0 format with comprehensive field extraction.
 */

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use Illuminate\Support\Collection;
use Sabre\VObject\Reader;

class ParseVCardFile extends BaseService implements ServiceInterface
{
    private array $vcards = [];

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'file_content' => 'required|string',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Parse vCard file and return structured data.
     */
    public function execute(array $data): Collection
    {
        $this->validateRules($data);

        $fileContent = $data['file_content'];
        
        // Convert to UTF-8 if needed to avoid "Malformed UTF-8" errors
        $fileContent = $this->ensureUtf8Encoding($fileContent);
        
        $this->vcards = $this->splitVCards($fileContent);

        return $this->parseVCards();
    }

    /**
     * Ensure the content is in UTF-8 encoding.
     * Detects and converts from common encodings (ISO-8859-1, Windows-1252, etc.).
     */
    private function ensureUtf8Encoding(string $content): string
    {
        // List of encodings to try, in order of likelihood
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ISO-8859-15'];
        
        // Detect current encoding
        $currentEncoding = mb_detect_encoding($content, $encodings, true);
        
        // If already UTF-8 or detection failed, try to force UTF-8 anyway
        if ($currentEncoding === 'UTF-8' || $currentEncoding === false) {
            // Try to fix any malformed UTF-8 by re-encoding
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        } else {
            // Convert from detected encoding to UTF-8
            $content = mb_convert_encoding($content, 'UTF-8', $currentEncoding);
        }
        
        return $content;
    }

    /**
     * Split the vCard file into individual vCards.
     * Handles folded lines (lines starting with space/tab are continuations).
     */
    private function splitVCards(string $content): array
    {
        $vcards = [];
        $lines = explode("\n", $content);
        $currentCard = [];
        $inCard = false;

        foreach ($lines as $line) {
            // Remove only trailing whitespace, preserve leading spaces for folded lines
            $line = rtrim($line, "\r\n");

            // Skip empty lines
            if ($line === '') {
                continue;
            }

            $trimmedLine = trim($line);

            if ($trimmedLine === 'BEGIN:VCARD') {
                $inCard = true;
                $currentCard = [$trimmedLine];
            } elseif ($trimmedLine === 'END:VCARD' && $inCard) {
                $currentCard[] = $trimmedLine;
                // Unfold lines before storing the vCard
                $vcards[] = $this->unfoldLines(implode("\n", $currentCard));
                $inCard = false;
                $currentCard = [];
            } elseif ($inCard) {
                // Check if this is a folded line (starts with space or tab)
                if (isset($line[0]) && ($line[0] === ' ' || $line[0] === "\t")) {
                    // Continuation of previous line - append without the leading space
                    $lastIndex = count($currentCard) - 1;
                    if ($lastIndex >= 0) {
                        $currentCard[$lastIndex] .= substr($line, 1);
                    }
                } else {
                    // New property line
                    $currentCard[] = $trimmedLine;
                }
            }
        }

        return $vcards;
    }

    /**
     * Unfold vCard lines (remove line breaks and spaces used for line folding).
     */
    private function unfoldLines(string $vcard): string
    {
        // Already unfolded in splitVCards, but keep this method for consistency
        return $vcard;
    }

    /**
     * Parse each vCard and extract relevant information.
     */
    private function parseVCards(): Collection
    {
        $parsedContacts = collect();

        foreach ($this->vcards as $index => $vcardString) {
            try {
                $vcard = Reader::read($vcardString);
                $parsedContacts->push($this->extractContactData($vcard, $index, $vcardString));
            } catch (\Exception $e) {
                // Skip invalid vCards but log the error
                $parsedContacts->push([
                    'index' => $index,
                    'error' => true,
                    'error_message' => $e->getMessage(),
                    'raw' => $vcardString,
                ]);
            }
        }

        return $parsedContacts;
    }

    /**
     * Extract contact data from a vCard.
     */
    private function extractContactData($vcard, int $index, string $raw): array
    {
        $data = [
            'index' => $index,
            'error' => false,
            'raw' => $raw,
        ];

        // Extract names
        if (isset($vcard->N)) {
            $parts = $vcard->N->getParts();
            $data['last_name'] = $this->cleanValue($parts[0] ?? '');
            $data['first_name'] = $this->cleanValue($parts[1] ?? '');
            $data['middle_name'] = $this->cleanValue($parts[2] ?? '');
            $data['prefix'] = $this->cleanValue($parts[3] ?? '');
            $data['suffix'] = $this->cleanValue($parts[4] ?? '');
        }

        // Formatted name
        if (isset($vcard->FN)) {
            $data['formatted_name'] = $this->cleanValue((string) $vcard->FN);
            
            // If no structured name, parse formatted name
            if (empty($data['first_name']) && empty($data['last_name'])) {
                $nameParts = explode(' ', $data['formatted_name']);
                if (count($nameParts) === 1) {
                    $data['first_name'] = $nameParts[0];
                } elseif (count($nameParts) >= 2) {
                    $data['first_name'] = $nameParts[0];
                    $data['last_name'] = implode(' ', array_slice($nameParts, 1));
                }
            }
        }

        // Nickname
        if (isset($vcard->NICKNAME)) {
            $data['nickname'] = $this->cleanValue((string) $vcard->NICKNAME);
        }

        // Gender
        if (isset($vcard->GENDER)) {
            $data['gender'] = $this->cleanValue((string) $vcard->GENDER);
        }

        // Birthday
        if (isset($vcard->BDAY)) {
            $data['birthday'] = $this->cleanValue((string) $vcard->BDAY);
        }

        // Organization
        if (isset($vcard->ORG)) {
            $data['organization'] = $this->cleanValue((string) $vcard->ORG);
        }

        // Title/Job
        if (isset($vcard->TITLE)) {
            $data['title'] = $this->cleanValue((string) $vcard->TITLE);
        }

        // Emails
        if (isset($vcard->EMAIL)) {
            $data['emails'] = [];
            foreach ($vcard->EMAIL as $email) {
                $data['emails'][] = [
                    'value' => $this->cleanValue((string) $email),
                    'type' => $this->getPropertyType($email),
                ];
            }
        }

        // Phones
        if (isset($vcard->TEL)) {
            $data['phones'] = [];
            foreach ($vcard->TEL as $tel) {
                $data['phones'][] = [
                    'value' => $this->cleanValue((string) $tel),
                    'type' => $this->getPropertyType($tel),
                ];
            }
        }

        // Addresses
        if (isset($vcard->ADR)) {
            $data['addresses'] = [];
            foreach ($vcard->ADR as $adr) {
                $parts = $adr->getParts();
                $data['addresses'][] = [
                    'street' => $this->cleanValue($parts[2] ?? ''),
                    'city' => $this->cleanValue($parts[3] ?? ''),
                    'region' => $this->cleanValue($parts[4] ?? ''),
                    'postal_code' => $this->cleanValue($parts[5] ?? ''),
                    'country' => $this->cleanValue($parts[6] ?? ''),
                    'type' => $this->getPropertyType($adr),
                ];
            }
        }

        // URLs
        if (isset($vcard->URL)) {
            $data['urls'] = [];
            foreach ($vcard->URL as $url) {
                $data['urls'][] = $this->cleanValue((string) $url);
            }
        }

        // Notes
        if (isset($vcard->NOTE)) {
            $data['note'] = $this->cleanValue((string) $vcard->NOTE);
        }

        // Categories/Tags
        if (isset($vcard->CATEGORIES)) {
            $categories = (string) $vcard->CATEGORIES;
            $data['categories'] = array_map('trim', explode(',', $categories));
        }

        // Photo
        if (isset($vcard->PHOTO)) {
            $photo = $vcard->PHOTO;
            $data['photo'] = [
                'value' => (string) $photo,
                'encoding' => isset($photo['ENCODING']) ? (string) $photo['ENCODING'] : null,
                'type' => isset($photo['TYPE']) ? (string) $photo['TYPE'] : null,
            ];
        }

        return $data;
    }

    /**
     * Clean and format a value.
     */
    private function cleanValue(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Get the type parameter from a vCard property.
     */
    private function getPropertyType($property): ?string
    {
        if (isset($property['TYPE'])) {
            $type = $property['TYPE'];
            if (is_array($type)) {
                return strtolower($type[0]);
            }

            return strtolower((string) $type);
        }

        return null;
    }
}
