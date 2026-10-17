

<?php $__env->startSection('title', 'Detail reservasi'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $resto  = $reservasi->resto;
        $dibuat = $reservasi->created_at->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H.i');
    ?>

    <div class="page">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-1">Detail &amp; ubah reservasi</h1>
                <p class="subtitle mb-0"><?php echo e($reservasi->kode_booking); ?> · <?php echo e($resto->nama); ?> · Dibuat <?php echo e($dibuat); ?> WIB</p>
            </div>
            <a href="<?php echo e(route('customer.reservations')); ?>" class="btn btn-outline-purple">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke riwayat
            </a>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="panel mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <h5 class="mb-0"><?php echo e($resto->nama); ?> · <?php echo e($reservasi->user->name); ?></h5>
                        <?php if($reservasi->bisa_diedit): ?>
                            <span class="status status-check_in">Belum dikonfirmasi · dapat diedit</span>
                        <?php else: ?>
                            <span class="status status-selesai">Terkunci · tidak dapat diedit</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="status status-<?php echo e($reservasi->status); ?>">Reservasi: <?php echo e($reservasi->status_label); ?></span>
                        <span class="status <?php echo e($reservasi->dp_kelas); ?>">DP: <?php echo e($reservasi->dp_label); ?></span>
                    </div>
                    <?php if($reservasi->catatan): ?>
                        <p class="subtitle small mb-0">Catatan: <?php echo e($reservasi->catatan); ?></p>
                    <?php endif; ?>
                    <?php if($reservasi->alasan_penolakan && ! $reservasi->batal_otomatis): ?>
                        <p class="small mt-2 mb-0" style="color:#bd3448;">Pesan dari resto: <?php echo e($reservasi->alasan_penolakan); ?></p>
                    <?php endif; ?>
                </div>

                <div class="panel mb-3">
                    <?php if($reservasi->bisa_diedit): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Ubah jadwal, tamu &amp; menu</h5>
                            <span class="sub">Mode edit</span>
                        </div>

                        <form method="POST" action="<?php echo e(route('customer.reservations.update', $reservasi)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="field-label" for="tanggal">Tanggal kunjungan</label>
                                    <input type="date" class="form-control" id="tanggal" name="tanggal"
                                        min="<?php echo e(now()->toDateString()); ?>"
                                        value="<?php echo e(old('tanggal', $reservasi->tanggal->toDateString())); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="field-label" for="jam">Jam</label>
                                    <input type="time" class="form-control" id="jam" name="jam"
                                        value="<?php echo e(old('jam', substr($reservasi->jam, 0, 5))); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="field-label" for="jumlah_orang">Jumlah orang</label>
                                    <input type="number" class="form-control" id="jumlah_orang" name="jumlah_orang"
                                        min="1" max="100" value="<?php echo e(old('jumlah_orang', $reservasi->jumlah_orang)); ?>" required>
                                </div>
                            </div>

                            <p class="field-label mt-4 mb-2">Pesanan Anda · ubah kuantitas dengan − / +</p>

                            <?php $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $lama  = (int) ($qty[$menu->id] ?? 0);
                                    $nilai = (int) old('menu.'.$menu->id, $lama);
                                    $maks  = $menu->tersedia ? 50 : $lama;
                                ?>
                                <?php if(! $menu->tersedia && $lama === 0) continue; ?>

                                <div class="menu-row d-flex align-items-center gap-3 py-2">
                                    <?php if($menu->foto): ?>
                                        <img src="<?php echo e(asset('storage/'.$menu->foto)); ?>" class="menu-thumb" alt="<?php echo e($menu->nama); ?>">
                                    <?php else: ?>
                                        <div class="menu-thumb menu-thumb-ph"><i class="bi bi-egg-fried"></i></div>
                                    <?php endif; ?>

                                    <div class="flex-grow-1">
                                        <strong><?php echo e($menu->nama); ?></strong>
                                        <?php if (! ($menu->tersedia)): ?>
                                            <span class="status status-ditolak ms-1">Habis</span>
                                        <?php endif; ?>
                                        <div class="sub line"><?php echo e($rupiah($menu->harga)); ?> × <?php echo e($nilai); ?></div>
                                    </div>

                                    <div class="qty-box">
                                        <button type="button" class="qty-btn" data-step="-1" aria-label="Kurangi">−</button>
                                        <input type="number" class="qty-input" name="menu[<?php echo e($menu->id); ?>]"
                                            value="<?php echo e($nilai); ?>" min="0" max="<?php echo e($maks); ?>" data-price="<?php echo e($menu->harga); ?>">
                                        <button type="button" class="qty-btn" data-step="1" aria-label="Tambah">+</button>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            <div id="validasi" class="validasi ok mt-3"></div>
                            <p class="sub mt-2 mb-0">Kapasitas kursi dicek otomatis saat perubahan disimpan. Menu habis tidak dapat ditambahkan. DP tetap perlu diverifikasi.</p>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-purple">
                                    <i class="bi bi-check2 me-1"></i> Simpan perubahan
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <h5 class="mb-3">Jadwal &amp; pesanan</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><div class="sub">Tanggal kunjungan</div><strong><?php echo e($reservasi->tanggal->locale('id')->translatedFormat('d F Y')); ?></strong></div>
                            <div class="col-md-4"><div class="sub">Jam</div><strong><?php echo e($reservasi->jam_rentang); ?> WIB</strong></div>
                            <div class="col-md-4"><div class="sub">Jumlah orang</div><strong><?php echo e($reservasi->jumlah_orang); ?> orang</strong></div>
                        </div>
                        <?php $__currentLoopData = $reservasi->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="d-flex justify-content-between py-2" style="border-top:1px solid var(--line)">
                                <span><?php echo e($item->menu->nama); ?> × <?php echo e($item->jumlah); ?></span>
                                <strong><?php echo e($rupiah($item->harga_satuan * $item->jumlah)); ?></strong>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>

                <div class="panel" style="background:#f5f0fb;">
                    <strong style="color:var(--purple-dark);"><i class="bi bi-info-circle me-1"></i> Aturan edit &amp; pembatalan</strong>
                    <p class="small mb-0 mt-1">
                        Edit tanggal, jam, jumlah orang, dan menu terkunci setelah resto mengonfirmasi. Pembatalan hanya sebelum check-in; setelah check-in tombol batal terkunci.
                    </p>
                </div>
            </div>

            
            <div class="col-lg-4">
                <div class="panel mb-3">
                    <h5 class="mb-3">Ringkasan pembayaran</h5>
                    <?php $__currentLoopData = $reservasi->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="d-flex justify-content-between py-1">
                            <span class="subtitle"><?php echo e($item->jumlah); ?> × <?php echo e($item->menu->nama); ?></span>
                            <span><?php echo e($rupiah($item->harga_satuan * $item->jumlah)); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span class="subtitle">Total pesanan</span>
                        <strong class="fs-5"><?php echo e($rupiah($reservasi->total_harga)); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="subtitle">DP <?php echo e($resto->dp_persen); ?>%</span>
                        <span><?php echo e($rupiah($reservasi->dp_nominal)); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="subtitle">Sisa bayar di resto</span>
                        <strong><?php echo e($rupiah(max(0, $reservasi->total_harga - $reservasi->dp_nominal))); ?></strong>
                    </div>
                    <?php if($reservasi->status_pembayaran === 'lunas'): ?>
                        <span class="status status-dikonfirmasi mt-2">Pembayaran lunas</span>
                    <?php endif; ?>
                </div>

                
                <?php if(in_array($reservasi->status, ['menunggu', 'dikonfirmasi'], true)): ?>
                    <div class="panel mb-3">
                        <h5 class="mb-2">Bukti DP</h5>

                        <?php if($reservasi->status_dp === 'diterima'): ?>
                            <span class="status status-dikonfirmasi">DP terverifikasi</span>
                            <?php if($reservasi->bukti_dp): ?>
                                <p class="small mt-2 mb-0"><a href="<?php echo e(asset('storage/'.$reservasi->bukti_dp)); ?>" target="_blank" rel="noopener">Lihat bukti</a></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="small subtitle mb-2">
                                Transfer DP <strong><?php echo e($rupiah($reservasi->dp_nominal)); ?></strong> ke
                                <?php echo e($resto->nama_bank ?? 'rekening resto'); ?>

                                <?php if($resto->no_rekening): ?> <strong><?php echo e($resto->no_rekening); ?></strong> <?php endif; ?>
                                <?php if($resto->atas_nama): ?> a.n. <?php echo e($resto->atas_nama); ?> <?php endif; ?>
                            </p>

                            <?php if($reservasi->status_dp === 'ditolak' && $reservasi->alasan_penolakan): ?>
                                <p class="small mb-2" style="color:#bd3448;">DP ditolak: <?php echo e($reservasi->alasan_penolakan); ?></p>
                            <?php endif; ?>

                            <?php if($reservasi->status_dp === 'menunggu_verifikasi'): ?>
                                <span class="status status-menunggu">Menunggu verifikasi resto</span>
                                <?php if($reservasi->bukti_dp): ?>
                                    <p class="small mt-2 mb-0"><a href="<?php echo e(asset('storage/'.$reservasi->bukti_dp)); ?>" target="_blank" rel="noopener">Lihat bukti terkirim</a></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" action="<?php echo e(route('customer.reservations.proof', $reservasi)); ?>" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <input type="file" name="bukti_dp" class="form-control mb-2" accept="image/*" required>
                                    <button class="btn btn-outline-purple w-100" type="submit">
                                        <?php echo e($reservasi->status_dp === 'ditolak' ? 'Unggah ulang bukti' : 'Kirim bukti DP'); ?>

                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                
                <?php if($reservasi->bisa_dibatalkan): ?>
                    <div class="panel mb-3">
                        <span class="status status-ditolak">Konfirmasi pembatalan</span>
                        <h5 class="mt-2">Batalkan reservasi ini?</h5>
                        <p class="subtitle small">
                            Meja untuk <?php echo e($reservasi->jumlah_orang); ?> orang akan dilepas. Jika DP sudah dibayar, pengembalian mengikuti kebijakan resto; hubungi resto untuk tindak lanjut.
                        </p>
                        <div class="row g-2">
                            <div class="col">
                                <a href="<?php echo e(route('customer.reservations')); ?>" class="btn btn-outline-purple w-100">Tetap reservasi</a>
                            </div>
                            <div class="col">
                                <form method="POST" action="<?php echo e(route('customer.reservations.cancel', $reservasi)); ?>"
                                    onsubmit="return confirm('Yakin ingin membatalkan reservasi ini?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn w-100"
                                        style="background:var(--red);color:#bd3448;border:1px solid #f1cbd2;">Ya, batalkan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="panel mb-3">
                        <h5>Pembatalan</h5>
                        <p class="subtitle small mb-0">
                            Reservasi ini tidak bisa dibatalkan lagi. Status saat ini: <?php echo e($reservasi->status_label); ?>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php $__env->startPush('styles'); ?>
        <style>
            .status-dibatalkan { background: var(--red); color: #bd3448; }
            .sub { color: var(--muted); font-size: 12px; }
            .menu-thumb { width: 64px; height: 64px; border-radius: 10px; object-fit: cover; flex-shrink: 0; }
            .menu-thumb-ph {
                display: flex; align-items: center; justify-content: center;
                background: var(--lav2); color: var(--purple); font-size: 24px;
            }
            .qty-box {
                display: inline-flex; align-items: center; border: 1px solid var(--line);
                border-radius: 10px; overflow: hidden; background: #fff;
            }
            .qty-btn { border: 0; background: #fff; color: var(--purple-dark); width: 36px; height: 38px; font-size: 18px; }
            .qty-btn:hover { background: var(--lav2); }
            .qty-input {
                width: 52px; border: 0; text-align: center; font-weight: 700;
                color: var(--ink); background: transparent; -moz-appearance: textfield;
            }
            .qty-input::-webkit-outer-spin-button,
            .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
            .qty-input:focus { outline: none; }
            .validasi { border-radius: 12px; padding: 12px 14px; font-size: 13px; }
            .validasi.ok { background: var(--green); color: #16805a; border: 1px solid #c9ead9; }
            .validasi.bad { background: var(--red); color: #bd3448; border: 1px solid #f1cbd2; }
        </style>
    <?php $__env->stopPush(); ?>

    <?php if($reservasi->bisa_diedit): ?>
        <?php $__env->startPush('scripts'); ?>
            <script>
                const dpRate = <?php echo e((int) $resto->dp_persen); ?>;
                const minPerOrang = <?php echo e(\App\Models\Reservasi::MINIMAL_PER_ORANG); ?>;
                const rupiah = n => 'Rp' + Math.round(n).toLocaleString('id-ID');

                function hitung() {
                    let total = 0;

                    document.querySelectorAll('.qty-input').forEach(el => {
                        const q = parseInt(el.value || 0);
                        const harga = parseInt(el.dataset.price);
                        total += q * harga;
                        el.closest('.menu-row').querySelector('.line').textContent = rupiah(harga) + ' × ' + q;
                    });

                    const orang = parseInt(document.getElementById('jumlah_orang').value || 0);
                    const minimum = minPerOrang;
                    const dp = Math.round(total * dpRate / 100);
                    const box = document.getElementById('validasi');

                    if (total > 0 && total >= minimum) {
                        box.className = 'validasi ok mt-3';
                        box.innerHTML = '<strong>Validasi perubahan: minimal pesan terpenuhi</strong><br>Total '
                            + rupiah(total) + ' ≥ minimum ' + orang + ' × ' + rupiah(minPerOrang) + ' = ' + rupiah(minimum)
                            + '. DP ' + dpRate + '% dihitung ulang menjadi ' + rupiah(dp) + '.';
                    } else {
                        box.className = 'validasi bad mt-3';
                        box.innerHTML = '<strong>Pesanan belum memenuhi minimal</strong><br>Total '
                            + rupiah(total) + ' masih di bawah minimum ' + orang + ' × ' + rupiah(minPerOrang) + ' = ' + rupiah(minimum) + '.';
                    }
                }

                document.querySelectorAll('.qty-btn').forEach(btn => btn.addEventListener('click', () => {
                    const input = btn.parentElement.querySelector('.qty-input');
                    const next = parseInt(input.value || 0) + parseInt(btn.dataset.step);
                    input.value = Math.min(parseInt(input.max), Math.max(0, next));
                    hitung();
                }));

                document.querySelectorAll('.qty-input').forEach(el => el.addEventListener('input', hitung));
                document.getElementById('jumlah_orang').addEventListener('input', hitung);
                hitung();
            </script>
        <?php $__env->stopPush(); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Reservia_Kelompok4_B\resources\views/customer/reservations-detail.blade.php ENDPATH**/ ?>