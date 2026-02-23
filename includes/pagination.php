<!-- includes/pagination.php -->
<?php
$per_page = 10;
$total_pages = ceil($total / $per_page);
$current_page = max(1, min($total_pages, $page ?? 1));

if ($total_pages <= 1) return;
?>

<nav aria-label="Page navigation" class="mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <!-- First + Previous -->
        <div>
            <ul class="pagination mb-0">
                <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1">First</a>
                </li>
                <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= max(1, $current_page - 1) ?>">Previous</a>
                </li>
            </ul>
        </div>

        <!-- Page Numbers -->
        <div>
            <ul class="pagination mb-0">
                <?php
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);

                if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if ($start > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $start; $i <= $end; $i++) {
                    $active = ($i == $current_page) ? 'active' : '';
                    echo "<li class=\"page-item $active\"><a class=\"page-link\" href=\"?page=$i\">$i</a></li>";
                }

                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo "<li class=\"page-item\"><a class=\"page-link\" href=\"?page=$total_pages\">$total_pages</a></li>";
                }
                ?>
            </ul>
        </div>

        <!-- Next + Last -->
        <div>
            <ul class="pagination mb-0">
                <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= min($total_pages, $current_page + 1) ?>">Next</a>
                </li>
                <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $total_pages ?>">Last</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Go to Page -->
    <div class="text-center mt-3">
        <form class="d-inline-block" method="get">
            <?php foreach ($_GET as $key => $val): if ($key !== 'page'): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
            <?php endif; endforeach; ?>
            <div class="input-group input-group-sm" style="width: 200px;">
                <input type="number" name="page" class="form-control" min="1" max="<?= $total_pages ?>" 
                       placeholder="Page..." value="<?= $current_page ?>">
                <button class="btn btn-outline-primary" type="submit">Go</button>
            </div>
        </form>
        <div class="text-muted small mt-2">
            Page <?= $current_page ?> of <?= $total_pages ?> | 
            Showing <?= (($current_page - 1) * $per_page) + 1 ?>–<?= min($current_page * $per_page, $total) ?> of <?= $total ?> visits
        </div>
    </div>
</nav>