<?php $__env->startSection('title', 'Riwayat reservasi'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $rupiah = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
        $tabs = ['semua' => 'Semua', 'aktif' => 'Aktif', 'selesai' => 'Selesai', 'batal' => 'Dibatalkan'];
        $opsiStatus = [
            'menunggu' => 'Menunggu konfirmasi', 'dikonfirmasi' => 'Dikonfirmasi', 'check_in' => 'Sudah check-in',
            'selesai' => 'Selesai', 'ditolak' => 'Ditolak resto', 'dibatalkan' => 'Dibatalkan',
        ];
        $opsiDp = [
            'belum_bayar' => 'Belum bayar', 'menunggu_verifikasi' => 'Menunggu verifikasi',
            'diterima' => 'Terverifikasi', 'ditolak' => 'Ditolak',
        ];
    ?>

    <div class="page">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-1">Riwayat reservasi</h1>
                <p class="subtitle mb-0">Semua rencana makan Anda, beserta status reservasi dan pembayaran yang terpisah.</p>
            </div>
            <a href="<?php echo e(route('customer.index')); ?>" class="btn btn-purple">
                <i class="bi bi-plus-lg me-1"></i> Cari resto lagi
            </a>
        </div>

        
        <form method="GET" action="<?php echo e(route('customer.reservations')); ?>" class="row g-3 mb-3">
            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Cari kode booking atau resto</label>
                <div class="position-relative">
                    <input type="text" class="form-control" name="q" value="<?php echo e(request('q')); ?>" placeholder="Cari reservasi Anda…">
                    <button type="submit" class="btn border-0 position-absolute top-50 end-0 translate-middle-y" aria-label="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Periode kunjungan</label>
                <input type="month" class="form-control" name="periode" value="<?php echo e(request('periode')); ?>" onchange="this.form.submit()">
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Status reservasi</label>
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <?php $__currentLoopData = $opsiStatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nilai => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($nilai); ?>" <?php if(request('status') === $nilai): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="field-label">Status DP</label>
                <select class="form-select" name="dp" onchange="this.form.submit()">
                    <option value="">Semua status DP</option>
                    <?php $__currentLoopData = $opsiDp; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nilai => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($nilai); ?>" <?php if(request('dp') === $nilai): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </form>

        
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="chip <?php echo e($tab === $key ? 'active' : ''); ?>"
                   href="<?php echo e(route('customer.reservations', array_merge(request()->except(['page', 'tab']), ['tab' => $key]))); ?>">
                    <?php echo e($label); ?> · <?php echo e($hitung[$key]); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Reservasi Anda</h5>
                <span class="sub"><?php echo e(request('periode') ? \Carbon\Carbon::createFromFormat('!Y-m', request('periode'))->locale('id')->translatedFormat('F Y') : 'Semua periode'); ?></span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="thead-lav">
                        <tr>
                            <th>Booking / resto</th>
                            <th>Tanggal & jam</th>
                            <th>Status reservasi</th>
                            <th>Status DP</th>
                            <th>Total pesanan</th>
                            <th>Sisa bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $reservasis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo e($r->kode_booking); ?></div>
                                    <div class="sub"><?php echo e($r->resto->nama); ?> · <?php echo e($r->jumlah_orang); ?> orang</div>
                                </td>
                                <td>
                                    <div><?php echo e($r->tanggal->locale('id')->translatedFormat('d M Y')); ?></div>
                                    <div class="sub"><?php echo e($r->jam_rentang); ?></div>
                                </td>
                                <td><span class="status status-<?php echo e($r->status); ?>"><?php echo e($r->status_label); ?></span></td>
                                <td>
                                    <span class="status <?php echo e($r->dp_kelas); ?>"><?php echo e($r->dp_label); ?></span>
                                    <div class="sub mt-1"><?php echo e($r->dp_catatan); ?></div>
                                </td>
                                <td><?php echo e($rupiah($r->total_harga)); ?></td>
                                <td><?php echo e($rupiah($r->sisa_bayar)); ?></td>
                                <td>
                                    <a href="<?php echo e(route('customer.reservations.show', $r)); ?>"
                                       class="btn btn-sm <?php echo e($r->bisa_diedit ? 'btn-purple' : 'btn-outline-purple'); ?>">
                                        <?php echo e($r->bisa_diedit ? 'Detail / ubah' : 'Detail'); ?>

                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <h5>Tidak ada reservasi</h5>
                                    <p class="subtitle mb-3">Belum ada reservasi yang cocok dengan filter ini.</p>
                                    <a href="<?php echo e(route('customer.index')); ?>" class="btn btn-purple">Cari resto</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <span class="sub">
                    Menampilkan <?php echo e($reservasis->firstItem() ?? 0); ?>–<?php echo e($reservasis->lastItem() ?? 0); ?> dari <?php echo e($reservasis->total()); ?> reservasi · Nominal sisa bukan tagihan untuk reservasi yang batal.
                </span>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (! ($reservasis->onFirstPage())): ?>
                        <a class="btn btn-sm btn-outline-purple" href="<?php echo e($reservasis->previousPageUrl()); ?>">Sebelumnya</a>
                    <?php endif; ?>
                    <span class="btn btn-sm btn-outline-purple disabled">Halaman <?php echo e($reservasis->currentPage()); ?></span>
                    <?php if($reservasis->hasMorePages()): ?>
                        <a class="btn btn-sm btn-outline-purple" href="<?php echo e($reservasis->nextPageUrl()); ?>">Berikutnya</a>
                    <?php endif; ?>
                </div>
            </div>

            <p class="small mt-3 mb-0" style="color: var(--purple-dark);">
                Bukti yang masih diverifikasi belum dianggap pembayaran final. Sisa akan menyesuaikan jika DP ditolak.
            </p>
        </div>
    </div>

    <?php $__env->startPush('styles'); ?>
        <style>
            .status-dibatalkan { background: var(--red); color: #bd3448; }
            .sub { color: var(--muted); font-size: 12px; }
            .chip {
                border: 1px solid var(--line); background: #fff; color: var(--purple-dark);
                border-radius: 10px; padding: 8px 14px; font-size: 13px; font-weight: 600; text-decoration: none;
            }
            .chip:hover { background: var(--lav2); color: var(--purple-dark); }
            .chip.active { background: var(--purple); border-color: var(--purple); color: #fff; }
            .thead-lav th {
                background: var(--lav); color: var(--muted); font-size: 12px; font-weight: 600;
                border: 0; white-space: nowrap;
            }
        </style>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\Reservia_Kelompok4_B\resources\views/customer/reservations.blade.php ENDPATH**/ ?>