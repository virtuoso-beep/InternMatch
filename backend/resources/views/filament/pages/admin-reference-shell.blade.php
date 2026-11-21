@php
    // Same groups and links as before; only the presentation changed (matches the portal shell).
    $groups = [
        'User management' => ['students' => 'Students', 'users' => 'User accounts', 'roles' => 'Roles and permissions', 'student-import' => 'Student import'],
        'Internship management' => ['hosts' => 'Host establishments', 'opportunities' => 'Opportunities', 'moa' => 'MOA records'],
        'Academic management' => ['programs' => 'Programs and curriculum', 'monitoring-settings' => 'Monitoring settings', 'terms' => 'Academic terms'],
        'Communication and alerts' => ['chat-settings' => 'Chat availability', 'notifications' => 'Notifications'],
        'System administration' => ['audit' => 'Audit trail', 'backup' => 'Backup and recovery', 'settings' => 'System settings'],
    ];
    $icons = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'students' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'roles' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'student-import' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
        'hosts' => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
        'opportunities' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
        'moa' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'programs' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'monitoring-settings' => '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
        'terms' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M17 14h-6"/><path d="M13 18H7"/>',
        'chat-settings' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'notifications' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'audit' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'backup' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'profile' => '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
    ];
    $icon = fn (string $key) => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($icons[$key] ?? $icons['dashboard']).'</svg>';
    $currentGroup = null;
    $currentLabel = $workspaceSection === 'dashboard' ? 'Dashboard' : null;
    foreach ($groups as $groupLabel => $items) {
        if (array_key_exists($workspaceSection, $items)) {
            $currentGroup = $groupLabel;
            $currentLabel = $items[$workspaceSection];
        }
    }
    $currentLabel ??= $workspaceSection === 'profile' ? 'My profile' : 'Administration';
    $initials = collect(explode(' ', auth()->user()->name))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('');
@endphp
<style>
.fi-topbar,.fi-sidebar,.fi-header{display:none!important}.fi-main{padding:0!important;max-width:none!important}.fi-main-ctn{padding:0!important}
.im-admin{--bg:oklch(0.968 0.003 250);--card:#fff;--fg:oklch(0.22 0.01 260);--muted:oklch(0.53 0.015 258);--border:oklch(0.915 0.006 255);--brand:oklch(0.35 0.11 18);--brand-soft:oklch(0.955 0.018 20);--hover:oklch(0.965 0.005 250);--shadow:0 12px 24px -8px rgb(16 24 40/.22);position:fixed;inset:0;z-index:40;background:var(--bg);color:var(--fg);font-family:"Public Sans",ui-sans-serif,system-ui,sans-serif;font-size:14px;-webkit-font-smoothing:antialiased}
.im-admin.dark{--bg:oklch(0.175 0.008 20);--card:oklch(0.215 0.009 20);--fg:oklch(0.95 0.004 20);--muted:oklch(0.74 0.01 20);--border:oklch(1 0 0/.11);--brand:oklch(0.78 0.1 18);--brand-soft:oklch(0.3 0.06 18);--hover:oklch(0.27 0.01 20);--shadow:0 16px 32px -8px rgb(0 0 0/.6);color-scheme:dark}
.im-admin *{box-sizing:border-box}.im-admin a{color:inherit;text-decoration:none}.im-admin button{font:inherit;color:inherit;background:none;border:0;cursor:pointer}
.im-admin :focus-visible{outline:2px solid oklch(0.62 0.13 70);outline-offset:2px}
.im-skip{position:absolute;left:-9999px;top:8px;background:var(--card);padding:8px 12px;border-radius:8px;z-index:60;font-weight:600}.im-skip:focus{left:8px}
.im-side{position:fixed;inset:0 auto 0 0;width:256px;background:var(--card);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:45}
.im-brandbar{height:64px;display:flex;align-items:center;gap:12px;padding:0 16px;border-bottom:1px solid var(--border);flex-shrink:0}.im-brandbar img{width:36px;height:36px}.im-brandbar strong{display:block;font-size:16px;font-weight:600;color:var(--brand);line-height:1.2}.im-brandbar span{display:block;font-size:12px;color:var(--muted)}
.im-nav{flex:1;overflow-y:auto;padding:16px 12px;scrollbar-width:none}.im-nav::-webkit-scrollbar{display:none}
.im-group{margin:0 0 20px}.im-group p{margin:0 0 6px;padding:0 12px;font-size:12px;font-weight:600;color:var(--muted)}
.im-link{display:flex;align-items:center;gap:12px;padding:8px 12px;border-radius:8px;transition:background-color .15s}.im-link:hover{background:var(--hover)}.im-link[aria-current=page]{background:var(--brand-soft);color:var(--brand);font-weight:600}.im-link svg{flex-shrink:0}
.im-main{position:fixed;inset:0 0 0 256px;display:flex;flex-direction:column}
.im-head{height:64px;background:oklch(0.35 0.11 18);color:oklch(0.99 0.005 60);display:flex;align-items:center;gap:12px;padding:0 24px;flex-shrink:0}
.im-crumb{flex:1;min-width:0;display:flex;align-items:center;gap:6px;font-size:14px;white-space:nowrap;overflow:hidden}.im-crumb span{opacity:.8}.im-crumb strong{font-weight:600;overflow:hidden;text-overflow:ellipsis}
.im-hbtn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;color:inherit}.im-hbtn:hover,.im-hbtn[aria-expanded=true]{background:rgb(255 255 255/.15)}
.im-burger{display:none}
.im-account{display:flex;align-items:center;gap:8px;padding:4px 8px 4px 4px;border-radius:8px}.im-account:hover,.im-account[aria-expanded=true]{background:rgb(255 255 255/.15)}.im-avatar{width:32px;height:32px;border-radius:50%;background:rgb(255 255 255/.15);display:grid;place-items:center;font-size:12px;font-weight:600}.im-uname{font-size:14px;font-weight:500;max-width:11rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.im-pop{position:absolute;top:56px;right:24px;width:256px;background:var(--card);color:var(--fg);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);overflow:hidden;z-index:50}
.im-pop .im-id{padding:12px 16px;border-bottom:1px solid var(--border)}.im-pop .im-id strong{display:block;font-weight:600}.im-pop .im-id span{display:block;font-size:12px;color:var(--muted);margin-top:4px}
.im-pop a,.im-pop button.im-item{display:flex;width:100%;align-items:center;gap:10px;padding:10px 16px;text-align:left}.im-pop a:hover,.im-pop button.im-item:hover{background:var(--hover)}
.im-pop form button{border-top:1px solid var(--border);font-weight:600}
.im-content{flex:1;min-height:0;padding:24px}.im-content iframe{width:100%;height:100%;border:0;display:block;background:transparent;color-scheme:normal}
.im-scrim{position:fixed;inset:0;background:rgb(16 24 40/.45);z-index:44}
.im-theme{right:80px;top:56px;width:176px;padding:4px}.im-theme button{display:flex;width:100%;align-items:center;gap:10px;padding:8px 10px;border-radius:6px;text-align:left}.im-theme button:hover{background:var(--hover)}.im-theme button[aria-checked=true]{font-weight:600;color:var(--brand)}
@media(max-width:1023px){.im-side{transform:translateX(-100%);transition:transform .2s;box-shadow:var(--shadow)}.im-side.open{transform:none}.im-main{left:0}.im-burger{display:inline-flex}.im-head{padding:0 12px}.im-content{padding:12px}.im-uname{display:none}.im-pop{right:12px}.im-theme{right:60px}}
@media(max-width:767px){.im-parent{display:none}}
@media(prefers-reduced-motion:reduce){.im-side,.im-link{transition:none}}
.im-modal-scrim{position:fixed;inset:0;z-index:70;background:rgb(15 15 20/.55);display:grid;place-items:center;padding:16px}.im-modal{width:100%;max-width:400px;background:var(--card);color:var(--fg);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow)}.im-modal h2{margin:0;font-size:18px;font-weight:600}.im-modal-head{padding:20px 20px 0}.im-modal-body{padding:8px 20px 20px;color:var(--muted);font-size:14px;line-height:1.5}.im-modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;border-top:1px solid var(--border)}.im-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 16px;border-radius:8px;font-size:14px;font-weight:600}.im-admin .im-btn-outline{border:1px solid var(--border);background:var(--card)}.im-admin .im-btn-outline:hover{background:var(--hover)}.im-admin .im-btn-primary{background:var(--brand);color:#fff}.im-admin.dark .im-btn-primary{color:oklch(0.2 0.04 18)}@media(max-width:639px){.im-modal-scrim{padding:0;place-items:end stretch}.im-modal{max-width:none;border-radius:12px 12px 0 0}}
</style>
<div class="im-admin" :class="{ dark: dark }" x-cloak
     x-data="{
        drawer: false, menu: 'none', theme: 'system', dark: false, confirmSignOut: false,
        key: 'internmatch:theme',
        init() {
            try { const p = localStorage.getItem(this.key); this.theme = (p === 'light' || p === 'dark') ? p : 'system'; } catch (e) {}
            this.apply();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { if (this.theme === 'system') this.apply(); });
        },
        apply() { this.dark = this.theme === 'dark' || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches); },
        choose(value) {
            this.theme = value;
            try { value === 'system' ? localStorage.removeItem(this.key) : localStorage.setItem(this.key, value); } catch (e) {}
            this.apply(); this.menu = 'none';
        }
     }"
     @keydown.escape.window="menu = 'none'; drawer = false; confirmSignOut = false">
    <a class="im-skip" href="#im-content">Skip to content</a>

    <div class="im-scrim" x-show="drawer" x-transition.opacity @click="drawer = false" aria-hidden="true"></div>
    <aside class="im-side" :class="{ open: drawer }" aria-label="Administrator navigation">
        <div class="im-brandbar">
            <img src="/images/umtc-seal.png" alt="UM Tagum College seal">
            <div><strong>InternMatch</strong><span>System Administrator</span></div>
        </div>
        <nav class="im-nav" aria-label="Administrator navigation">
            <div class="im-group">
                <p>Overview</p>
                <a class="im-link" href="/manage" @if($workspaceSection === 'dashboard') aria-current="page" @endif>{!! $icon('dashboard') !!}Dashboard</a>
            </div>
            @foreach($groups as $groupLabel => $items)
                <div class="im-group">
                    <p>{{ $groupLabel }}</p>
                    @foreach($items as $key => $label)
                        <a class="im-link" href="/manage/administration?section={{ $key }}" @if($workspaceSection === $key) aria-current="page" @endif>{!! $icon($key) !!}{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    <div class="im-main">
        <header class="im-head" @click.outside="menu = 'none'">
            <button type="button" class="im-hbtn im-burger" aria-label="Open navigation" @click="drawer = true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <nav class="im-crumb" aria-label="Breadcrumb">
                <span class="im-parent">System Administrator</span>
                @if($currentGroup)<span class="im-parent">/ {{ $currentGroup }}</span>@endif
                <strong aria-current="page"><span class="im-parent" style="font-weight:400">/</span> {{ $currentLabel }}</strong>
            </nav>

            <div style="position:relative">
                <button type="button" class="im-hbtn" aria-label="Colour theme" title="Colour theme" :aria-expanded="menu === 'theme'" aria-haspopup="menu" @click="menu = menu === 'theme' ? 'none' : 'theme'">
                    <svg x-show="theme === 'light'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg x-show="theme === 'dark'" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    <svg x-show="theme === 'system'" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </button>
            </div>
            <a class="im-hbtn" href="/manage/administration?section=notifications" aria-label="Notifications" title="Notifications">{!! $icon('notifications') !!}</a>
            <button type="button" class="im-account" :aria-expanded="menu === 'user'" aria-haspopup="menu" aria-label="Account menu" @click="menu = menu === 'user' ? 'none' : 'user'">
                <span class="im-avatar">{{ $initials }}</span>
                <span class="im-uname">{{ auth()->user()->name }}</span>
            </button>

            <div class="im-pop im-theme" role="menu" x-show="menu === 'theme'" x-cloak>
                <button type="button" role="menuitemradio" :aria-checked="theme === 'light'" @click="choose('light')">Light</button>
                <button type="button" role="menuitemradio" :aria-checked="theme === 'dark'" @click="choose('dark')">Dark</button>
                <button type="button" role="menuitemradio" :aria-checked="theme === 'system'" @click="choose('system')">Match device</button>
            </div>
            <div class="im-pop" role="menu" x-show="menu === 'user'" x-cloak>
                <div class="im-id"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span><span>System Administrator, all programs</span></div>
                <a role="menuitem" href="/manage/administration?section=profile">{!! $icon('profile') !!}My profile</a>
                <button type="button" class="im-item" role="menuitem" @click="menu = 'none'; confirmSignOut = true">Sign out</button>
            </div>
        </header>
        <main class="im-content" id="im-content" tabindex="-1">
            <iframe title="InternMatch {{ $currentLabel }}" src="{{ route('admin.workspace', ['section' => $workspaceSection]) }}"></iframe>
        </main>
    </div>
    <div class="im-modal-scrim" x-show="confirmSignOut" x-cloak x-transition.opacity @click.self="confirmSignOut = false">
        <div class="im-modal" role="dialog" aria-modal="true" aria-labelledby="im-signout-title" aria-describedby="im-signout-desc" x-trap.noscroll="confirmSignOut">
            <div class="im-modal-head"><h2 id="im-signout-title">Sign out?</h2></div>
            <p class="im-modal-body" id="im-signout-desc">You will need to sign in again to continue.</p>
            <form class="im-modal-foot" method="POST" action="{{ route('filament.manage.auth.logout') }}">@csrf
                <button type="button" class="im-btn im-btn-outline" @click="confirmSignOut = false" x-init="$watch('confirmSignOut', open => open && $nextTick(() => $el.focus()))">Cancel</button>
                <button type="submit" class="im-btn im-btn-primary">Sign out</button>
            </form>
        </div>
    </div>
</div>
