</main>

</div>

<!-- CONFIRM DIALOG (used by every admin page through confirmAction() in admin-data.js) -->
<dialog id="confirmDlg" aria-labelledby="confirmTitle">
    <div class="dlg">
        <h2 id="confirmTitle"></h2>
        <p class="sub" id="confirmText"></p>
        <div class="dlg-foot">
            <button type="button" class="btn" id="confirmCancel">Cancel</button>
            <button type="button" class="btn primary" id="confirmOk">Confirm</button>
        </div>
    </div>
</dialog>
<?php /* The page adds its own <script type="module"> after this include, then closes </body></html>. */ ?>

<button id="gemini-float-btn" class="gemini-float-btn">
    <i class="ph ph-sparkle"></i>
    <span>Ask Gemini</span>
</button>

<!-- DARK MODE TOGGLE FALLBACK & SCRIPT -->
<script>
(function () {
    try {
        if (document.getElementById('theme-toggle')) return;
        var b = document.createElement('button');
        b.id = 'theme-toggle';
        b.type = 'button';
        b.className = 'theme-toggle user-theme-toggle';
        b.setAttribute('aria-label', 'Toggle dark mode');
        b.setAttribute('aria-pressed', 'false');
        b.innerHTML = '<i class="ph-bold ph-moon theme-icon" aria-hidden="true"></i>';
        document.body.appendChild(b);
    } catch (e) {}
})();
</script>
<script src="../assets/js/theme.js"></script>