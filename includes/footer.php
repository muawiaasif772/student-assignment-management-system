<?php
declare(strict_types=1);

$footerInstitution = $institution ?? get_setting('institution_name', '');
$footerYear = $academicYear ?? get_setting('academic_year', '');
$footerContact = $contactEmail ?? get_setting('contact_email', '');
?>
<?php if (!empty($publicLayout)): ?>
    </main>
    <footer class="site-footer">
        <div class="container d-flex flex-wrap gap-3 justify-content-between">
            <span><?= e(APP_SHORT) ?> · <?= e($footerInstitution) ?></span>
            <span><?= e($footerYear) ?><?php if ($footerContact !== ''): ?> · <?= e($footerContact) ?><?php endif; ?></span>
        </div>
    </footer>
</div>
<?php else: ?>
        </div>
    </main>
    <footer class="site-footer app-footer">
        <div class="container-fluid page-wrap d-flex flex-wrap gap-3 justify-content-between">
            <span><?= e(APP_SHORT) ?> · <?= e($footerInstitution) ?></span>
            <span><?= e($footerYear) ?></span>
        </div>
    </footer>
</div>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="confirmForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="confirmRecordId" value="">
                <input type="hidden" name="action" id="confirmAction" value="">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="confirmTitle">Please confirm</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><p id="confirmMessage" class="mb-0"></p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmSubmit">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<script src="<?= e(base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/script.js')) ?>"></script>
</body>
</html>
