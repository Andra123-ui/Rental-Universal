<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
?>
<?php if ($pager->getPageCount() > 1): ?>
    <ul style="display:flex;gap:8px;list-style:none;padding:0;margin:0;">
        <?php if ($pager->hasPrevious()): ?>
            <li><a href="<?= $pager->getPrevious() ?>"
                    style="padding:9px 14px;border:1px solid var(--line);border-radius:4px;">&laquo;</a></li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link): ?>
            <li>
                <a href="<?= $link['uri'] ?>"
                    style="display:inline-block;padding:9px 14px;border-radius:4px;border:1px solid var(--line);
                <?= $link['active'] ? 'background:var(--accent);color:#fff;border-color:var(--accent);' : 'background:var(--surface);color:var(--ink);' ?>">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNext()): ?>
            <li><a href="<?= $pager->getNext() ?>"
                    style="padding:9px 14px;border:1px solid var(--line);border-radius:4px;">&raquo;</a></li>
        <?php endif; ?>
    </ul>
<?php endif; ?>