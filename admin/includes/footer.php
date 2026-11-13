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