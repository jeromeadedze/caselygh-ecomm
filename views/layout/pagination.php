<?php
// Previous and next links. Expects $page, $pages and $pageUrl (a function from page number to URL).
if ($pages > 1):
?>
<nav class="pagination" aria-label="Pages">
    <?php if ($page > 1): ?>
        <a href="<?php echo e($pageUrl($page - 1)); ?>" class="btn btn-outline btn-sm" rel="prev">&larr; Previous</a>
    <?php endif; ?>
    <span class="muted small">Page <?php echo $page; ?> of <?php echo $pages; ?></span>
    <?php if ($page < $pages): ?>
        <a href="<?php echo e($pageUrl($page + 1)); ?>" class="btn btn-outline btn-sm" rel="next">Next &rarr;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
