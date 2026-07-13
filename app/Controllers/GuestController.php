<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Guest;
use App\Models\GuestDocument;
use App\Services\GuestService;

final class GuestController
{
    private Guest $guests;
    private GuestDocument $documents;
    private GuestService $service;

    public function __construct(
        ?Guest $guests = null,
        ?GuestDocument $documents = null,
        ?GuestService $service = null,
    ) {
        $this->guests = $guests ?? new Guest();
        $this->documents = $documents ?? new GuestDocument();
        $this->service = $service ?? new GuestService($this->guests, $this->documents);
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::GUESTS_VIEW);

        $q = $request->input('q');
        $q = is_string($q) ? trim($q) : '';

        View::render('guests/index', [
            'title' => 'Guests',
            'guests' => $this->guests->search($q !== '' ? $q : null),
            'q' => $q,
            'canManage' => Auth::can(\Permission::GUESTS_MANAGE),
            'guestService' => $this->service,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::GUESTS_MANAGE);

        View::render('guests/form', [
            'title' => 'New Guest',
            'guest' => null,
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'guestService' => $this->service,
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::GUESTS_MANAGE);

        $payload = $this->validateGuestPayload($request);
        if ($payload === null) {
            redirect('/guests/create');
        }

        $id = $this->guests->create($payload);
        Session::flash('success', 'Guest profile created.');
        redirect('/guests/' . $id);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::GUESTS_VIEW);

        $guestId = (int) $id;
        $guest = $this->guests->findById($guestId);
        if ($guest === null) {
            Session::flash('error', 'Guest not found.');
            redirect('/guests');
        }

        View::render('guests/show', [
            'title' => (string) $guest['full_name'],
            'guest' => $guest,
            'documents' => $this->documents->forGuest($guestId),
            'stays' => $this->guests->stayHistory($guestId),
            'canManage' => Auth::can(\Permission::GUESTS_MANAGE),
            'guestService' => $this->service,
            'docErrors' => Session::pullFlash('doc_errors') ?? [],
        ], 'app');
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::GUESTS_MANAGE);

        $guest = $this->guests->findById((int) $id);
        if ($guest === null) {
            Session::flash('error', 'Guest not found.');
            redirect('/guests');
        }

        View::render('guests/form', [
            'title' => 'Edit Guest',
            'guest' => $guest,
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'guestService' => $this->service,
        ], 'app');
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::GUESTS_MANAGE);

        $guestId = (int) $id;
        if ($this->guests->findById($guestId) === null) {
            Session::flash('error', 'Guest not found.');
            redirect('/guests');
        }

        $payload = $this->validateGuestPayload($request);
        if ($payload === null) {
            redirect('/guests/' . $guestId . '/edit');
        }

        $this->guests->update($guestId, $payload);
        Session::flash('success', 'Guest profile updated.');
        redirect('/guests/' . $guestId);
    }

    public function documentStore(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::GUESTS_MANAGE);

        $guestId = (int) $id;
        if ($this->guests->findById($guestId) === null) {
            Session::flash('error', 'Guest not found.');
            redirect('/guests');
        }

        $files = $request->files();
        $file = $files['document'] ?? null;
        if (!is_array($file)) {
            Session::flash('doc_errors', ['document' => 'Choose a file to upload.']);
            redirect('/guests/' . $guestId);
        }

        $docType = (string) ($request->input('document_type') ?: 'id_scan');
        $result = $this->service->storeDocument($guestId, $file, $docType);
        if (!$result['ok']) {
            Session::flash('doc_errors', ['document' => $result['error']]);
            redirect('/guests/' . $guestId);
        }

        Session::flash('success', 'Document uploaded.');
        redirect('/guests/' . $guestId);
    }

    public function documentDestroy(Request $request, string $id, string $docId): void
    {
        Auth::requirePermission(\Permission::GUESTS_MANAGE);

        $guestId = (int) $id;
        $result = $this->service->deleteDocument($guestId, (int) $docId);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Document removed.');
        }

        redirect('/guests/' . $guestId);
    }

    public function documentDownload(Request $request, string $id, string $docId): void
    {
        Auth::requirePermission(\Permission::GUESTS_VIEW);

        $guestId = (int) $id;
        $doc = $this->documents->findById((int) $docId);
        if ($doc === null || (int) $doc['guest_id'] !== $guestId) {
            Session::flash('error', 'Document not found.');
            redirect('/guests/' . $guestId);
        }

        $absolute = $this->service->absolutePath((string) $doc['file_path']);
        if ($absolute === null || !is_file($absolute)) {
            Session::flash('error', 'File is missing on disk.');
            redirect('/guests/' . $guestId);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'application/octet-stream';
        $downloadName = $this->service->downloadFilename($doc);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($absolute));
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($absolute);
        exit;
    }

    /** @return array<string, mixed>|null */
    private function validateGuestPayload(Request $request): ?array
    {
        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'full_name' => 'required|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|max:30',
            'id_type' => 'nullable|max:30',
            'id_number' => 'nullable|max:100',
            'nationality' => 'nullable|max:80',
            'address' => 'nullable|max:255',
            'notes' => 'nullable|max:2000',
        ]);

        $idType = $request->input('id_type');
        $idType = is_string($idType) && $idType !== '' ? $idType : null;
        if ($idType !== null && !in_array($idType, Guest::ID_TYPES, true)) {
            Session::flash('errors', ['id_type' => 'Select a valid ID type.']);
            Session::flash('old', $request->post());
            return null;
        }

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            return null;
        }

        return [
            'full_name' => (string) $data['full_name'],
            'email' => $data['email'] !== null && $data['email'] !== '' ? (string) $data['email'] : null,
            'phone' => $data['phone'] !== null && $data['phone'] !== '' ? (string) $data['phone'] : null,
            'id_type' => $idType,
            'id_number' => $data['id_number'] !== null && $data['id_number'] !== '' ? (string) $data['id_number'] : null,
            'nationality' => $data['nationality'] !== null && $data['nationality'] !== '' ? (string) $data['nationality'] : null,
            'address' => $data['address'] !== null && $data['address'] !== '' ? (string) $data['address'] : null,
            'notes' => $data['notes'] !== null && $data['notes'] !== '' ? (string) $data['notes'] : null,
        ];
    }
}
