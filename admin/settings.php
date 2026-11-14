<?php
// admin/settings.php
// Admin account, password, sample-data tools and a quick look at what is stored in Firebase.
// Same sidebar, header and look as the other admin pages (admin/includes + assets/css/admin.css).
// Logic lives in assets/js/admin-settings.js. Every class below is prefixed st-.

$pageTitle = 'Settings';
$active    = 'settings';
$heading   = 'Settings';
$subtitle  = 'Manage your admin account and the data stored for AgriTrack.';

$topActions = '';

$extraHead = <<<'HTML'
<style>
    /* Settings page only. Every class is prefixed st- so nothing clashes with admin.css */
    .st {
        --ink: #1c2a22; --dark: #08301d; --green: #1a8348; --mint: #e6f5d6;
        --line: #dbe6d0; --muted: #56655b; --danger: #b8402b;
        color: var(--ink);
    }
    .st * { box-sizing: border-box; }
    .st-status { margin: 0 0 16px; font-size: .9rem; color: var(--muted); }
    .st-status:empty { display: none; }

    .st-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; align-items: start; }
    .st-card { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 24px -18px rgba(8, 48, 29, .3); padding: 24px 26px; }
    .st-card.wide { grid-column: 1 / -1; }
    .st-card h2 { margin: 0 0 4px; font-family: var(--font-display); font-size: 17px; letter-spacing: -.2px; color: var(--dark); }
    .st-card > p.st-sub { margin: 0 0 18px; font-size: .88rem; color: var(--muted); line-height: 1.55; }

    .st-field { display: grid; gap: 6px; margin-bottom: 14px; }
    .st-field label { font-size: .72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); }
    .st-field input {
        font: inherit; font-weight: 500; padding: 11px 14px; color: var(--ink); background: #fff;
        border: 1.5px solid var(--line); border-radius: 12px; transition: border-color .2s, box-shadow .2s;
    }
    .st-field input:focus { outline: 0; border-color: var(--green); box-shadow: 0 0 0 4px rgba(47, 165, 95, .16); }
    .st-field input[readonly] { background: #f6faf0; color: var(--muted); }
    .st-hint { margin: -6px 0 14px; font-size: .8rem; color: var(--muted); }

    .st-btn {
        display: inline-flex; align-items: center; gap: 8px; font: inherit; font-weight: 700; font-size: .88rem; cursor: pointer;
        padding: 10px 18px; border-radius: 12px; border: 1px solid var(--line); background: #fff; color: var(--dark);
        transition: border-color .15s, color .15s, filter .15s;
    }
    .st-btn:hover { border-color: var(--green); color: var(--green); }
    .st-btn:focus-visible { outline: 3px solid rgba(47, 165, 95, .4); outline-offset: 2px; }
    .st-btn.primary { color: #fff; border-color: transparent; background: linear-gradient(180deg, #35b06a, #12663a); }
    .st-btn.primary:hover { color: #fff; filter: brightness(1.08); }
    .st-btn.danger { color: var(--danger); }
    .st-btn.danger:hover { border-color: var(--danger); color: var(--danger); }
    .st-btn.primary.danger-solid { background: var(--danger); }
    .st-btn[disabled] { opacity: .55; cursor: default; filter: none; }
    .st-row { display: flex; flex-wrap: wrap; gap: 10px; }

    .st-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 0 0 18px; }
    .st-fact { padding: 14px 16px; border-radius: 14px; background: #f6faf0; border: 1px solid var(--line); }
    .st-fact b { display: block; font-family: var(--font-display); font-size: 1.5rem; line-height: 1.1; color: var(--dark); }
    .st-fact span { display: block; margin-top: 4px; font-size: .78rem; font-weight: 600; color: var(--muted); }

    .st-list { list-style: none; margin: 0 0 18px; padding: 0; border: 1px solid var(--line); border-radius: 14px; }
    .st-list li { display: flex; justify-content: space-between; gap: 12px; padding: 11px 14px; font-size: .88rem; border-bottom: 1px solid #eef3e8; }
    .st-list li:last-child { border-bottom: 0; }
    .st-list span { color: var(--muted); text-align: right; word-break: break-all; }

    .st-tool { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 14px 0; border-top: 1px solid #eef3e8; }
    .st-tool:first-of-type { border-top: 0; padding-top: 0; }
    .st-tool b { display: block; color: var(--dark); font-size: .95rem; }
    .st-tool p { margin: 2px 0 0; font-size: .84rem; color: var(--muted); max-width: 520px; line-height: 1.5; }

    .st dialog { width: min(460px, calc(100vw - 32px)); padding: 0; border: 0; border-radius: 20px; color: var(--ink); box-shadow: 0 30px 70px rgba(8, 48, 29, .35); }
    .st dialog::backdrop { background: rgba(8, 30, 20, .5); }
    .st-dlg { padding: 26px; }
    .st-dlg h2 { margin: 0; font-size: 1.2rem; color: var(--dark); }
    .st-dlg p { margin: 6px 0 0; font-size: .9rem; color: var(--muted); line-height: 1.55; }
    .st-dlg-foot { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }

    @media (max-width: 1100px) { .st-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 860px) { .st-grid { grid-template-columns: 1fr; } }
</style>
HTML;

require __DIR__ . '/includes/header.php';
?>

        <div class="st" id="stRoot">

            <p class="st-status" id="status" role="status"></p>

            <div class="st-grid">

                <!-- ACCOUNT -->
                <section class="st-card">
                    <h2>Your account</h2>
                    <p class="st-sub">This is the administrator account you are signed in with.</p>
                    <form id="profileForm" autocomplete="off">
                        <div class="st-field">
                            <label for="adminName">Display name</label>
                            <input type="text" id="adminName" maxlength="60" required>
                        </div>
                        <div class="st-field">
                            <label for="adminEmail">Email</label>
                            <input type="email" id="adminEmail" readonly>
                        </div>
                        <div class="st-field">
                            <label for="adminRole">Role</label>
                            <input type="text" id="adminRole" value="Administrator" readonly>
                        </div>
                        <button type="submit" class="st-btn primary" id="saveProfile">Save changes</button>
                    </form>
                </section>

                <!-- PASSWORD + SESSION -->
                <section class="st-card">
                    <h2>Password and sign-in</h2>
                    <p class="st-sub">For safety, passwords are changed from an email link. We will send it to the address on your account.</p>
                    <div class="st-row">
                        <button type="button" class="st-btn primary" id="resetPw">Email me a reset link</button>
                        <button type="button" class="st-btn" id="signOutBtn">Sign out</button>
                    </div>
                </section>

                <!-- DATA OVERVIEW -->
                <section class="st-card wide">
                    <h2>Data in Firebase</h2>
                    <p class="st-sub">What AgriTrack currently has stored, read live from your Realtime Database.</p>
                    <div class="st-facts">
                        <div class="st-fact"><b id="cFarmers">--</b><span>Farmer accounts</span></div>
                        <div class="st-fact"><b id="cCrops">--</b><span>Registered crops</span></div>
                        <div class="st-fact"><b id="cMarkets">--</b><span>Market listings</span></div>
                        <div class="st-fact"><b id="cSample">--</b><span>Sample farmers</span></div>
                    </div>
                    <ul class="st-list">
                        <li>Firebase project <span id="iProject">--</span></li>
                        <li>Database <span id="iDb">--</span></li>
                        <li>Signed in as <span id="iUid">--</span></li>
                    </ul>
                    <button type="button" class="st-btn" id="refreshBtn">Refresh counts</button>
                </section>

                <!-- TOOLS -->
                <section class="st-card wide">
                    <h2>Data tools</h2>
                    <p class="st-sub">Handy while you are setting things up. Sample farmers are clearly marked and never touch real accounts.</p>

                    <div class="st-tool">
                        <div><b>Add market listings</b><p>Adds the default Laguna markets so farmers can see them under Market locations. Skipped if listings already exist.</p></div>
                        <button type="button" class="st-btn" id="seedMarkets">Add markets</button>
                    </div>
                    <div class="st-tool">
                        <div><b>Add sample farmers</b><p>Creates demo farmers with crops, harvests and saved markets, so the dashboard and charts have something to show.</p></div>
                        <button type="button" class="st-btn" id="seedSample">Add sample data</button>
                    </div>
                    <div class="st-tool">
                        <div><b>Remove sample farmers</b><p>Deletes only the demo farmers and everything saved under them. Real farmer accounts are not touched.</p></div>
                        <button type="button" class="st-btn danger" id="clearSample">Remove sample data</button>
                    </div>
                </section>

            </div>

            <dialog id="confirmDlg" aria-labelledby="confirmTitle">
                <div class="st-dlg">
                    <h2 id="confirmTitle"></h2>
                    <p id="confirmText"></p>
                    <div class="st-dlg-foot">
                        <button type="button" class="st-btn" id="confirmCancel">Cancel</button>
                        <button type="button" class="st-btn primary" id="confirmOk">Confirm</button>
                    </div>
                </div>
            </dialog>

        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/admin-settings.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>