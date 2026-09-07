<?= view('partials/header', ['title' => 'Metode Pemenuhan']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / <a href="<?= base_url('/checkout') ?>">Data
        Penyewa</a> / Fulfillment</div>
    <h1>Metode Pemenuhan & Lokasi</h1>
  </div>
</div>

<section>
  <div class="wrap">
    <div class="step-indicator">
      <div class="s-item done">1. Keranjang</div>
      <div class="s-item done">2. Data Penyewa</div>
      <div class="s-item active">3. Fulfillment</div>
      <div class="s-item">4. Review</div>
      <div class="s-item">5. Selesai</div>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert-box alert-warn">
        <?php foreach (session()->getFlashdata('errors') as $err): ?>
          <div>
            <?= esc($err) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('/checkout/fulfillment') ?>" style="max-width:640px;">
      <?= csrf_field() ?>

      <div class="field-group">
        <label>Metode Pemenuhan</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px;">
          <?php
          $methods = [
            'SELF_PICKUP' => ['label' => 'Ambil Sendiri', 'desc' => 'Anda ambil & kembalikan langsung ke lokasi kami.'],
            'DELIVERY' => ['label' => 'Diantar', 'desc' => 'Kami antar ke alamat Anda.'],
            'ONSITE' => ['label' => 'Di Lokasi Anda', 'desc' => 'Untuk item yang dipasang/dipakai di tempat Anda.'],
            'SERVICE' => ['label' => 'Jasa/Personel', 'desc' => 'Petugas datang sesuai jadwal yang disepakati.'],
          ];
          foreach ($methods as $val => $m):
            ?>
            <label style="border:1px solid var(--line);border-radius:6px;padding:14px;cursor:pointer;display:block;">
              <input type="radio" name="fulfillment_method" value="<?= $val ?>" required style="margin-right:8px;">
              <strong style="font-size:0.92rem;">
                <?= esc($m['label']) ?>
              </strong>
              <p style="margin:6px 0 0;font-size:0.8rem;color:var(--muted);">
                <?= esc($m['desc']) ?>
              </p>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="field-group" style="margin-top:20px;">
        <label for="pickup_address">Alamat Pickup / Pelaksanaan (jika relevan)</label>
        <textarea id="pickup_address" name="pickup_address" rows="2"
          placeholder="Isi jika memilih Diantar / Di Lokasi Anda / Jasa"></textarea>
      </div>
      <div class="field-group">
        <label for="return_address">Alamat Pengembalian (jika berbeda)</label>
        <textarea id="return_address" name="return_address" rows="2"
          placeholder="Kosongkan jika sama dengan alamat di atas"></textarea>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <a href="<?= base_url('/checkout') ?>" class="btn btn-outline">Kembali</a>
        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Lanjut ke Review</button>
      </div>
    </form>
  </div>
</section>

<?= view('partials/footer') ?>