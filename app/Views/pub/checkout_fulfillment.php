<?php
/**
 * @var array $cartItems
 * @var float $subtotal
 * @var float $grandTotal
 */
?>
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

    <form method="post" action="<?= base_url('/checkout/fulfillment') ?>" style="max-width:800px;">
      <?= csrf_field() ?>

      <!-- LOOPING PER ITEM -->
      <?php foreach ($cartItems as $index => $item): ?>
        <div class="accordion-item"
          style="border:1px solid var(--line); border-radius:8px; margin-bottom:16px; background:#fff; overflow:hidden;">

          <!-- HEADER ACCORDION (Bisa Diklik) -->
          <div class="accordion-header" onclick="toggleAccordion(<?= $index ?>)"
            style="padding:16px 20px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:#f8f9fa; transition:background 0.2s;">

            <div>
              <span class="badge"
                style="background:var(--primary); color:#fff; padding:2px 8px; border-radius:4px; font-size:0.7rem; text-transform:uppercase;">
                <?= esc($item['item_type']) ?>
              </span>
              <h3 style="margin:8px 0 4px; font-size:1rem;">
                <?= esc($item['name']) ?>
              </h3>
              <p style="margin:0; font-size:0.8rem; color:var(--muted);">
                Jumlah:
                <?= esc($item['qty']) ?> |
                Rp
                <?= number_format($item['line_total'], 0, ',', '.') ?>
              </p>
            </div>

            <!-- Ikon Panah -->
            <div class="accordion-icon" id="icon-<?= $index ?>" style="font-size:1.2rem; transition:transform 0.3s;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>

          <!-- BODY ACCORDION (Tersembunyi Awalnya) -->
          <div class="accordion-body" id="body-<?= $index ?>"
            style="display:none; padding:20px; border-top:1px solid var(--line);">

            <!-- Pilihan Metode (Hanya Pick Up & Driver) -->
            <div class="field-group">
              <label style="font-weight:600; font-size:0.9rem;">Pilih Metode Pemenuhan</label>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
                <?php
                // HANYA 2 METODE SESUAI PERMINTAAN
                $methods = [
                  'SELF_PICKUP' => ['label' => 'Ambil Sendiri', 'desc' => 'Ambil & kembalikan ke lokasi kami.'],
                  'DELIVERY' => ['label' => 'Diantar (Driver)', 'desc' => 'Kami antar ke alamat Anda.'],
                ];
                foreach ($methods as $val => $m):
                  $inputId = "method_{$index}_{$val}";
                  ?>
                  <label for="<?= $inputId ?>"
                    style="border:1px solid var(--line); border-radius:6px; padding:12px; cursor:pointer; display:block; transition:all 0.2s;">
                    <input type="radio" id="<?= $inputId ?>" name="items[<?= $index ?>][fulfillment_method]"
                      value="<?= $val ?>" required style="margin-right:8px;">
                    <strong style="font-size:0.85rem;">
                      <?= esc($m['label']) ?>
                    </strong>
                    <p style="margin:4px 0 0; font-size:0.75rem; color:var(--muted); line-height:1.3;">
                      <?= esc($m['desc']) ?>
                    </p>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Alamat Spesifik per Item -->
            <div class="field-group" style="margin-top:16px;">
              <label for="pickup_<?= $index ?>" style="font-size:0.85rem;">Alamat Pickup / Pengantaran</label>
              <textarea id="pickup_<?= $index ?>" name="items[<?= $index ?>][pickup_address]" rows="2"
                placeholder="Isi alamat jika memilih Diantar" style="font-size:0.85rem; padding:8px;"></textarea>
            </div>

            <div class="field-group" style="margin-top:8px;">
              <label for="return_<?= $index ?>" style="font-size:0.85rem;">Alamat Pengembalian (jika berbeda)</label>
              <textarea id="return_<?= $index ?>" name="items[<?= $index ?>][return_address]" rows="2"
                placeholder="Kosongkan jika sama dengan alamat di atas"
                style="font-size:0.85rem; padding:8px;"></textarea>
            </div>

            <!-- Hidden input -->
            <input type="hidden" name="items[<?= $index ?>][cart_key]" value="<?= esc($item['key']) ?>">
            <input type="hidden" name="items[<?= $index ?>][catalog_item_id]"
              value="<?= esc($item['catalog_item_id']) ?>">

          </div>
        </div>
      <?php endforeach; ?>
      <!-- END LOOPING -->

      <!-- TOTAL & TOMBOL -->
      <div style="background:#f8f9fa; border:1px solid var(--line); border-radius:8px; padding:20px; margin-top:24px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
          <span>Subtotal</span>
          <strong>Rp
            <?= number_format($subtotal, 0, ',', '.') ?>
          </strong>
        </div>
        <div
          style="display:flex; justify-content:space-between; border-top:1px dashed var(--line); padding-top:12px; margin-top:12px;">
          <span style="font-size:1.1rem; font-weight:600;">Total</span>
          <strong style="font-size:1.1rem; color:var(--primary);">Rp
            <?= number_format($grandTotal, 0, ',', '.') ?>
          </strong>
        </div>
      </div>

      <div style="display:flex; gap:12px; margin-top:20px;">
        <a href="<?= base_url('/checkout') ?>" class="btn btn-outline">Kembali</a>
        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">Lanjut ke Review</button>
      </div>
    </form>
  </div>
</section>

<!-- JAVASCRIPT UNTUK ACCORDION -->
<script>
  function toggleAccordion(index) {
    const body = document.getElementById('body-' + index);
    const icon = document.getElementById('icon-' + index);

    if (body.style.display === 'none' || body.style.display === '') {
      body.style.display = 'block';
      icon.style.transform = 'rotate(180deg)';
    } else {
      body.style.display = 'none';
      icon.style.transform = 'rotate(0deg)';
    }
  }
</script>

<?= view('partials/footer') ?>