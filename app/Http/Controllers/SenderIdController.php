<?php

namespace App\Http\Controllers;

use App\Enums\SenderIdStatus;
use App\Http\Requests\SenderIds\StoreSenderIdRequest;
use App\Http\Requests\SenderIds\UpdateSenderIdRequest;
use App\Models\SenderId;
use App\Services\ActivityLogger;
use App\Support\PrivateStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SenderIdController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SenderId::class);

        $senderIds = SenderId::query()
            ->latest()
            ->paginate(15)
            ->through(fn (SenderId $senderId) => [
                'id' => $senderId->id,
                'value' => $senderId->value,
                'status' => $senderId->status->value,
                'status_label' => $senderId->status->label(),
                'has_document' => $senderId->hasDocument(),
                'rejection_reason' => $senderId->rejection_reason,
                'created_at' => $senderId->created_at?->toIso8601String(),
                'document_url' => $senderId->hasDocument()
                    ? URL::temporarySignedRoute(
                        'sender-ids.document',
                        now()->addMinutes(30),
                        ['sender_id' => $senderId->id],
                    )
                    : null,
            ]);

        return Inertia::render('sender-ids/Index', [
            'senderIds' => $senderIds,
            'companyStatus' => $request->user()?->currentCompany?->status?->value,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SenderId::class);

        return Inertia::render('sender-ids/Create');
    }

    public function store(StoreSenderIdRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $companyId = $user->currentCompanyId();

        abort_if($companyId === null, 403);

        $documentPath = null;
        $documentOriginalName = null;

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $extension = $file->getClientOriginalExtension();
            $documentPath = $file->storeAs(
                'sender-ids/'.$companyId,
                Str::uuid()->toString().($extension !== '' ? '.'.$extension : ''),
                PrivateStorage::name(),
            );
            $documentOriginalName = $file->getClientOriginalName();
        }

        $senderId = SenderId::query()->create([
            'company_id' => $companyId,
            'value' => $request->validated('value'),
            'status' => SenderIdStatus::Pending,
            'document_path' => $documentPath,
            'document_original_name' => $documentOriginalName,
            'uses_company_letterhead' => $request->boolean('uses_company_letterhead'),
            'requested_by' => $user->id,
        ]);

        $logger->log('sender_id.created', $senderId, [
            'value' => $senderId->value,
        ]);

        return redirect()
            ->route('sender-ids.index')
            ->with('success', 'Sender ID submitted for review.');
    }

    public function edit(SenderId $senderId): Response
    {
        $this->authorize('update', $senderId);

        return Inertia::render('sender-ids/Edit', [
            'senderId' => [
                'id' => $senderId->id,
                'value' => $senderId->value,
                'status' => $senderId->status->value,
                'uses_company_letterhead' => $senderId->uses_company_letterhead,
                'has_document' => $senderId->hasDocument(),
            ],
        ]);
    }

    public function update(
        UpdateSenderIdRequest $request,
        SenderId $senderId,
        ActivityLogger $logger,
    ): RedirectResponse {
        $wasApproved = $senderId->status === SenderIdStatus::Approved;
        $companyId = $senderId->company_id;

        $data = [
            'value' => $request->validated('value'),
            'uses_company_letterhead' => $request->boolean('uses_company_letterhead'),
        ];

        if ($request->hasFile('document')) {
            if ($senderId->document_path) {
                PrivateStorage::disk()->delete($senderId->document_path);
            }

            $file = $request->file('document');
            $extension = $file->getClientOriginalExtension();
            $data['document_path'] = $file->storeAs(
                'sender-ids/'.$companyId,
                Str::uuid()->toString().($extension !== '' ? '.'.$extension : ''),
                PrivateStorage::name(),
            );
            $data['document_original_name'] = $file->getClientOriginalName();
        }

        $senderId->fill($data);

        if ($wasApproved || $senderId->isDirty('value')) {
            $senderId->status = SenderIdStatus::Pending;
            $senderId->rejection_reason = null;
            $senderId->reviewed_by = null;
            $senderId->reviewed_at = null;
        }

        $senderId->save();

        $logger->log('sender_id.updated', $senderId, [
            'value' => $senderId->value,
            'reset_to_pending' => $wasApproved,
        ]);

        return redirect()
            ->route('sender-ids.index')
            ->with('success', $wasApproved
                ? 'Sender ID updated and reset to pending review.'
                : 'Sender ID updated.');
    }

    public function destroy(SenderId $senderId, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('delete', $senderId);

        if ($senderId->document_path) {
            PrivateStorage::disk()->delete($senderId->document_path);
        }

        $logger->log('sender_id.deleted', $senderId, [
            'value' => $senderId->value,
        ]);

        $senderId->delete();

        return redirect()
            ->route('sender-ids.index')
            ->with('success', 'Sender ID deleted.');
    }

    public function downloadDocument(SenderId $senderId): StreamedResponse
    {
        $this->authorize('downloadDocument', $senderId);

        abort_unless($senderId->hasDocument(), 404);
        abort_unless(
            PrivateStorage::disk()->exists($senderId->document_path),
            404,
        );

        return PrivateStorage::disk()->download(
            $senderId->document_path,
            $senderId->document_original_name ?? 'document',
        );
    }
}
