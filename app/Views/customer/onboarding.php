<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<h1 class="auth-title">Lengkapi Profil Anda</h1>
<p class="auth-subtitle">Sebelum lanjut, lengkapi data berikut supaya transaksi Anda tercatat dengan benar.</p>

<?php if (session()->getFlashdata('errors')): ?>
<div class="alert alert-error">
    <ul>
        <?php foreach (session()->getFlashdata('errors') as $error): ?>
        <li><?= esc($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form action="<?= site_url('account/onboarding') ?>" method="post" class="auth-form">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="name">Nama Lengkap</label>
        <input type="text" id="name" name="name" class="form-control" required
            value="<?= esc(old('name', $customer['name'] === $customer['phone'] ? '' : $customer['name'])) ?>">
    </div>

    <div class="form-group">
        <label for="email">Email (opsional)</label>
        <input type="email" id="email" name="email" class="form-control"
            value="<?= esc(old('email', $customer['email'] ?? '')) ?>">
    </div>

    <div class="form-group">
        <label for="address">Alamat (opsional)</label>
        <textarea id="address" name="address" class="form-control"
            rows="3"><?= esc(old('address', $customer['address'] ?? '')) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan & Lanjutkan</button>
</form>

<?= $this->endSection() ?>