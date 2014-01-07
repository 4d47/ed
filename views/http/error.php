<?php
$title = $exception->reason;
?>
<h1><?= htmlspecialchars($exception->reason) ?></h1>
<p><?= _("Something went wrong, we're looking into it.") ?></p>

<? if ($config['debug']): ?>
<pre>
    <?= htmlspecialchars(print_r($exception->getPrevious(), true)); ?>
</pre>
<? endif ?>
