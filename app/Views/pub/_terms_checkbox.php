<?php
/**
 * app/Views/pub/_terms_checkbox.php
 * Partial checkbox persetujuan syarat & ketentuan, dipakai di form guest & account.
 */
?>
<label style="display:flex;gap:8px;align-items:flex-start;font-size:0.85rem;color:var(--muted);margin:16px 0;">
    <input type="checkbox" name="agree_terms" required style="margin-top:3px;">
    Saya menyetujui <a href="<?= base_url('/terms') ?>" target="_blank" style="color:var(--accent);">syarat & ketentuan
        rental</a> (v<?= esc(\App\Controllers\Terms::CURRENT_VERSION) ?>) dan kebijakan privasi.
</label>
<input type="hidden" name="terms_version" value="<?= esc(\App\Controllers\Terms::CURRENT_VERSION) ?>">