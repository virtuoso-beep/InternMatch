<div x-data="{ contentHeight: 800, viewportHeight: window.innerHeight }"
     x-on:resize.window="viewportHeight = window.innerHeight"
     x-on:message.window="if ($event.origin === window.location.origin && $event.source === $refs.workspace.contentWindow && $event.data?.type === 'internmatch:admin-height' && Number.isFinite($event.data.height)) contentHeight = $event.data.height">
    <iframe x-ref="workspace" title="InternMatch {{ $workspaceSection }}"
            src="{{ route('admin.workspace', ['section' => $workspaceSection]) }}"
            x-bind:style="'width:100%;border:0;display:block;height:' + Math.max(400, Math.min(contentHeight, Math.max(500, viewportHeight - 200))) + 'px'"
            style="width:100%;border:0;height:800px;display:block" ></iframe>
</div>
