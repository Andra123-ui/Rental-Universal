<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>

<div class="page-head">
    <h1>Halo, <?= esc(session()->get('admin_name')) ?></h1>
</div>

<div class="panel">
    <p class="muted" style="margin-top:0;">Role: <strong><?= esc(session()->get('admin_role')) ?></strong></p>
    <p style="margin-bottom:8px;">Hak akses:</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php if (session()->get('admin_role') === 'owner'): ?>
        <span class="badge badge-ok">semua hak akses (owner)</span>
        <?php else: ?>
        <?php foreach ((array) session()->get('admin_permissions') as $code): ?>
        <span class="badge badge-ok"><?= esc($code) ?></span>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>