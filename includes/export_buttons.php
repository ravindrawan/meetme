<!-- includes/export_buttons.php -->
<div class="d-flex gap-2 mb-3">
    <form method="post" action="includes/export.php" class="d-inline">
        <input type="hidden" name="format" value="excel">
        <input type="hidden" name="filters" value="<?= htmlspecialchars(json_encode($_GET)) ?>">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
    </form>
    <form method="post" action="includes/export.php" class="d-inline">
        <input type="hidden" name="format" value="pdf">
        <input type="hidden" name="filters" value="<?= htmlspecialchars(json_encode($_GET)) ?>">
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </button>
    </form>
</div>