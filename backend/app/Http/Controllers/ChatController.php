<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Placement;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Notifications\ChatMessageNotification;
use App\Services\Audit;
use App\Services\ChatAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    private function eligible(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [Role::Student, Role::Coordinator, Role::Supervisor], true), 403);
        abort_unless(ChatAccess::enabled(), 423, 'Chat is currently disabled by the administrator.');
    }

    public function settings(Request $request)
    {
        return response()->json(['enabled' => ChatAccess::enabled()], headers: ['Cache-Control' => 'no-store']);
    }

    public function updateSettings(Request $request)
    {
        abort_unless($request->user()->role === Role::Admin, 403);
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        DB::transaction(function () use ($data, $request) {
            $before = DB::table('chat_settings')->where('id', 1)->lockForUpdate()->first();
            DB::table('chat_settings')->where('id', 1)->update(['enabled' => $data['enabled'], 'updated_at' => now()]);
            Audit::record($request->user(), 'chat.availability_updated', $request->user(), ['before' => (bool) $before->enabled, 'after' => $data['enabled']]);
        });

        return $this->settings($request);
    }

    public function index(Request $request)
    {
        $this->eligible($request);
        $user = $request->user();
        $page = Conversation::with(['enrollment.student.user', 'enrollment.programTerm.program.coordinators', 'placement.hostEstablishment'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $user->id)->whereRaw('chat_messages.id > COALESCE((SELECT last_message_id FROM conversation_reads WHERE conversation_reads.conversation_id = chat_messages.conversation_id AND user_id = ?), 0)', [$user->id])])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(30);
        $page->getCollection()->transform(fn ($c) => $this->summary($c) + ['unread_count' => $c->unread_count]);

        return response()->json($page, headers: ['Cache-Control' => 'no-store']);
    }

    private function summary(Conversation $conversation): array
    {
        return ['id' => $conversation->id, 'type' => $conversation->placement_id ? 'placement' : 'coordinator',
            'student' => $conversation->enrollment->student->user?->name ?? 'Former student',
            'program' => $conversation->enrollment->programTerm->program->code,
            'host' => $conversation->placement?->hostEstablishment?->name,
            'enrollment_id' => $conversation->student_enrollment_id, 'placement_id' => $conversation->placement_id,
            'writable' => ChatAccess::writable($conversation),
            'coordinator_available' => ChatAccess::coordinator($conversation->enrollment) !== null,
            'participants' => array_map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->role->value], ChatAccess::participants($conversation)),
            'updated_at' => $conversation->updated_at];
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->eligible($request);
        Gate::authorize('view', $conversation);
        $data = $request->validate(['before' => ['nullable', 'integer', 'min:1']]);
        $messages = $conversation->messages()->with('sender:id,name')->when($data['before'] ?? null, fn ($q, $id) => $q->where('id', '<', $id))->orderByDesc('id')->limit(51)->get();
        $more = $messages->count() > 50;

        return response()->json(['conversation' => $this->summary($conversation), 'has_older' => $more,
            'messages' => $messages->take(50)->reverse()->values()->map(fn ($m) => ['id' => $m->id, 'sender_id' => $m->sender_id, 'sender' => $m->sender?->name ?? 'Former participant', 'body' => $m->body, 'created_at' => $m->created_at])], headers: ['Cache-Control' => 'no-store']);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $this->eligible($request);
        Gate::authorize('send', $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000', 'regex:/\S/u'], 'client_id' => ['required', 'uuid']]);
        DB::transaction(function () use ($request, $conversation, $data) {
            // Serialize messages and recheck current assignment and placement status before writing.
            $programId = $conversation->enrollment->programTerm->program_id;
            Program::whereKey($programId)->lockForUpdate()->firstOrFail();
            StudentEnrollment::whereKey($conversation->student_enrollment_id)->lockForUpdate()->firstOrFail();
            if ($conversation->placement_id) {
                Placement::whereKey($conversation->placement_id)->lockForUpdate()->firstOrFail();
            }
            $request->user()->refresh();
            $locked = Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('send', $locked);
            $existing = ChatMessage::where('sender_id', $request->user()->id)->where('client_id', $data['client_id'])->first();
            if ($existing) {
                abort_unless($existing->conversation_id === $locked->id, 409);

                return;
            }
            $message = $locked->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['body']), 'client_id' => $data['client_id']]);
            $locked->touch();
            foreach (ChatAccess::participants($locked) as $recipient) {
                if ($recipient->id !== $request->user()->id && ChatAccess::scope(Conversation::withoutGlobalScope('participant')->whereKey($locked->id), $recipient)->exists()) {
                    $recipient->notify(new ChatMessageNotification($locked->id));
                }
            }
            Audit::record($request->user(), 'chat.message_sent', $locked, ['message_id' => $message->id], $locked->enrollment->programTerm->program_id);
        });

        return $this->show($request, $conversation);
    }

    public function read(Request $request, Conversation $conversation)
    {
        $this->eligible($request);
        Gate::authorize('view', $conversation);
        $data = $request->validate(['last_message_id' => ['required', 'integer', 'min:1']]);
        abort_unless($conversation->messages()->whereKey($data['last_message_id'])->exists(), 422);
        DB::statement('INSERT INTO conversation_reads (conversation_id, user_id, last_message_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_message_id = GREATEST(last_message_id, VALUES(last_message_id))', [$conversation->id, $request->user()->id, $data['last_message_id']]);

        return response()->noContent();
    }
}
